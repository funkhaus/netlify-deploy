<?php

/*
 * Register the new top level settings page
 */
/*
    function nd_add_admin_menu()
    {
        add_menu_page(
            'Netlify Deploy',
            'Netlify Deploy',
            'manage_options',
            'netlify_deploy',
            'nd_options_page',
            'dashicons-networking'
        );
    }
    add_action('admin_menu', 'nd_add_admin_menu');
*/

/*
 * Register a new settings page under Settings sub-menu
 */
function nd_register_options_page()
{
    $title = nd_get_provider_label() . " Deploy";
    add_options_page(
        $title,
        $title,
        "manage_options",
        "netlify-deploy",
        "nd_options_page"
    );
}
add_action("admin_menu", "nd_register_options_page");

/*
 * Register the new settings fields on the new Admin page
 */
function nd_settings_init()
{
    register_setting("nd_plugin", "nd_settings", [
        "sanitize_callback" => "nd_sanitize_settings",
    ]);

    add_settings_section(
        "nd_plugin_section",
        "Settings",
        "nd_settings_section_render",
        "nd_plugin"
    );

    add_settings_field(
        "provider",
        "Frontend host",
        "nd_provider_render",
        "nd_plugin",
        "nd_plugin_section"
    );

    // Only show fields for the saved provider. Hidden fields keep their values (see nd_sanitize_settings).
    if (nd_get_provider() === "sevalla") {
        add_settings_field(
            "sevalla_site_id",
            "Sevalla static site ID",
            "nd_sevalla_site_id_render",
            "nd_plugin",
            "nd_plugin_section"
        );

        add_settings_field(
            "sevalla_api_key",
            "Sevalla API key",
            "nd_sevalla_api_key_render",
            "nd_plugin",
            "nd_plugin_section"
        );
    } else {
        add_settings_field(
            "build_hook_url",
            "Netlify build hook URL",
            "nd_build_hook_url_render",
            "nd_plugin",
            "nd_plugin_section"
        );

        add_settings_field(
            "status_url",
            "Netlify status badge URL",
            "nd_status_url_render",
            "nd_plugin",
            "nd_plugin_section"
        );
    }

    add_settings_field(
        "auto_deploy",
        "Auto deploy on publish",
        "nd_auto_depoy_render",
        "nd_plugin",
        "nd_plugin_section"
    );
}
add_action("admin_init", "nd_settings_init");

/*
 * Merge submitted fields over the saved options, so fields not on the form
 * (last_deploy, the other provider's settings) aren't wiped on save.
 */
function nd_sanitize_settings($input)
{
    $options = get_option("nd_settings");
    $options = is_array($options) ? $options : [];
    $input = is_array($input) ? $input : [];

    // The settings form always submits provider. Code that calls update_option() passes the full options array.
    $is_form_submit = isset($input["provider"]);

    foreach (["build_hook_url", "status_url"] as $key) {
        if (isset($input[$key])) {
            $options[$key] = esc_url_raw(trim($input[$key]));
        }
    }
    if (isset($input["sevalla_site_id"])) {
        $options["sevalla_site_id"] = sanitize_text_field(
            trim($input["sevalla_site_id"])
        );
    }
    if (isset($input["provider"])) {
        $options["provider"] =
            $input["provider"] === "sevalla" ? "sevalla" : "netlify";
    }
    if (isset($input["last_deploy"])) {
        $options["last_deploy"] = $input["last_deploy"];
    }

    // An unchecked checkbox isn't submitted at all, so on a form save its absence means off
    if ($is_form_submit) {
        $options["auto_deploy"] = empty($input["auto_deploy"]) ? 0 : 1;
    } elseif (isset($input["auto_deploy"])) {
        $options["auto_deploy"] = $input["auto_deploy"];
    }

    // Settings changed, so don't trust the cached deploy status
    delete_transient("nd_sevalla_status");

    return $options;
}

/*
 * Render function
 */
function nd_provider_render()
{
    $provider = nd_get_provider(); ?>
            <select class="input input-provider" name="nd_settings[provider]">
                <option value="netlify" <?php selected($provider, "netlify"); ?>>Netlify</option>
                <option value="sevalla" <?php selected($provider, "sevalla"); ?>>Sevalla</option>
            </select>

            <p class="description">Where the frontend is built and hosted. Save to show that host's settings.</p>
        <?php
}

/*
 * Render function
 */
function nd_sevalla_site_id_render()
{
    $options = get_option("nd_settings");
    $site_id = $options["sevalla_site_id"] ?? "";
    ?>
            <input type='text' class="input input-sevalla-site-id regular-text code" name='nd_settings[sevalla_site_id]' value='<?php echo esc_attr(
                $site_id
            ); ?>' placeholder="1a2b3c4d-1234-5678-9abc-1a2b3c4d5e6f">

            <p class="description">The static site's ID, found in its URL in the <a href="https://app.sevalla.com/" target="_blank">Sevalla</a> dashboard or via the API (<code>GET /v3/static-sites</code>).</p>
        <?php
}

