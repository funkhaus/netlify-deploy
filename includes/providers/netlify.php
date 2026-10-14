<?php

/*
 * Netlify is configured if a build hook URL is saved
 */
function nd_netlify_is_configured($options)
{
    return !empty($options["build_hook_url"]);
}

/*
 * POST to the Netlify build hook. Returns true or WP_Error.
 */
function nd_netlify_trigger_deploy($options)
{
    $response = wp_remote_post($options["build_hook_url"]);

    if (is_wp_error($response)) {
        return new WP_Error(
            "network_error",
            "Something went wrong with the network request.",
            [
                "status" => 400,
            ]
        );
    }

    return true;
}

/*
 * Return latest build status, read from the Netlify status badge
 */
function nd_netlify_get_status($options)
{
    $content_header = "";
    $url = $options["status_url"] ?? "";

    // Get staus badge headers
    if (!empty($url) && wp_http_validate_url($url)) {
        $response = wp_remote_head($url);

        // Abort if error
        if (is_wp_error($response)) {
            return "unknown";
        }

        if ($response["headers"]) {
            $content_header = (string) $response["headers"]->offsetGet(
                "content-disposition"
            );
        }
    }

    // Determine status from content-disposition header (it inlcudes the SVG filename so we look for that).
    $status = "unknown";
    switch (true) {
        case strpos($content_header, "success") !== false:
            $status = "success";
            break;

        case strpos($content_header, "building") !== false:
            $status = "building";
            break;

        case strpos($content_header, "canceled") !== false:
            $status = "canceled";
            break;

        case strpos($content_header, "failed") !== false:
            $status = "failed";
            break;
    }

    return $status;
}
