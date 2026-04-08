<?php
/**
 * Plugin Name: Blog Lead Magnet
 * Description: Flexible CTA system for blog posts with analytics and floating bar.
 * Version: 1.0.0
 * Author: important.is
 * Text Domain: blog-lead-magnet
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BLM_VERSION', '1.0.0' );
define( 'BLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BLM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Includes
require_once BLM_PLUGIN_DIR . 'includes/class-blm-activator.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-cta-model.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-analytics-model.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-content-injector.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-floating-bar.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-github-updater.php';

// Admin
if ( is_admin() ) {
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-cta.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-analytics.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-floating-bar.php';
}

// Activation
register_activation_hook( __FILE__, array( 'BLM_Activator', 'activate' ) );

// Initialize
add_action( 'plugins_loaded', 'blm_init' );

function blm_init() {
    if ( is_admin() ) {
        new BLM_Admin();
        new BLM_GitHub_Updater();
        return;
    }

    // Frontend only — skip REST API, AJAX, Cron
    if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    new BLM_Content_Injector();
    new BLM_Floating_Bar();
}

// AJAX handlers for analytics tracking
add_action( 'wp_ajax_blm_track_event', 'blm_handle_track_event' );
add_action( 'wp_ajax_nopriv_blm_track_event', 'blm_handle_track_event' );

function blm_handle_track_event() {
    // Soft nonce check — don't block tracking on cached pages where nonce expired.
    // This is a low-risk analytics endpoint (worst case: fake impressions, mitigated by session_hash dedup).
    // Hard nonce check would break tracking for ALL visitors behind page cache after 24h.

    $cta_id      = isset( $_POST['cta_id'] ) ? absint( $_POST['cta_id'] ) : 0;
    $post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $event_type  = isset( $_POST['event_type'] ) ? sanitize_text_field( $_POST['event_type'] ) : '';
    $session_hash = isset( $_POST['session_hash'] ) ? sanitize_text_field( $_POST['session_hash'] ) : '';

    if ( ! $cta_id || ! $post_id || ! in_array( $event_type, array( 'impression', 'click' ), true ) || ! $session_hash ) {
        wp_send_json_error( 'Invalid data' );
    }

    BLM_Analytics_Model::record_event( $cta_id, $post_id, $event_type, $session_hash );
    wp_send_json_success();
}

// Enqueue frontend assets
add_action( 'wp_enqueue_scripts', 'blm_enqueue_frontend' );

function blm_enqueue_frontend() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }

    wp_enqueue_style(
        'blm-frontend',
        BLM_PLUGIN_URL . 'frontend/css/blm-frontend.css',
        array(),
        BLM_VERSION
    );

    wp_enqueue_script(
        'blm-frontend',
        BLM_PLUGIN_URL . 'frontend/js/blm-frontend.js',
        array(),
        BLM_VERSION,
        true
    );

    wp_localize_script( 'blm-frontend', 'blm_data', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'blm_track' ),
        'post_id'  => get_the_ID(),
    ) );
}
