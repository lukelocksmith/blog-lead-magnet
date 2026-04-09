<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$conditions_labels = array(
    'after_h2_1' => 'Po 1. sekcji',
    'after_h2_2' => 'Po 2. sekcji',
    'after_h2_3' => 'Po 3. sekcji',
    'after_h2_4' => 'Po 4. sekcji',
    'after_h2_5' => 'Po 5. sekcji',
    'after_30'   => 'Po 30%',
    'after_50'   => 'Po 50%',
    'after_70'   => 'Po 70%',
    'end'        => 'Na końcu',
);

$day_options = array( 7, 30, 90, 0 );
$day_labels  = array( 7 => 'Ostatnie 7 dni', 30 => 'Ostatnie 30 dni', 90 => 'Ostatnie 90 dni', 0 => 'Wszystko' );
?>

<h2>Analityka CTA</h2>

<div class="blm-analytics-filters" style="margin-bottom:15px;">
    <?php foreach ( $day_options as $d ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=analytics&days=' . $d ) ); ?>"
           class="button <?php echo $days === $d ? 'button-primary' : ''; ?>">
            <?php echo esc_html( $day_labels[ $d ] ); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ( empty( $stats ) ) : ?>
    <p>Brak danych. Dodaj CTA, aby zbierać statystyki.</p>
<?php else : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>CTA</th>
                <th>Warunek</th>
                <th>Status</th>
                <th style="width:120px">Wyświetlenia</th>
                <th style="width:120px">Kliknięcia</th>
                <th style="width:100px">CTR</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $stats as $row ) :
                $ctr = $row->impressions > 0 ? round( ( $row->clicks / $row->impressions ) * 100, 1 ) : 0;
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $row->heading ?: '(brak nagłówka)' ); ?></strong></td>
                    <td><?php echo esc_html( $conditions_labels[ $row->display_condition ] ?? $row->display_condition ); ?></td>
                    <td>
                        <?php if ( $row->is_active ) : ?>
                            <span class="blm-status blm-status--active">Aktywne</span>
                        <?php else : ?>
                            <span class="blm-status blm-status--inactive">Nieaktywne</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format_i18n( $row->impressions ); ?></td>
                    <td><?php echo number_format_i18n( $row->clicks ); ?></td>
                    <td><strong><?php echo esc_html( $ctr ); ?>%</strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
