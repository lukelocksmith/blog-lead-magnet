<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Admin_CTA {

    public function handle_actions() {
        // Handle save
        if ( isset( $_POST['blm_cta_save'] ) ) {
            check_admin_referer( 'blm_cta_save', 'blm_cta_nonce' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Unauthorized' );
            }

            $data = array(
                'heading'           => $_POST['heading'] ?? '',
                'body'              => $_POST['body'] ?? '',
                'image_id'          => $_POST['image_id'] ?? 0,
                'shortcode'         => $_POST['shortcode'] ?? '',
                'button_text'       => $_POST['button_text'] ?? '',
                'button_url'        => $_POST['button_url'] ?? '',
                'bg_color'          => $_POST['bg_color'] ?? '#f0f4ff',
                'button_color'      => $_POST['button_color'] ?? '#2563eb',
                'text_color'        => $_POST['text_color'] ?? '#1e293b',
                'text_size'         => $_POST['text_size'] ?? 16,
                'is_active'         => isset( $_POST['is_active'] ) ? 1 : 0,
                'priority'          => $_POST['priority'] ?? 10,
                'display_condition' => $_POST['display_condition'] ?? 'end',
            );

            $id = isset( $_POST['cta_id'] ) ? absint( $_POST['cta_id'] ) : 0;

            if ( $id ) {
                BLM_CTA_Model::update( $id, $data );
                $message = 'updated';
            } else {
                BLM_CTA_Model::create( $data );
                $message = 'created';
            }

            wp_safe_redirect( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&message=' . $message ) );
            exit;
        }

        // Handle delete
        if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['cta_id'] ) ) {
            check_admin_referer( 'blm_delete_cta_' . $_GET['cta_id'] );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Unauthorized' );
            }

            BLM_CTA_Model::delete( absint( $_GET['cta_id'] ) );

            wp_safe_redirect( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&message=deleted' ) );
            exit;
        }

        // Handle toggle
        if ( isset( $_GET['action'] ) && 'toggle' === $_GET['action'] && isset( $_GET['cta_id'] ) ) {
            check_admin_referer( 'blm_toggle_cta_' . $_GET['cta_id'] );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Unauthorized' );
            }

            BLM_CTA_Model::toggle_active( absint( $_GET['cta_id'] ) );

            wp_safe_redirect( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&message=toggled' ) );
            exit;
        }
    }

    public function render() {
        // Show messages
        if ( isset( $_GET['message'] ) ) {
            $messages = array(
                'created' => 'CTA zostało dodane.',
                'updated' => 'CTA zostało zaktualizowane.',
                'deleted' => 'CTA zostało usunięte.',
                'toggled' => 'Status CTA został zmieniony.',
            );
            $msg = sanitize_text_field( $_GET['message'] );
            if ( isset( $messages[ $msg ] ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $msg ] ) . '</p></div>';
            }
        }

        // Edit form or list
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'add', 'edit' ), true ) ) {
            $cta = null;
            if ( 'edit' === $_GET['action'] && isset( $_GET['cta_id'] ) ) {
                $cta = BLM_CTA_Model::get_by_id( absint( $_GET['cta_id'] ) );
            }
            include BLM_PLUGIN_DIR . 'admin/views/cta-form.php';
        } else {
            $ctas = BLM_CTA_Model::get_all();
            include BLM_PLUGIN_DIR . 'admin/views/cta-list.php';
        }
    }
}
