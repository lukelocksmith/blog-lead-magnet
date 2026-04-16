<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Admin_Floating_Bar {

    public function handle_save() {
        if ( ! isset( $_POST['blm_floating_bar_save'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'blm_floating_bar_save', 'blm_fb_nonce' );

        $modes = array( 'both', 'cta_only', 'toc_only' );

        $data = array(
            'enabled'        => isset( $_POST['fb_enabled'] ) ? 1 : 0,
            'mode'           => in_array( $_POST['fb_mode'] ?? '', $modes, true ) ? $_POST['fb_mode'] : 'both',
            'progress_bar'   => isset( $_POST['fb_progress_bar'] ) ? 1 : 0,
            'btn_text'       => sanitize_text_field( $_POST['fb_btn_text'] ?? '' ),
            'btn_url'        => esc_url_raw( $_POST['fb_btn_url'] ?? '' ),
            'author_name'    => sanitize_text_field( $_POST['fb_author_name'] ?? '' ),
            'author_role'    => sanitize_text_field( $_POST['fb_author_role'] ?? '' ),
            'author_avatar'  => esc_url_raw( $_POST['fb_author_avatar'] ?? '' ),
            'bar_bg'         => sanitize_hex_color( $_POST['fb_bar_bg'] ?? '' ) ?: '#ffffff',
            'btn_color'      => sanitize_hex_color( $_POST['fb_btn_color'] ?? '' ) ?: '#2563eb',
            'progress_color' => sanitize_hex_color( $_POST['fb_progress_color'] ?? '' ) ?: '#e22007',
        );

        update_option( 'blm_floating_bar', $data );

        wp_safe_redirect( admin_url( 'admin.php?page=blog-lead-magnet&tab=floating-bar&message=saved' ) );
        exit;
    }

    public function render() {
        if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>Ustawienia pływającego paska zostały zapisane.</p></div>';
        }

        include BLM_PLUGIN_DIR . 'admin/views/floating-bar.php';
    }
}
