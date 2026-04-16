<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Activator {

    public static function activate() {
        self::create_tables();
        self::maybe_upgrade();
        self::set_defaults();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_ctas = "CREATE TABLE {$wpdb->prefix}blm_ctas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(10) NOT NULL DEFAULT 'cta',
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
            is_bare TINYINT(1) NOT NULL DEFAULT 0,
            priority INT NOT NULL DEFAULT 10,
            display_condition VARCHAR(50) NOT NULL DEFAULT 'end',
            category_filter VARCHAR(500) NOT NULL DEFAULT '',
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

    /**
     * Add new columns to existing tables when upgrading.
     * dbDelta only creates tables, it doesn't add missing columns.
     */
    public static function maybe_upgrade() {
        // Gate behind a version option — avoids SHOW COLUMNS on every request.
        // Only runs when the stored DB version doesn't match the current plugin version.
        if ( get_option( 'blm_db_version' ) === BLM_VERSION ) {
            return;
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'blm_ctas';
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $table", 0 );

        if ( ! in_array( 'type', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN type VARCHAR(10) NOT NULL DEFAULT 'cta' AFTER id" );
        }

        if ( ! in_array( 'category_filter', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN category_filter VARCHAR(500) NOT NULL DEFAULT '' AFTER display_condition" );
        }

        update_option( 'blm_db_version', BLM_VERSION );
    }

    private static function set_defaults() {
        if ( false === get_option( 'blm_floating_bar' ) ) {
            update_option( 'blm_floating_bar', BLM_Floating_Bar::defaults(), false );
        }
    }
}
