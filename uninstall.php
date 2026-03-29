<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('scc_settings');

global $wpdb;

if (isset($wpdb->options)) {
    $like_transient = $wpdb->esc_like('_transient_scc_cloud_') . '%';
    $like_timeout   = $wpdb->esc_like('_transient_timeout_scc_cloud_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like_transient,
            $like_timeout
        )
    );
}
