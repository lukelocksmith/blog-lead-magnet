<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$conditions_labels = array(
    'after_h2_1' => 'Po 1. H2',
    'after_h2_2' => 'Po 2. H2',
    'after_h2_3' => 'Po 3. H2',
    'after_h2_4' => 'Po 4. H2',
    'after_h2_5' => 'Po 5. H2',
    'after_30'   => 'Po 30%',
    'after_50'   => 'Po 50%',
    'after_70'   => 'Po 70%',
    'end'        => 'Na końcu',
);

$day_options = array( 7, 30, 90, 0 );
$day_labels  = array( 7 => 'Ostatnie 7 dni', 30 => 'Ostatnie 30 dni', 90 => 'Ostatnie 90 dni', 0 => 'Wszystko' );
$analytics_base = admin_url( 'admin.php?page=blog-lead-magnet&tab=analytics' );

// Summary totals
$total_impressions = 0;
$total_clicks      = 0;
foreach ( $stats as $row ) {
    $total_impressions += $row->impressions;
    $total_clicks      += $row->clicks;
}
$total_ctr = $total_impressions > 0 ? round( ( $total_clicks / $total_impressions ) * 100, 1 ) : 0;
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h2 style="margin:0;font-size:16px;font-weight:600;color:var(--blm-text);">Analityka CTA</h2>
    <div class="blm-filter-bar" style="margin:0;">
        <?php foreach ( $day_options as $d ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'days', $d, $analytics_base ) ); ?>"
               class="blm-filter-tag <?php echo $days === $d ? 'is-active' : ''; ?>">
                <?php echo esc_html( $day_labels[ $d ] ); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ( ! empty( $stats ) ) : ?>
<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px;">
    <div class="blm-card">
        <div class="blm-card-body" style="padding:16px 20px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--blm-text-subtle);">Wyświetlenia</p>
            <p style="margin:0;font-size:28px;font-weight:700;color:var(--blm-text);line-height:1.2;"><?php echo number_format_i18n( $total_impressions ); ?></p>
        </div>
    </div>
    <div class="blm-card">
        <div class="blm-card-body" style="padding:16px 20px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--blm-text-subtle);">Kliknięcia</p>
            <p style="margin:0;font-size:28px;font-weight:700;color:var(--blm-text);line-height:1.2;"><?php echo number_format_i18n( $total_clicks ); ?></p>
        </div>
    </div>
    <div class="blm-card">
        <div class="blm-card-body" style="padding:16px 20px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--blm-text-subtle);">Śr. CTR</p>
            <p style="margin:0;font-size:28px;font-weight:700;color:var(--blm-text);line-height:1.2;"><?php echo esc_html( $total_ctr ); ?>%</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ( empty( $stats ) ) : ?>
    <div class="blm-empty">
        <p class="blm-empty-title">Brak danych</p>
        <p class="blm-empty-desc">Aktywuj CTA na blogu, aby zacząć zbierać statystyki.</p>
    </div>
<?php else : ?>
    <div class="blm-table-wrap">
        <table class="blm-table">
            <thead>
                <tr>
                    <th>CTA</th>
                    <th style="width:80px">Typ</th>
                    <th style="width:90px">Gdzie</th>
                    <th style="width:80px">Status</th>
                    <th style="width:110px">Wyświetlenia</th>
                    <th style="width:110px">Kliknięcia</th>
                    <th style="width:80px">CTR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $stats as $row ) :
                    $ctr      = $row->impressions > 0 ? round( ( $row->clicks / $row->impressions ) * 100, 1 ) : 0;
                    $cta_type = isset( $row->type ) ? $row->type : 'cta';
                    $bar_pct  = min( 100, $ctr * 4 ); // visual bar scale (25% CTR = full bar)
                ?>
                <tr data-cta-id="<?php echo esc_attr( $row->id ); ?>" style="cursor:pointer;">
                    <td class="blm-row-title">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <button type="button" class="blm-row-expand" data-cta-id="<?php echo esc_attr( $row->id ); ?>" data-days="<?php echo esc_attr( $days ); ?>" aria-expanded="false" style="background:none;border:none;padding:0;cursor:pointer;display:flex;align-items:center;color:var(--blm-text-muted);flex-shrink:0;">
                                <svg class="blm-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;transition:transform .2s;" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=edit&cta_id=' . $row->id ) ); ?>" onclick="event.stopPropagation()">
                                <?php echo esc_html( $row->heading ?: '(brak nagłówka)' ); ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <?php if ( 'gate' === $cta_type ) : ?>
                            <span class="blm-badge blm-badge-yellow">Gate</span>
                        <?php else : ?>
                            <span class="blm-badge blm-badge-blue">CTA</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--blm-text-muted);">
                        <?php echo esc_html( $conditions_labels[ $row->display_condition ] ?? $row->display_condition ); ?>
                    </td>
                    <td>
                        <?php if ( $row->is_active ) : ?>
                            <span class="blm-badge blm-badge-green">Aktywne</span>
                        <?php else : ?>
                            <span class="blm-badge blm-badge-red">Wyłączone</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;font-weight:500;"><?php echo number_format_i18n( $row->impressions ); ?></td>
                    <td style="font-size:13px;font-weight:500;"><?php echo number_format_i18n( $row->clicks ); ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:13px;font-weight:600;color:var(--blm-text);min-width:36px;"><?php echo esc_html( $ctr ); ?>%</span>
                            <div style="flex:1;height:4px;background:var(--blm-bg-subtle);border-radius:2px;overflow:hidden;min-width:40px;">
                                <div style="height:100%;width:<?php echo esc_attr( $bar_pct ); ?>%;background:var(--blm-primary);border-radius:2px;transition:width .3s;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