/*
 * Render function. The key itself is never shown.
 */
function nd_sevalla_api_key_render()
{
    if (nd_sevalla_has_api_key()): ?>
            <p><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Set in wp-config.php (<code>ND_SEVALLA_API_KEY</code>).</p>
        <?php else: ?>
            <p class="error" style="color: #e65054;">Missing. Add <code>define("ND_SEVALLA_API_KEY", "…");</code> to wp-config.php.</p>
        <?php endif; ?>

            <p class="description">Create the key in Sevalla under Integration &gt; API keys with custom capabilities <code>STATIC_SITE:READ</code> and <code>STATIC_SITE:UPDATE</code>, both scoped to this one static site. It lives in wp-config.php so it stays out of the database and DB exports.</p>
        <?php
}

/*
 * Render function for what comes after the new settings group title.
 */
function nd_settings_section_render()
{
    //echo 'Config when the Netlify Deploy plugin below.';
}

/*
 * Render function
 */
function nd_status_url_render()
{
    $options = get_option("nd_settings"); ?>
            <input type='text' class="input input-status-url regular-text code" name='nd_settings[status_url]' value='<?php echo $options[
                "status_url"
            ] ??
                ""; ?>' placeholder="https://api.netlify.com/api/v1/badges/1234-5789-0123/deploy-status">

            <p class="description">The "Deploy status badge" URL found in the <a href="https://app.netlify.com/" target="_blank">Netlify</a> admin under `Site Settings > General > Deploy status badge` settings page. Note, you only need to enter the URL, not the full badge markdown code.</p>

        <?php 
            $url = $options["status_url"];
            if( strpos($url, ']') !== false || strpos($url, "[") !== false ) {
                ?>
                    <p class="error" style="color: #e65054;">It seems the URL contains some invalid characters. Are you sure you entered just the URL and not the Netlify markdown code?</p>
                <?php
            }
}

/*
 * Render function
 */
function nd_build_hook_url_render()
{
    $options = get_option("nd_settings");
    $build_hook = $options["build_hook_url"] ?? "";
    ?>
            <input type='text' class="input input-build-hook regular-text code" name='nd_settings[build_hook_url]' value='<?php echo $build_hook; ?>' placeholder="https://api.netlify.com/build_hooks/123456789abcdefgh">

            <p class="description">A "Build hook" URL found in the <a href="https://app.netlify.com/" target="_blank">Netlify</a> admin under `Site Settings > Build & Deploy > Build hooks` settings page. Recommended to name this hook "WordPress" but it's optional.</p>

        <?php
}

/*
 * Render function
 */
function nd_auto_depoy_render()
{
    $options = get_option("nd_settings"); ?>
            <input type='checkbox' id="nd-auto-deploy" class="input input-auto-deploy" name='nd_settings[auto_deploy]' <?php checked(
                $options["auto_deploy"],
                1
            ); ?> value='1'> <label for="nd-auto-deploy">Yes</label>

            <p class="description">When enabled, anytime content has changed, the plugin will automatically trigger a deploy.</p>
        <?php
}

/*
 * Render function for the new Admin page. This controls the output of everything on the page.
 */
function nd_options_page()
{
    $options = get_option("nd_settings");
    $status_url = $options["status_url"] ?? "";

    // Shape data for logging of last content publish
    $last_content = nd_get_last_published_content();
    $title = $last_content->post_title;
    $type = $last_content->post_type;

    if ($type == "nav_menu_item") {
        $title = "";
        $type = "A menu was updated";
    }

    // Get the last time a deploy was sent to the provider from WordPress
    $last_deploy = nd_get_last_deploy_datetimestamp();
    if (!$last_deploy) {
        $last_deploy = "Never";
    }

    // Shape deploy needed
    $needed = "No";
    if (nd_deploy_needed()) {
        $needed = "Yes";
    }
    ?>

        <div id="page-nd-options" class="wrap page-nd-options">
            <h1><?php echo nd_get_provider_label(); ?> Deploy</h1>

            <p>This plugin enables auto deploys to <?php echo nd_get_provider_label(); ?> when any post types, menus or attachments change in WordPress. It also adds a manual Deploy button to the admin bar.</p>

            <h2>Deploy status</h2>

            <p>
                Last updated content: <?php echo $title; ?> <?php echo $type; ?> at <?php echo $last_content->post_modified; ?><br>
                Last deploy time: <?php echo $last_deploy; ?><br>
                Deploy recommended: <?php echo $needed; ?>
            </p>

            <?php if ($status_url && nd_get_provider() === "netlify"): ?>
            <img class="nd-status-image" src="<?php echo $status_url; ?>"/>
            <?php endif; ?>

            <form action='options.php' method='post'>

                <?php
                // Required WP functions so form submits correctly.
                settings_fields("nd_plugin");
                do_settings_sections("nd_plugin");
                submit_button();?>

            </form>


        </div>

        <?php
}
