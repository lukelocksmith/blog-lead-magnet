<?php
/**
 * Plugin Name: Blog Lead Magnet
 * Description: Flexible CTA system for blog posts with analytics and floating bar.
 * Version: 1.1.1
 * Author: important.is
 * Text Domain: blog-lead-magnet
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BLM_VERSION', '1.1.1' );
define( 'BLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BLM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Includes
require_once BLM_PLUGIN_DIR . 'includes/class-blm-activator.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-cta-model.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-analytics-model.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-content-injector.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-floating-bar.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-shortcodes.php';
require_once BLM_PLUGIN_DIR . 'includes/class-blm-github-updater.php';

// Admin
if ( is_admin() ) {
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-cta.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-analytics.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-floating-bar.php';
    require_once BLM_PLUGIN_DIR . 'admin/class-blm-admin-post-meta.php';
}

// Activation / deactivation
register_activation_hook( __FILE__, array( 'BLM_Activator', 'activate' ) );
register_activation_hook( __FILE__, 'blm_schedule_cleanup' );
register_deactivation_hook( __FILE__, 'blm_unschedule_cleanup' );

function blm_schedule_cleanup() {
    if ( ! wp_next_scheduled( 'blm_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'blm_daily_cleanup' );
    }
}

function blm_unschedule_cleanup() {
    wp_clear_scheduled_hook( 'blm_daily_cleanup' );
}

add_action( 'blm_daily_cleanup', array( 'BLM_Analytics_Model', 'cleanup_old' ) );

// Run DB upgrade on every load (cheap SHOW COLUMNS check)
add_action( 'plugins_loaded', array( 'BLM_Activator', 'maybe_upgrade' ) );

// Initialize
add_action( 'plugins_loaded', 'blm_init', 20 );

function blm_init() {
    // Skip AJAX, Cron, and REST entirely — only our own AJAX actions handle those
    if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    if ( is_admin() ) {
        new BLM_Admin();
        new BLM_GitHub_Updater();
        new BLM_Admin_Post_Meta(); // registers hooks only on post.php / post-new.php via its own checks
        return;
    }

    // Pure frontend page requests only
    new BLM_Content_Injector();
    new BLM_Floating_Bar();
    new BLM_Shortcodes();
}

// AJAX handlers for analytics tracking
add_action( 'wp_ajax_blm_track_event',        'blm_handle_track_event' );
add_action( 'wp_ajax_nopriv_blm_track_event', 'blm_handle_track_event' );

// AJAX handler: per-post stats for a CTA (admin only)
add_action( 'wp_ajax_blm_cta_post_stats', 'blm_handle_cta_post_stats' );

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

    // Rate-limit: max 30 events per IP per minute to prevent analytics flooding
    $ip_key = 'blm_rl_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
    $hits   = (int) get_transient( $ip_key );
    if ( $hits >= 30 ) {
        wp_send_json_error( 'Rate limit exceeded' );
    }
    set_transient( $ip_key, $hits + 1, MINUTE_IN_SECONDS );

    BLM_Analytics_Model::record_event( $cta_id, $post_id, $event_type, $session_hash );
    wp_send_json_success();
}

function blm_handle_cta_post_stats() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'blm_admin_ajax' ) ) {
        wp_send_json_error( 'Bad nonce' );
    }

    $cta_id = isset( $_GET['cta_id'] ) ? absint( $_GET['cta_id'] ) : 0;
    $days   = isset( $_GET['days'] )   ? absint( $_GET['days'] )   : 30;

    if ( ! $cta_id ) {
        wp_send_json_error( 'No CTA ID' );
    }

    $rows = BLM_Analytics_Model::get_post_stats_for_cta( $cta_id, $days );

    // Prime post cache in one IN(...) query — prevents N+1 individual get_post() calls
    $post_ids = array_map( 'absint', wp_list_pluck( $rows, 'post_id' ) );
    if ( ! empty( $post_ids ) ) {
        _prime_post_caches( $post_ids, false, false );
    }

    $data = array();
    foreach ( $rows as $row ) {
        $post   = get_post( absint( $row->post_id ) );
        $data[] = array(
            'post_id'     => (int) $row->post_id,
            'title'       => $post ? $post->post_title : '(usunięty post #' . $row->post_id . ')',
            'url'         => $post ? get_permalink( $post->ID ) : '',
            'impressions' => (int) $row->impressions,
            'clicks'      => (int) $row->clicks,
        );
    }

    wp_send_json_success( $data );
}

// Enqueue frontend assets
add_action( 'wp_enqueue_scripts', 'blm_enqueue_frontend' );

function blm_enqueue_frontend() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }

    // Warm the CTA transient here — avoids a second identical query later in BLM_Content_Injector
    $ctas = get_transient( 'blm_active_ctas' );
    if ( false === $ctas ) {
        $ctas = BLM_CTA_Model::get_all( true );
        set_transient( 'blm_active_ctas', $ctas, HOUR_IN_SECONDS );
    }
    $has_ctas = ! empty( $ctas );
    $bar      = BLM_Floating_Bar::get();
    if ( ! $has_ctas && empty( $bar['enabled'] ) ) {
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
