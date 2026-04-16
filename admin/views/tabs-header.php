<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'cta';
$tabs = array(
    'cta'          => 'CTA',
    'analytics'    => 'Analityka',
    'floating-bar' => 'Pływający pasek',
);
?>
<div class="wrap blm-wrap">
    <div class="blm-page-header">
        <h1 class="blm-page-title">Blog Lead Magnet</h1>
        <?php if ( ! empty( $blm_header_action ) ) : ?>
            <div class="blm-page-header-action"><?php echo wp_kses_post( $blm_header_action ); ?></div>
        <?php endif; ?>
    </div>
    <nav class="blm-tabs">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=' . $slug ) ); ?>"
               class="blm-tab <?php echo $current_tab === $slug ? 'is-active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="blm-panel">
