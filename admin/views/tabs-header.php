<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'cta';
$tabs = array(
    'cta'          => 'CTA',
    'analytics'    => 'Analityka',
    'floating-bar' => 'Pływający pasek',
);
?>
<div class="wrap">
    <h1>Blog Lead Magnet</h1>
    <nav class="nav-tab-wrapper">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=' . $slug ) ); ?>"
               class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="blm-tab-content" style="margin-top: 20px;">
