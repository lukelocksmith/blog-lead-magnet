<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Activator {

    public static function activate() {
        self::create_tables();
        self::set_defaults();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_ctas = "CREATE TABLE {$wpdb->prefix}blm_ctas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            heading VARCHAR(255) NOT NULL DEFAULT '',
            body LONGTEXT NOT NULL,
            image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            shortcode VARCHAR(500) NOT NULL DEFAULT '',
            button_text VARCHAR(255) NOT NULL DEFAULT '',
            button_url VARCHAR(500) NOT NULL DEFAULT '',
            bg_color VARCHAR(7) NOT NULL DEFAULT '#f0f4ff',
            button_color VARCHAR(7) NOT NULL DEFAULT '#2563eb',
            text_color VARCHAR(7) NOT NULL DEFAULT '#1e293b',
            text_size SMALLINT UNSIGNED NOT NULL DEFAULT 16,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            priority INT NOT NULL DEFAULT 10,
            display_condition VARCHAR(50) NOT NULL DEFAULT 'end',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_active_condition (is_active, display_condition),
            KEY idx_priority (priority)
        ) $charset_collate;";

        $sql_analytics = "CREATE TABLE {$wpdb->prefix}blm_analytics (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cta_id BIGINT UNSIGNED NOT NULL,
            post_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(20) NOT NULL,
            session_hash VARCHAR(64) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cta_event (cta_id, event_type),
            KEY idx_created (created_at),
            KEY idx_cta_event_date (cta_id, event_type, created_at),
            KEY idx_dedup (cta_id, post_id, event_type, session_hash)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_ctas );
        dbDelta( $sql_analytics );

        update_option( 'blm_db_version', BLM_VERSION, false );
    }

    private static function set_defaults() {
        if ( false === get_option( 'blm_floating_bar' ) ) {
            update_option( 'blm_floating_bar', array(
                'enabled'      => 0,
                'heading'      => '',
                'body'         => '',
                'button_text'  => '',
                'button_url'   => '',
                'bg_color'     => '#1e293b',
                'button_color' => '#2563eb',
                'text_color'   => '#ffffff',
                'position'     => 'bottom',
                'show_delay'   => 3,
                'dismissible'  => 1,
            ) );
        }
    }
}
