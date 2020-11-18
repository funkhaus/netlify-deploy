<?php
/*
 * Deounce requests to deploy
 */
function nd_debounce_deploy($object_id)
{
    $options = get_option("nd_settings");

    // Abort early
    if (empty($options["auto_deploy"]) || empty($options["build_hook_url"])) {
        return false;
    }

    $deploy_time = get_transient("nd_deploy_time");

    if ($deploy_time) {
        // Try again in 60 seconds
        wp_clear_scheduled_hook("nd_run_auto_deploy");
        wp_schedule_single_event($deploy_time, "nd_run_auto_deploy");
    } else {
        nd_deploy_post();
        set_transient("nd_deploy_time", time() + 59, 60);
    }
}
add_action("wp_update_nav_menu", "nd_debounce_deploy");
add_action("save_post", "nd_debounce_deploy");
add_action("attachment_updated", "nd_debounce_deploy");
add_action("nestedpages_post_order_updated", "nd_debounce_deploy");

/*
 * Run an auto deploy
 */
function nd_run_auto_deploy()
{
    nd_deploy_post();
}
add_action("nd_run_auto_deploy", "nd_run_auto_deploy");
