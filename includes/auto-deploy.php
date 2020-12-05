<?php
/*
 * Deounce requests to deploy
 */
function nd_debounce_deploy($object_id)
{

    // Abort if doing an auto save of a revision
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Abort if NOT publishing something
    if( get_post_status($object_id) !== "publish") {
        return;        
    }
    
    // Abort early if we have no settings
    $options = get_option("nd_settings");    
    if ( empty($options["auto_deploy"]) || empty($options["build_hook_url"]) ) {
        return;
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
add_action("wp_update_nav_menu", "nd_debounce_deploy", 20, 1);
add_action("save_post", "nd_debounce_deploy", 20, 1);
add_action("attachment_updated", "nd_debounce_deploy", 20, 1);
add_action("nestedpages_post_order_updated", "nd_debounce_deploy", 20, 1);

/*
 * Run an auto deploy event
 */
function nd_run_auto_deploy()
{
    nd_deploy_post();
}
add_action("nd_run_auto_deploy", "nd_run_auto_deploy");
