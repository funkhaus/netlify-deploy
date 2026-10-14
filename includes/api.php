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
 * Trigger a deploy at the configured provider (Netlify build hook or Sevalla API)
 */
function nd_deploy_post()
{
    // Return error if the provider isn't set up in admin
    if (!nd_is_configured()) {
        $message =
            nd_get_provider() === "sevalla"
                ? "You need to define a Sevalla site ID on the settings page and ND_SEVALLA_API_KEY in wp-config.php."
                : "You need to define a Build hook url on the settings page.";

        return new WP_Error("invalid_url", $message, [
            "status" => 422,
        ]);
    }

    // If there was an error trying to hit the provider
    $result = nd_trigger_deploy();
    if (is_wp_error($result)) {
        return $result;
    }

    // Build return data
    $timestamp = gmdate("Y-m-d H:i:s");
    $data = [
        "success" => true,
        "datetimestamp" => $timestamp,
        "nonce" => wp_create_nonce("wp_rest"),
    ];

    // Save last deploy time
    $options = get_option("nd_settings");
    $options["last_deploy"] = $timestamp;
    update_option("nd_settings", $options);

    return new WP_REST_Response($data, 200);
}

/*
 * Get the build status from the provider
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
