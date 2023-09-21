<?php

/**
 * Checks if the user is logged into WordPress and can publish a post. Used as permission_callback of WP JSON.
 *
 * @return Boolean|WP_Error true if valid token, otherwise error
 */
function nd_check_user_can_publish($request)
{
    // For this to work, generate a nonce using: `wp_create_nonce('wp_rest')` and set it as a HTTP header `X-WP-Nonce`.
    // Must also include valid WP logged in cookies in request headers too (credentials: include and CORS on)
    return current_user_can("publish_posts");
    //return true;
}

/**
 * Post to the Netlify build hook
 */
function nd_deploy_post()
{
    $options = get_option("nd_settings");

    $url = $options["build_hook_url"] ?? "";

    if ($url) {
        // Attempt to POST to Netlify build
        $response = wp_remote_post($url);
    } else {
        // Return error if no URL set in admin
        return new WP_Error(
            "invalid_url",
            "You need to define a Build hook url on the settings page.",
            [
                "status" => 422,
            ]
        );
    }

    // If there was an error trying to hit Netlify
    if (is_wp_error($response)) {
        return new WP_Error(
            "network_error",
            "Something went wrong with the network request.",
            [
                "status" => 400,
            ]
        );
    }

    // Build return data
    $timestamp = gmdate("Y-m-d H:i:s");
    $data = [
        "success" => true,
        "datetimestamp" => $timestamp,
        "nonce" => wp_create_nonce("wp_rest"),
    ];

    // Save last deploy time
    $options["last_deploy"] = $timestamp;
    update_option("nd_settings", $options);

    return new WP_REST_Response($data, 200);
}

/*
 * Get the build status from Netlify
 */
function nd_build_get()
{
    // Build output
    $data = [
        "success" => true,
        "data" => [
            "status" => nd_get_build_status(),
        ],
        "nonce" => wp_create_nonce("wp_rest"),
    ];

    return new WP_REST_Response($data, 200);
}
