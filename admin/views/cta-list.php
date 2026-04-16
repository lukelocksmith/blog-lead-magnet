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

$filter_cat = isset( $_GET['filter_cat'] ) ? sanitize_text_field( $_GET['filter_cat'] ) : '';

// Collect unique category slugs across all CTAs, then resolve names in one query
$used_cats = array();
$all_slugs = array();
foreach ( $ctas as $cta ) {
    $f = isset( $cta->category_filter ) ? trim( $cta->category_filter ) : '';
    if ( '' !== $f ) {
        foreach ( array_filter( array_map( 'trim', explode( ',', $f ) ) ) as $slug ) {
            $all_slugs[ $slug ] = true;
        }
    }
}
if ( ! empty( $all_slugs ) ) {
    $terms = get_terms( array(
        'taxonomy'   => 'category',
        'slug'       => array_keys( $all_slugs ),
        'hide_empty' => false,
    ) );
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $used_cats[ $term->slug ] = $term->name;
        }
    }
    // Preserve slugs that didn't match any known term
    foreach ( array_keys( $all_slugs ) as $slug ) {
        if ( ! isset( $used_cats[ $slug ] ) ) {
            $used_cats[ $slug ] = $slug;
        }
    }
}

// Apply filter
$filtered_ctas = $ctas;
if ( '' !== $filter_cat ) {
    $filtered_ctas = array_filter( $ctas, function( $cta ) use ( $filter_cat ) {
        $f = isset( $cta->category_filter ) ? trim( $cta->category_filter ) : '';
        if ( '__global' === $filter_cat ) return '' === $f;
        return in_array( $filter_cat, array_filter( array_map( 'trim', explode( ',', $f ) ) ), true );
    } );
}

$list_base = admin_url( 'admin.php?page=blog-lead-magnet&tab=cta' );
?>

<?php if ( ! empty( $used_cats ) ) : ?>
<div class="blm-filter-bar">
    <span class="blm-filter-label">Filtruj:</span>
    <a href="<?php echo esc_url( $list_base ); ?>" class="blm-filter-tag <?php echo '' === $filter_cat ? 'is-active' : ''; ?>">Wszystkie</a>
    <a href="<?php echo esc_url( add_query_arg( 'filter_cat', '__global', $list_base ) ); ?>" class="blm-filter-tag <?php echo '__global' === $filter_cat ? 'is-active' : ''; ?>">Globalne</a>
    <?php foreach ( $used_cats as $slug => $name ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'filter_cat', $slug, $list_base ) ); ?>"
           class="blm-filter-tag <?php echo $filter_cat === $slug ? 'is-active' : ''; ?>">
            <?php echo esc_html( $name ); ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ( empty( $filtered_ctas ) ) : ?>
    <div class="blm-empty">
        <p class="blm-empty-title"><?php echo '' !== $filter_cat ? 'Brak CTA dla wybranego filtru' : 'Brak CTA'; ?></p>
        <p class="blm-empty-desc"><?php echo '' !== $filter_cat ? 'Zmień filtr lub dodaj nowe CTA.' : 'Dodaj pierwsze CTA żeby zacząć.'; ?></p>
        <?php if ( '' === $filter_cat ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=add' ) ); ?>" class="blm-btn blm-btn-primary">+ Dodaj CTA</a>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="blm-table-wrap">
        <table class="blm-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th style="width:56px">Typ</th>
                    <th>Nazwa</th>
                    <th style="width:90px">Gdzie</th>
                    <th>Kategorie</th>
                    <th style="width:54px">Prior.</th>
                    <th style="width:88px">Status</th>
                    <th style="width:168px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $filtered_ctas as $cta ) :
                    $cta_type   = isset( $cta->type ) ? $cta->type : 'cta';
                    $cat_filter = isset( $cta->category_filter ) ? trim( $cta->category_filter ) : '';
                    $cat_slugs  = $cat_filter !== '' ? array_filter( array_map( 'trim', explode( ',', $cat_filter ) ) ) : array();
                    $edit_url   = admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=edit&cta_id=' . $cta->id );
                ?>
                <tr>
                    <td style="color:var(--blm-text-subtle);font-size:12px;"><?php echo esc_html( $cta->id ); ?></td>
                    <td>
                        <?php if ( 'gate' === $cta_type ) : ?>
                            <span class="blm-badge blm-badge-yellow">Gate</span>
                        <?php else : ?>
                            <span class="blm-badge blm-badge-blue">CTA</span>
                        <?php endif; ?>
                    </td>
                    <td class="blm-row-title">
                        <a href="<?php echo esc_url( $edit_url ); ?>">
                            <?php echo esc_html( $cta->heading ?: '(brak nagłówka)' ); ?>
                        </a>
                    </td>
                    <td style="font-size:12px;color:var(--blm-text-muted);">
                        <?php echo esc_html( $conditions_labels[ $cta->display_condition ] ?? $cta->display_condition ); ?>
                    </td>
                    <td>
                        <?php if ( empty( $cat_slugs ) ) : ?>
                            <span class="blm-badge blm-badge-slate">Globalne</span>
                        <?php else : ?>
                            <?php foreach ( $cat_slugs as $slug ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'filter_cat', $slug, $list_base ) ); ?>" class="blm-chip">
                                    <?php echo esc_html( $used_cats[ $slug ] ?? $slug ); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--blm-text-muted);"><?php echo esc_html( $cta->priority ); ?></td>
                    <td>
                        <?php if ( $cta->is_active ) : ?>
                            <span class="blm-badge blm-badge-green">Aktywne</span>
                        <?php else : ?>
                            <span class="blm-badge blm-badge-red">Wyłączone</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="blm-row-actions">
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="blm-btn blm-btn-secondary blm-btn-sm">Edytuj</a>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=toggle&cta_id=' . $cta->id ), 'blm_toggle_cta_' . $cta->id ) ); ?>" class="blm-btn blm-btn-ghost blm-btn-sm">
                                <?php echo $cta->is_active ? 'Wyłącz' : 'Włącz'; ?>
                            </a>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=delete&cta_id=' . $cta->id ), 'blm_delete_cta_' . $cta->id ) ); ?>"
                               class="blm-btn blm-btn-destructive blm-btn-sm"
                               onclick="return confirm('Na pewno usunąć to CTA?');">Usuń</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
