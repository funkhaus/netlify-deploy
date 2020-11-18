<?php
/*
Plugin Name: Netlify Deploy
Description: Trigger a deploy to Netlify either automatically or with a button in the dashboard.
Version: 1.0
Author: Funkhaus
Plugin URI:  https://github.com/funkhaus/netlify-deploy
Author URI:  http://funkhaus.us
*/

/*
 * Import required files
 */
include_once plugin_dir_path(__FILE__) . "includes/utilities.php";
include_once plugin_dir_path(__FILE__) . "includes/settings.php";
include_once plugin_dir_path(__FILE__) . "includes/admin-bar.php";
include_once plugin_dir_path(__FILE__) . "includes/api.php";
include_once plugin_dir_path(__FILE__) . "includes/auto-deploy.php";

/*
 * Plugin activated, setup default options
 */
function nd_plugin_activated()
{
    $defaults = [
        "last_deploy" => "",
        "auto_deploy" => 0,
    ];
    add_option("nd_settings", $defaults);
}
register_activation_hook(__FILE__, "nd_plugin_activated");

/*
 * Enqueue scripts and styles
 */
function nd_admin_scripts()
{
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data["Version"];

    // Add required scripts and styles
    wp_enqueue_script("jquery");
    wp_enqueue_script(
        "nd_main",
        plugins_url("js/main.js", __FILE__),
        null,
        $plugin_version
    );
    wp_enqueue_style(
        "nd_main",
        plugins_url("css/main.css", __FILE__),
        null,
        $plugin_version
    );
    wp_enqueue_style("dashicons");

    // Inject some PHP vars into JS
    $options = get_option("nd_settings");
    $js_vars = [
        "url" => site_url("/wp-json/nd"),
        "nonce" => wp_create_nonce("wp_rest"),
    ];
    wp_add_inline_script(
        "nd_main",
        "var nd_vars = " . wp_json_encode($js_vars),
        "before"
    );
}
add_action("admin_enqueue_scripts", "nd_admin_scripts");

/*
 * Register custom API endpoints
 */
function nd_add_custom_api_routes()
{
    // Use this to trigger a deploy at Netlify
    register_rest_route("nd", "/deploy", [
        [
            "methods" => "POST",
            "callback" => "nd_deploy_post",
            "permission_callback" => "nd_check_user_can_publish",
        ],
    ]);

    // Use this to get status of a deploy at Netlify
    register_rest_route("nd", "/build", [
        [
            "methods" => "GET",
            "callback" => "nd_build_get",
            "permission_callback" => "nd_check_user_can_publish",
        ],
    ]);
}
add_action("rest_api_init", "nd_add_custom_api_routes");

/*
 * This customizes the CORS headers the server will accept, allows use of token header
 */
function add_custom_cors_headers()
{
    add_filter("rest_pre_serve_request", function ($value) {
        header("Access-Control-Allow-Headers: Content-Type");
        return $value;
    });
}
add_action("rest_api_init", "add_custom_cors_headers", 15);
