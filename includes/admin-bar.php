<?php

/*
 * Add the deply button to the admin bar at top of WP dashboard
 */
function nd_toolbar_item($wp_admin_bar)
{
    $options = get_option("nd_settings");
    $status = nd_get_build_status();

    $title = '
        <span class="msg msg-success"><span class="dashicons dashicons-admin-site"></span> New Deploy</span>
        <span class="msg msg-building"><span class="dashicons dashicons-backup"></span> Deploying</span>
        <span class="msg msg-failed"><span class="dashicons dashicons-dismiss"></span> Deploy failed</span>
    ';

    $args = [
        "id" => "nd-deploy-button",
        "title" => $title,
        "href" =>
            admin_url() . "options-general.php?page=netlify-deploy&deploy=1",
        "meta" => [
            "class" => "nd-deploy-button nd-status-" . $status,
        ],
    ];

    if (!empty($options["build_hook_url"])) {
        $wp_admin_bar->add_node($args);
    }
}
add_action("admin_bar_menu", "nd_toolbar_item", 999);
