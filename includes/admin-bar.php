<?php

/*
 * Add the deply button to the admin bar at top of WP dashboard
 */
function nd_toolbar_item($wp_admin_bar)
{
    $status = nd_is_configured() ? nd_get_build_status() : "no-build-hook";

    $title = '
        <span class="msg msg-success" title="Last deploy succeeded. Click to trigger new deploy."><span class="dashicons dashicons-admin-site"></span> New Deploy</span>
        <span class="msg msg-building" title="Deploy building"><span class="dashicons dashicons-backup"></span> Deploying</span>
        <span class="msg msg-failed" title="Deploy failed"><span class="dashicons dashicons-warning"></span> Deploy failed</span>
        <span class="msg msg-canceled" title="Deploy canceled"><span class="dashicons dashicons-dismiss"></span> Deploy cancelled</span>       
        <span class="msg msg-unknown" title="Deploy status unknown"><span class="dashicons dashicons-flag"></span> Deploy status unknown</span>               
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

    $wp_admin_bar->add_node($args);
}
add_action("admin_bar_menu", "nd_toolbar_item", 999);
