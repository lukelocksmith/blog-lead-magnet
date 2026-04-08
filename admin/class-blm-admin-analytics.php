<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Admin_Analytics {

    public function render() {
        $days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
        $stats = BLM_Analytics_Model::get_stats( $days );

        include BLM_PLUGIN_DIR . 'admin/views/analytics.php';
    }
}
