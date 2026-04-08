<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Admin_Floating_Bar {

    public function handle_save() {
        if ( ! isset( $_POST['blm_floating_bar_save'] ) ) {
            return;
        }

        check_admin_referer( 'blm_floating_bar_save', 'blm_fb_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $data = array(
            'enabled'      => isset( $_POST['fb_enabled'] ) ? 1 : 0,
            'heading'      => sanitize_text_field( $_POST['fb_heading'] ?? '' ),
            'body'         => wp_kses_post( $_POST['fb_body'] ?? '' ),
            'button_text'  => sanitize_text_field( $_POST['fb_button_text'] ?? '' ),
            'button_url'   => esc_url_raw( $_POST['fb_button_url'] ?? '' ),
            'bg_color'     => sanitize_hex_color( $_POST['fb_bg_color'] ?? '#1e293b' ),
            'button_color' => sanitize_hex_color( $_POST['fb_button_color'] ?? '#2563eb' ),
            'text_color'   => sanitize_hex_color( $_POST['fb_text_color'] ?? '#ffffff' ),
            'position'     => in_array( $_POST['fb_position'] ?? '', array( 'top', 'bottom' ), true ) ? $_POST['fb_position'] : 'bottom',
            'show_delay'   => absint( $_POST['fb_show_delay'] ?? 3 ),
            'dismissible'  => isset( $_POST['fb_dismissible'] ) ? 1 : 0,
        );

        update_option( 'blm_floating_bar', $data );

        wp_safe_redirect( admin_url( 'admin.php?page=blog-lead-magnet&tab=floating-bar&message=saved' ) );
        exit;
    }

    public function render() {
        $config = get_option( 'blm_floating_bar', array() );

        if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>Ustawienia pływającego paska zostały zapisane.</p></div>';
        }

        include BLM_PLUGIN_DIR . 'admin/views/floating-bar.php';
    }
}
