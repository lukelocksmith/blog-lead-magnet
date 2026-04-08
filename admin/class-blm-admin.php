<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Handle form actions early (admin_init fires BEFORE render_page)
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
    }

    /**
     * Process form submissions on admin_init (before page render).
     * Only runs on our plugin page.
     */
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || 'blog-lead-magnet' !== $_GET['page'] ) {
            return;
        }

        // Delegate to appropriate handler
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'cta';

        switch ( $tab ) {
            case 'cta':
                $handler = new BLM_Admin_CTA();
                $handler->handle_actions();
                break;
            case 'floating-bar':
                $handler = new BLM_Admin_Floating_Bar();
                $handler->handle_save();
                break;
        }
    }

    public function add_menu() {
        add_menu_page(
            'Blog Lead Magnet',
            'Blog Lead Magnet',
            'manage_options',
            'blog-lead-magnet',
            array( $this, 'render_page' ),
            'dashicons-megaphone',
            30
        );
    }

    public function render_page() {
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'cta';

        include BLM_PLUGIN_DIR . 'admin/views/tabs-header.php';

        switch ( $tab ) {
            case 'analytics':
                $handler = new BLM_Admin_Analytics();
                $handler->render();
                break;
            case 'floating-bar':
                $handler = new BLM_Admin_Floating_Bar();
                $handler->render();
                break;
            default:
                $handler = new BLM_Admin_CTA();
                $handler->render();
                break;
        }

        echo '</div>';
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_blog-lead-magnet' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_style(
            'blm-admin',
            BLM_PLUGIN_URL . 'admin/css/blm-admin.css',
            array(),
            BLM_VERSION
        );
        wp_enqueue_script(
            'blm-admin',
            BLM_PLUGIN_URL . 'admin/js/blm-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            BLM_VERSION,
            true
        );
    }
}
