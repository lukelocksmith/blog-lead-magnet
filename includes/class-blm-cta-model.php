<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_CTA_Model {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'blm_ctas';
    }

    public static function get_all( $only_active = false ) {
        global $wpdb;
        $table = self::table();

        if ( $only_active ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table WHERE is_active = %d ORDER BY priority ASC, id ASC",
                1
            ) );
        }

        return $wpdb->get_results( "SELECT * FROM $table ORDER BY priority ASC, id ASC" );
    }

    public static function get_by_id( $id ) {
        global $wpdb;
        $table = self::table();

        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
    }

    public static function get_active_by_condition( $condition ) {
        global $wpdb;
        $table = self::table();

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE is_active = 1 AND display_condition = %s ORDER BY priority ASC, id ASC",
            $condition
        ) );
    }

    public static function create( $data ) {
        global $wpdb;

        $wpdb->insert( self::table(), self::sanitize_data( $data ) );
        self::invalidate_cache();
        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            self::sanitize_data( $data ),
            array( 'id' => absint( $id ) )
        );
        self::invalidate_cache();
        return $result;
    }

    public static function delete( $id ) {
        global $wpdb;
        $id = absint( $id );

        $wpdb->delete( $wpdb->prefix . 'blm_analytics', array( 'cta_id' => $id ) );
        $result = $wpdb->delete( self::table(), array( 'id' => $id ) );
        self::invalidate_cache();
        return $result;
    }

    public static function toggle_active( $id ) {
        global $wpdb;
        $table = self::table();

        $wpdb->query( $wpdb->prepare(
            "UPDATE $table SET is_active = 1 - is_active WHERE id = %d",
            absint( $id )
        ) );
        self::invalidate_cache();
    }

    /**
     * Invalidate transient cache when CTAs change.
     */
    public static function invalidate_cache() {
        delete_transient( 'blm_active_ctas' );
    }

    private static function sanitize_data( $data ) {
        $clean = array();
        $fields = array(
            'heading'           => 'sanitize_text_field',
            'body'              => 'wp_kses_post',
            'image_id'          => 'absint',
            'shortcode'         => 'sanitize_text_field',
            'button_text'       => 'sanitize_text_field',
            'button_url'        => 'esc_url_raw',
            'bg_color'          => 'sanitize_hex_color',
            'button_color'      => 'sanitize_hex_color',
            'text_color'        => 'sanitize_hex_color',
            'text_size'         => 'absint',
            'is_active'         => 'absint',
            'priority'          => 'intval',
            'display_condition' => 'sanitize_text_field',
        );

        foreach ( $fields as $key => $sanitizer ) {
            if ( isset( $data[ $key ] ) ) {
                $clean[ $key ] = call_user_func( $sanitizer, $data[ $key ] );
            }
        }

        return $clean;
    }
}
