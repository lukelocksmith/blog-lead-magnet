<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $cta );
$title   = $is_edit ? 'Edytuj CTA' : 'Dodaj nowe CTA';

$defaults = (object) array(
    'id'                => 0,
    'type'              => 'cta',
    'heading'           => '',
    'body'              => '',
    'image_id'          => 0,
    'shortcode'         => '',
    'button_text'       => '',
    'button_url'        => '',
    'bg_color'          => '#f0f4ff',
    'button_color'      => '#2563eb',
    'text_color'        => '#1e293b',
    'text_size'         => 16,
    'is_active'         => 1,
    'is_bare'           => 0,
    'priority'          => 10,
    'display_condition' => 'end',
    'category_filter'   => '',
);

$cta             = $is_edit ? $cta : $defaults;
$image_url       = $cta->image_id ? wp_get_attachment_image_url( $cta->image_id, 'medium' ) : '';
$cta_type        = isset( $cta->type ) ? $cta->type : 'cta';
$category_filter = isset( $cta->category_filter ) ? $cta->category_filter : '';
$selected_cats   = array_filter( array_map( 'trim', explode( ',', $category_filter ) ) );
$all_categories  = get_categories( array( 'hide_empty' => false, 'orderby' => 'name' ) );
?>

<a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta' ) ); ?>" class="blm-back-link">
    ← Wróć do listy
</a>

<div class="blm-card">
    <div class="blm-card-header">
        <h2 class="blm-card-title"><?php echo esc_html( $title ); ?></h2>
        <?php if ( $is_edit ) : ?>
            <span class="blm-badge <?php echo 'gate' === $cta_type ? 'blm-badge-yellow' : 'blm-badge-blue'; ?>">
                <?php echo 'gate' === $cta_type ? 'Gate' : 'CTA'; ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="blm-card-body">

