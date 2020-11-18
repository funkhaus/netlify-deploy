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
    add_options_page(
        "Netlify Deploy",
        "Netlify Deploy",
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
    register_setting("nd_plugin", "nd_settings");

    add_settings_section(
        "nd_plugin_section",
        "Settings",
        "nd_settings_section_render",
        "nd_plugin"
    );

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
}

/*
 * Render function
 */
function nd_build_hook_url_render()
{
    $options = get_option("nd_settings"); ?>
            <input type='text' class="input input-build-hook regular-text code" name='nd_settings[build_hook_url]' value='<?php echo $options[
                "build_hook_url"
            ] ??
                ""; ?>' placeholder="https://api.netlify.com/build_hooks/123456789abcdefgh">

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

    // Shape data for logging of last content publish
    $last_content = nd_get_last_published_content();
    $title = $last_content->post_title;
    $type = $last_content->post_type;

    if ($type == "nav_menu_item") {
        $title = "";
        $type = "A menu was updated";
    }

    // Get the last time a deploy was sent to Netlify from WordPress
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
            <h1>Netlify Deploy</h1>

            <p>This plugin enables auto deploys to Netlify when any post types, menus or attachments change in WordPress. It also adds a manual Deploy button to the admin bar.</p>

            <h2>Deploy status</h2>

            <p>
                Last updated content: <?php echo $title; ?> <?php echo $type; ?> at <?php echo $last_content->post_modified; ?><br>
                Last deploy time: <?php echo $last_deploy; ?><br>
                Deploy recommended: <?php echo $needed; ?>
            </p>

            <img class="nd-status-image" src="<?php echo $options[
                "status_url"
            ]; ?>"/>

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
