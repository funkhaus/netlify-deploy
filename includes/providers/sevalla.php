<?php

/*
 * Sevalla static sites have no build hook URL, so deploys go through the REST API.
 * Use an API key scoped to the one static site with STATIC_SITE:READ + STATIC_SITE:UPDATE
 * (there is no narrower deploy-only permission). The key is read from the
 * ND_SEVALLA_API_KEY constant in wp-config.php so it never lands in the database,
 * a DB export, or the settings form.
 */
define("ND_SEVALLA_API_URL", "https://api.sevalla.com/v3");

/*
 * Sevalla is configured if the API key constant and a site ID are both set
 */
function nd_sevalla_is_configured($options)
{
    return nd_sevalla_has_api_key() && !empty($options["sevalla_site_id"]);
}

/*
 * Is the API key constant defined in wp-config.php
 */
function nd_sevalla_has_api_key()
{
    return defined("ND_SEVALLA_API_KEY") && !empty(ND_SEVALLA_API_KEY);
}

/*
 * Make a request to the Sevalla API. Returns decoded JSON or WP_Error.
 */
function nd_sevalla_request($method, $path, $body = null)
{
    $args = [
        "method" => $method,
        "timeout" => 15,
        "headers" => [
            "Authorization" => "Bearer " . ND_SEVALLA_API_KEY,
        ],
    ];
    if ($body !== null) {
        $args["headers"]["Content-Type"] = "application/json";
        $args["body"] = wp_json_encode($body);
    }

    $response = wp_remote_request(ND_SEVALLA_API_URL . $path, $args);

    if (is_wp_error($response)) {
        return new WP_Error(
            "network_error",
            "Something went wrong with the network request.",
            [
                "status" => 400,
            ]
        );
    }

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // A 403 usually means the key isn't scoped to this site, or lacks STATIC_SITE:UPDATE
    if ($code < 200 || $code >= 300) {
        return new WP_Error(
            "sevalla_error",
            "Sevalla API error: " . ($data["message"] ?? "HTTP " . $code),
            [
                "status" => 502,
            ]
        );
    }

    return $data;
}

/*
 * Return the API path for the configured static site
 */
function nd_sevalla_site_path($options, $path = "")
{
    return "/static-sites/" . rawurlencode($options["sevalla_site_id"]) . $path;
}

/*
 * Deploy the static site's default branch. Returns true or WP_Error.
 */
function nd_sevalla_trigger_deploy($options)
{
    $deployment = nd_sevalla_request(
        "POST",
        nd_sevalla_site_path($options, "/deployments"),
        new stdClass()
    );

    if (is_wp_error($deployment)) {
        return $deployment;
    }

    // Show the new deploy straight away instead of the cached status
    delete_transient("nd_sevalla_status");

    return true;
}

/*
 * Return the status of the latest deployment, whatever triggered it (WordPress, a git push, the dashboard).
 * Cached briefly so admin bar polling stays well under Sevalla's 120 requests/minute company-wide limit.
 */
function nd_sevalla_get_status($options)
{
    $status = get_transient("nd_sevalla_status");
    if ($status) {
        return $status;
    }

    $status = "unknown";
    $list = nd_sevalla_request(
        "GET",
        nd_sevalla_site_path($options, "/deployments?limit=1")
    );
    if (!is_wp_error($list) && !empty($list["data"][0])) {
        $deployment = $list["data"][0];
        $status = nd_sevalla_map_status($deployment["status"] ?? "");

        // Sevalla reports success ~1 minute before the edge serves the new build,
        // so keep showing "building" until changes are actually visible
        $finished = strtotime($deployment["updated_at"] ?? "");
        if ($status === "success" && $finished && time() - $finished < 90) {
            $status = "building";
        }
    }

    set_transient("nd_sevalla_status", $status, $status === "building" ? 10 : 60);

    return $status;
}

/*
 * Map Sevalla deployment statuses onto the ones the admin bar knows
 */
function nd_sevalla_map_status($sevalla_status)
{
    switch ($sevalla_status) {
        case "waiting":
        case "inProgress":
            return "building";

        case "success":
        case "skipped":
            return "success";

        case "failed":
            return "failed";

        case "cancelled":
            return "canceled";
    }

    return "unknown";
}