<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta' ) ); ?>">
    <?php wp_nonce_field( 'blm_cta_save', 'blm_cta_nonce' ); ?>
    <?php if ( $is_edit ) : ?>
        <input type="hidden" name="cta_id" value="<?php echo esc_attr( $cta->id ); ?>">
    <?php endif; ?>

    <!-- ── Ogólne ──────────────────────────────────────────── -->
    <p class="blm-section-title" style="margin-top:0;">Ogólne</p>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="type">Typ</label>
            <p class="blm-description">CTA wstrzykuje blok. Gate ucina treść i pokazuje formularz zapisu.</p>
        </div>
        <div>
            <select name="type" id="type" class="blm-select" style="max-width:360px;">
                <option value="cta"  <?php selected( $cta_type, 'cta' ); ?>>CTA — blok w treści</option>
                <option value="gate" <?php selected( $cta_type, 'gate' ); ?>>Content Gate — bramka emailowa</option>
            </select>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="is_active">Status</label>
        </div>
        <div>
            <label class="blm-checkbox-row">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( $cta->is_active, 1 ); ?>>
                <span class="blm-checkbox-label">Aktywne — wyświetlaj na blogu</span>
            </label>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="is_bare">Bez opakowania</label>
            <p class="blm-description">Brak tła, paddingu i ramki. Shortcode na 100% szerokości.</p>
        </div>
        <div>
            <label class="blm-checkbox-row">
                <input type="checkbox" name="is_bare" id="is_bare" value="1" <?php checked( ! empty( $cta->is_bare ), 1 ); ?>>
                <span class="blm-checkbox-label">Bez opakowania (bare mode)</span>
            </label>
        </div>
    </div>

    <!-- ── Treść ───────────────────────────────────────────── -->
    <p class="blm-section-title">Treść</p>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="heading">Nagłówek</label>
        </div>
        <div>
            <input type="text" name="heading" id="heading" class="blm-input" value="<?php echo esc_attr( $cta->heading ); ?>" placeholder="np. Chcesz dowiedzieć się więcej?">
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label">Treść</label>
        </div>
        <div>
            <?php wp_editor( $cta->body, 'blm_body', array(
                'textarea_name' => 'body',
                'textarea_rows' => 6,
                'media_buttons' => false,
                'teeny'         => true,
            ) ); ?>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label">Obrazek</label>
        </div>
        <div>
            <div id="blm_image_preview" style="margin-bottom:10px;">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:240px;height:auto;">
                <?php endif; ?>
            </div>
            <input type="hidden" name="image_id" id="blm_image_id" value="<?php echo esc_attr( $cta->image_id ); ?>">
            <button type="button" class="blm-btn blm-btn-secondary blm-media-upload" data-target="blm_image_id" data-preview="blm_image_preview" data-remove="blm_remove_image" data-mode="id">Wybierz z biblioteki</button>
            <button type="button" class="blm-btn blm-btn-ghost blm-media-remove" data-target="blm_image_id" data-preview="blm_image_preview" data-mode="id" id="blm_remove_image" <?php echo $cta->image_id ? '' : 'style="display:none"'; ?>>Usuń</button>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="shortcode">Shortcode</label>
            <p class="blm-description">Formularz lub inny element wewnątrz CTA.</p>
        </div>
        <div>
            <input type="text" name="shortcode" id="shortcode" class="blm-input" value="<?php echo esc_attr( $cta->shortcode ); ?>" placeholder='[fluentform id="5"]'>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="button_text">Przycisk</label>
        </div>
        <div style="display:grid;gap:8px;">
            <input type="text" name="button_text" id="button_text" class="blm-input" value="<?php echo esc_attr( $cta->button_text ); ?>" placeholder="Tekst przycisku">
            <input type="url" name="button_url" id="button_url" class="blm-input" value="<?php echo esc_attr( $cta->button_url ); ?>" placeholder="https://...">
        </div>
    </div>

    <!-- ── Wygląd ──────────────────────────────────────────── -->
    <p class="blm-section-title">Wygląd</p>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label">Kolory</label>
        </div>
        <div class="blm-colors-grid">
            <div>
                <label class="blm-label" for="bg_color">Tło</label>
                <input type="text" name="bg_color" id="bg_color" class="blm-color-picker" value="<?php echo esc_attr( $cta->bg_color ); ?>">
            </div>
            <div>
                <label class="blm-label" for="button_color">Przycisk</label>
                <input type="text" name="button_color" id="button_color" class="blm-color-picker" value="<?php echo esc_attr( $cta->button_color ); ?>">
            </div>
            <div>
                <label class="blm-label" for="text_color">Tekst</label>
                <input type="text" name="text_color" id="text_color" class="blm-color-picker" value="<?php echo esc_attr( $cta->text_color ); ?>">
            </div>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="text_size">Rozmiar tekstu</label>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <input type="number" name="text_size" id="text_size" class="blm-input blm-input-small" value="<?php echo esc_attr( $cta->text_size ); ?>" min="10" max="48">
            <span style="font-size:12px;color:var(--blm-text-muted);">px</span>
        </div>
    </div>

    <!-- ── Podgląd ───────────────────────────────────────────── -->
    <p class="blm-section-title">Podgląd</p>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label">Wygląd bloku</label>
            <p class="blm-description">Aktualizuje się na żywo przy zmianie kolorów i tekstu.</p>
        </div>
        <div>
            <div id="blm-cta-preview" style="padding:20px 24px;border-radius:8px;background:<?php echo esc_attr( $cta->bg_color ); ?>;color:<?php echo esc_attr( $cta->text_color ); ?>;font-size:<?php echo intval( $cta->text_size ); ?>px;max-width:520px;box-shadow:0 1px 4px rgba(0,0,0,.08);">
                <div id="blm-preview-heading" style="font-size:1.15em;font-weight:700;margin:0 0 10px;line-height:1.3;"><?php echo esc_html( $cta->heading ?: '(brak nagłówka)' ); ?></div>
                <div id="blm-preview-body" style="font-size:.9em;opacity:.85;margin-bottom:14px;line-height:1.5;"><?php echo esc_html( wp_strip_all_tags( $cta->body ) ?: 'Treść pojawi się tutaj.' ); ?></div>
                <?php if ( $cta->button_text || $cta->button_url ) : ?>
                <a id="blm-preview-btn" href="#" onclick="return false" style="display:inline-block;padding:9px 20px;border-radius:6px;background:<?php echo esc_attr( $cta->button_color ); ?>;color:#fff;text-decoration:none;font-weight:600;font-size:.88em;"><?php echo esc_html( $cta->button_text ?: '(przycisk)' ); ?></a>
                <?php else : ?>
                <a id="blm-preview-btn" href="#" onclick="return false" style="display:inline-block;padding:9px 20px;border-radius:6px;background:<?php echo esc_attr( $cta->button_color ); ?>;color:#fff;text-decoration:none;font-weight:600;font-size:.88em;">(przycisk)</a>
                <?php endif; ?>
            </div>
            <p style="margin:6px 0 0;font-size:11px;color:var(--blm-text-subtle);">Treść WYSIWYG i shortcode nie są podglądane na żywo.</p>
        </div>
    </div>

    <!-- ── Wyświetlanie ────────────────────────────────────── -->
    <p class="blm-section-title">Wyświetlanie</p>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="display_condition">Gdzie</label>
            <p class="blm-description">Dla Gate: punkt ucięcia treści.</p>
        </div>
        <div>
            <select name="display_condition" id="display_condition" class="blm-select" style="max-width:280px;">
                <option value="after_h2_1" <?php selected( $cta->display_condition, 'after_h2_1' ); ?>>Po 1. sekcji H2</option>
                <option value="after_h2_2" <?php selected( $cta->display_condition, 'after_h2_2' ); ?>>Po 2. sekcji H2</option>
                <option value="after_h2_3" <?php selected( $cta->display_condition, 'after_h2_3' ); ?>>Po 3. sekcji H2</option>
                <option value="after_h2_4" <?php selected( $cta->display_condition, 'after_h2_4' ); ?>>Po 4. sekcji H2</option>
                <option value="after_h2_5" <?php selected( $cta->display_condition, 'after_h2_5' ); ?>>Po 5. sekcji H2</option>
                <option value="after_30"   <?php selected( $cta->display_condition, 'after_30' ); ?>>Po 30% artykułu</option>
                <option value="after_50"   <?php selected( $cta->display_condition, 'after_50' ); ?>>Po 50% artykułu</option>
                <option value="after_70"   <?php selected( $cta->display_condition, 'after_70' ); ?>>Po 70% artykułu</option>
                <option value="end"        <?php selected( $cta->display_condition, 'end' ); ?>>Na końcu artykułu</option>
            </select>
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label" for="priority">Priorytet</label>
            <p class="blm-description">Niższa liczba = wyższy priorytet.</p>
        </div>
        <div>
            <input type="number" name="priority" id="priority" class="blm-input blm-input-small" value="<?php echo esc_attr( $cta->priority ); ?>" min="1" max="999">
        </div>
    </div>

    <div class="blm-field">
        <div class="blm-field-label">
            <label class="blm-label">Kategorie</label>
            <p class="blm-description">Brak zaznaczenia = wyświetlaj wszędzie.</p>
        </div>
        <div>
            <?php if ( ! empty( $all_categories ) ) : ?>
                <div class="blm-cat-list">
                    <?php foreach ( $all_categories as $cat ) : ?>
                        <label>
                            <input type="checkbox"
                                name="category_filter[]"
                                value="<?php echo esc_attr( $cat->slug ); ?>"
                                <?php checked( in_array( $cat->slug, $selected_cats, true ) ); ?>>
                            <?php echo esc_html( $cat->name ); ?>
                            <span class="blm-cat-slug">(<?php echo esc_html( $cat->slug ); ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="blm-description">Brak kategorii — CTA wyświetli się wszędzie.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="blm-form-footer">
        <input type="submit" name="blm_cta_save" class="blm-btn blm-btn-primary"
               value="<?php echo $is_edit ? 'Zapisz zmiany' : 'Dodaj CTA'; ?>">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta' ) ); ?>" class="blm-btn blm-btn-ghost">Anuluj</a>
    </div>

</form>

    </div><!-- .blm-card-body -->
</div><!-- .blm-card -->
