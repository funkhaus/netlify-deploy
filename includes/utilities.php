<?php

/*
 * Return the last time WordPress deployed all published content to Netlify
 */
function nd_get_last_deploy_datetimestamp()
{
    $options = get_option("nd_settings");

    return $options["last_deploy"] ?? "";
}

/*
 * Return the last piece of content created/updated in WordPress
 */
function nd_get_last_published_content()
{
    // Get all types to watch for updates
    $types = ["any", "nav_menu_item", "attachment"];
    $cpt_types = get_post_types([
        "show_in_graphql" => true,
    ]);

    // Get most recent published content
    $args = [
        "post_type" => array_merge($types, $cpt_types),
        "post_status" => "publish",
        "order" => "DESC",
        "orderby" => "modified",
        "posts_per_page" => 1,
    ];
    $query = new WP_Query($args);

    return $query->posts[0] ?? false;
}

/*
 * Return boolean if new content time is newer than the last deployed time
 */
function nd_deploy_needed()
{
    $options = get_option("nd_settings");
    $last_deply = $options["last_deploy"] ?? false;

    // Exit early if never deployed
    if (!$last_deply) {
        return true;
    }

    // Get last content time, default to beginning of time.
    $last_content = nd_get_last_published_content();
    $last_timestamp = gmdate("Y-m-d H:i:s", 0);
    if ($last_content) {
        $last_timestamp = $last_content->post_modified_gmt;
    }

    return $last_timestamp >= $last_deply;
}

/*
 * Return the frontend host deploys go to: "netlify" (default) or "sevalla"
 */
function nd_get_provider()
{
    $options = get_option("nd_settings");

    return ($options["provider"] ?? "") === "sevalla" ? "sevalla" : "netlify";
}

/*
 * Return the frontend host's display name
 */
function nd_get_provider_label()
{
    return nd_get_provider() === "sevalla" ? "Sevalla" : "Netlify";
}

/*
 * Return boolean if the current provider has everything it needs to deploy
 */
function nd_is_configured()
{
    $options = get_option("nd_settings");

    if (nd_get_provider() === "sevalla") {
        return nd_sevalla_is_configured($options);
    }

    return nd_netlify_is_configured($options);
}

/*
 * Trigger a deploy at the current provider. Returns true or WP_Error.
 */
function nd_trigger_deploy()
{
    $options = get_option("nd_settings");

    if (nd_get_provider() === "sevalla") {
        return nd_sevalla_trigger_deploy($options);
    }

    return nd_netlify_trigger_deploy($options);
}

/*
 * Return latest build status: success, building, failed, canceled or unknown
 */
function nd_get_build_status()
{
    $options = get_option("nd_settings");

    if (nd_get_provider() === "sevalla") {
        return nd_is_configured() ? nd_sevalla_get_status($options) : "unknown";
    }

    return nd_netlify_get_status($options);
}
