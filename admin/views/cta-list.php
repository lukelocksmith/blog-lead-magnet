<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$conditions_labels = array(
    'after_h2_1' => 'Po 1. sekcji H2',
    'after_h2_2' => 'Po 2. sekcji H2',
    'after_h2_3' => 'Po 3. sekcji H2',
    'after_h2_4' => 'Po 4. sekcji H2',
    'after_h2_5' => 'Po 5. sekcji H2',
    'after_30'   => 'Po 30% artykułu',
    'after_50'   => 'Po 50% artykułu',
    'after_70'   => 'Po 70% artykułu',
    'end'        => 'Na końcu artykułu',
);
?>

<div class="blm-cta-list">
    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=add' ) ); ?>" class="button button-primary">
            + Dodaj CTA
        </a>
    </p>

    <?php if ( empty( $ctas ) ) : ?>
        <p>Brak CTA. Dodaj pierwsze!</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Nagłówek</th>
                    <th>Warunek wyświetlania</th>
                    <th style="width:80px">Priorytet</th>
                    <th style="width:80px">Status</th>
                    <th style="width:180px">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $ctas as $cta ) : ?>
                    <tr>
                        <td><?php echo esc_html( $cta->id ); ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=edit&cta_id=' . $cta->id ) ); ?>">
                                    <?php echo esc_html( $cta->heading ?: '(brak nagłówka)' ); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html( $conditions_labels[ $cta->display_condition ] ?? $cta->display_condition ); ?></td>
                        <td><?php echo esc_html( $cta->priority ); ?></td>
                        <td>
                            <?php if ( $cta->is_active ) : ?>
                                <span class="blm-status blm-status--active">Aktywne</span>
                            <?php else : ?>
                                <span class="blm-status blm-status--inactive">Nieaktywne</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=edit&cta_id=' . $cta->id ) ); ?>" class="button button-small">
                                Edytuj
                            </a>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=toggle&cta_id=' . $cta->id ), 'blm_toggle_cta_' . $cta->id ) ); ?>" class="button button-small">
                                <?php echo $cta->is_active ? 'Wyłącz' : 'Włącz'; ?>
                            </a>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=delete&cta_id=' . $cta->id ), 'blm_delete_cta_' . $cta->id ) ); ?>" class="button button-small blm-btn-delete" onclick="return confirm('Na pewno usunąć to CTA?');">
                                Usuń
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
