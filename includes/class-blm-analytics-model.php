<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Analytics_Model {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'blm_analytics';
    }

    public static function record_event( $cta_id, $post_id, $event_type, $session_hash = '' ) {
        global $wpdb;

        // Deduplicate impressions per session
        if ( 'impression' === $event_type && $session_hash ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT 1 FROM " . self::table() . " WHERE cta_id = %d AND post_id = %d AND event_type = 'impression' AND session_hash = %s LIMIT 1",
                $cta_id, $post_id, $session_hash
            ) );
            if ( $exists ) {
                return false;
            }
        }

        return $wpdb->insert( self::table(), array(
            'cta_id'       => absint( $cta_id ),
            'post_id'      => absint( $post_id ),
            'event_type'   => sanitize_text_field( $event_type ),
            'session_hash' => sanitize_text_field( $session_hash ),
        ) );
    }

    public static function get_stats( $days = 30 ) {
        global $wpdb;
        $table = self::table();
        $cta_table = $wpdb->prefix . 'blm_ctas';

        if ( $days > 0 ) {
            $date_filter = $wpdb->prepare( "AND a.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days );
        } else {
            $date_filter = '';
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $date_filter is already prepared above
        return $wpdb->get_results( "
            SELECT
                c.id,
                c.heading,
                c.display_condition,
                c.is_active,
                COALESCE(SUM(CASE WHEN a.event_type = 'impression' THEN 1 ELSE 0 END), 0) AS impressions,
                COALESCE(SUM(CASE WHEN a.event_type = 'click' THEN 1 ELSE 0 END), 0) AS clicks
            FROM $cta_table c
            LEFT JOIN $table a ON a.cta_id = c.id $date_filter
            GROUP BY c.id
            ORDER BY impressions DESC
            LIMIT 100
        " );
    }

    public static function get_stats_for_cta( $cta_id, $days = 30 ) {
        global $wpdb;
        $table = self::table();

        if ( $days > 0 ) {
            return $wpdb->get_row( $wpdb->prepare( "
                SELECT
                    COALESCE(SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END), 0) AS impressions,
                    COALESCE(SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END), 0) AS clicks
                FROM $table
                WHERE cta_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ", $cta_id, $days ) );
        }

        return $wpdb->get_row( $wpdb->prepare( "
            SELECT
                COALESCE(SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END), 0) AS impressions,
                COALESCE(SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END), 0) AS clicks
            FROM $table
            WHERE cta_id = %d
        ", $cta_id ) );
    }

    public static function cleanup_old( $days = 90 ) {
        global $wpdb;

        return $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . self::table() . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY) LIMIT 10000",
            $days
        ) );
    }
}
