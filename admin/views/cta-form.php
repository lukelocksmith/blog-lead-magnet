<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $cta );
$title   = $is_edit ? 'Edytuj CTA' : 'Dodaj nowe CTA';

// Defaults
$defaults = (object) array(
    'id'                => 0,
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
    'priority'          => 10,
    'display_condition' => 'end',
);

$cta = $is_edit ? $cta : $defaults;
$image_url = $cta->image_id ? wp_get_attachment_image_url( $cta->image_id, 'medium' ) : '';
?>

<h2><?php echo esc_html( $title ); ?></h2>

<p>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta' ) ); ?>">&larr; Wróć do listy</a>
</p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=cta' ) ); ?>">
    <?php wp_nonce_field( 'blm_cta_save', 'blm_cta_nonce' ); ?>
    <?php if ( $is_edit ) : ?>
        <input type="hidden" name="cta_id" value="<?php echo esc_attr( $cta->id ); ?>">
    <?php endif; ?>

    <table class="form-table">
        <!-- Active -->
        <tr>
            <th><label for="is_active">Aktywne</label></th>
            <td>
                <label>
                    <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( $cta->is_active, 1 ); ?>>
                    Wyświetlaj to CTA na blogu
                </label>
            </td>
        </tr>

        <!-- Heading -->
        <tr>
            <th><label for="heading">Nagłówek</label></th>
            <td>
                <input type="text" name="heading" id="heading" class="large-text" value="<?php echo esc_attr( $cta->heading ); ?>">
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <th><label>Treść</label></th>
            <td>
                <?php
                wp_editor( $cta->body, 'blm_body', array(
                    'textarea_name' => 'body',
                    'textarea_rows' => 8,
                    'media_buttons' => false,
                    'teeny'         => true,
                ) );
                ?>
            </td>
        </tr>

        <!-- Image -->
        <tr>
            <th><label>Obrazek</label></th>
            <td>
                <div class="blm-image-field">
                    <input type="hidden" name="image_id" id="blm_image_id" value="<?php echo esc_attr( $cta->image_id ); ?>">
                    <div id="blm_image_preview" style="margin-bottom:10px;">
                        <?php if ( $image_url ) : ?>
                            <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:300px;height:auto;">
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button blm-media-upload" data-target="blm_image_id" data-preview="blm_image_preview" data-remove="blm_remove_image" data-mode="id">Wybierz z biblioteki</button>
                    <button type="button" class="button blm-media-remove" data-target="blm_image_id" data-preview="blm_image_preview" data-mode="id" id="blm_remove_image" <?php echo $cta->image_id ? '' : 'style="display:none"'; ?>>Usuń obrazek</button>
                </div>
            </td>
        </tr>

        <!-- Shortcode -->
        <tr>
            <th><label for="shortcode">Shortcode</label></th>
            <td>
                <input type="text" name="shortcode" id="shortcode" class="large-text" value="<?php echo esc_attr( $cta->shortcode ); ?>" placeholder="np. [fluentform id=&quot;5&quot;]">
                <p class="description">Shortcode formularza lub innego elementu. Zostanie wyrenderowany wewnątrz CTA.</p>
            </td>
        </tr>

        <!-- Button -->
        <tr>
            <th><label for="button_text">Przycisk</label></th>
            <td>
                <input type="text" name="button_text" id="button_text" class="regular-text" value="<?php echo esc_attr( $cta->button_text ); ?>" placeholder="Tekst przycisku">
                <input type="url" name="button_url" id="button_url" class="regular-text" value="<?php echo esc_attr( $cta->button_url ); ?>" placeholder="https://..." style="margin-top:5px;display:block;">
            </td>
        </tr>

        <!-- Colors -->
        <tr>
            <th>Kolory</th>
            <td>
                <div class="blm-colors-grid">
                    <div>
                        <label for="bg_color">Tło</label><br>
                        <input type="text" name="bg_color" id="bg_color" class="blm-color-picker" value="<?php echo esc_attr( $cta->bg_color ); ?>">
                    </div>
                    <div>
                        <label for="button_color">Przycisk</label><br>
                        <input type="text" name="button_color" id="button_color" class="blm-color-picker" value="<?php echo esc_attr( $cta->button_color ); ?>">
                    </div>
                    <div>
                        <label for="text_color">Tekst</label><br>
                        <input type="text" name="text_color" id="text_color" class="blm-color-picker" value="<?php echo esc_attr( $cta->text_color ); ?>">
                    </div>
                </div>
            </td>
        </tr>

        <!-- Text Size -->
        <tr>
            <th><label for="text_size">Rozmiar tekstu (px)</label></th>
            <td>
                <input type="number" name="text_size" id="text_size" class="small-text" value="<?php echo esc_attr( $cta->text_size ); ?>" min="10" max="48">
            </td>
        </tr>

        <!-- Display Condition -->
        <tr>
            <th><label for="display_condition">Gdzie wyświetlać</label></th>
            <td>
                <select name="display_condition" id="display_condition">
                    <option value="after_h2_1" <?php selected( $cta->display_condition, 'after_h2_1' ); ?>>Po 1. sekcji (po pierwszym H2 + tekst)</option>
                    <option value="after_h2_2" <?php selected( $cta->display_condition, 'after_h2_2' ); ?>>Po 2. sekcji</option>
                    <option value="after_h2_3" <?php selected( $cta->display_condition, 'after_h2_3' ); ?>>Po 3. sekcji</option>
                    <option value="after_h2_4" <?php selected( $cta->display_condition, 'after_h2_4' ); ?>>Po 4. sekcji</option>
                    <option value="after_h2_5" <?php selected( $cta->display_condition, 'after_h2_5' ); ?>>Po 5. sekcji</option>
                    <option value="after_30" <?php selected( $cta->display_condition, 'after_30' ); ?>>Po 30% artykułu</option>
                    <option value="after_50" <?php selected( $cta->display_condition, 'after_50' ); ?>>Po 50% artykułu</option>
                    <option value="after_70" <?php selected( $cta->display_condition, 'after_70' ); ?>>Po 70% artykułu</option>
                    <option value="end" <?php selected( $cta->display_condition, 'end' ); ?>>Na końcu artykułu</option>
                </select>
                <p class="description">Sekcja = nagłówek H2 + cały tekst pod nim (do kolejnego H2).</p>
            </td>
        </tr>

        <!-- Priority -->
        <tr>
            <th><label for="priority">Priorytet</label></th>
            <td>
                <input type="number" name="priority" id="priority" class="small-text" value="<?php echo esc_attr( $cta->priority ); ?>" min="1" max="999">
                <p class="description">Niższa liczba = wyższy priorytet. Przy konflikcie pozycji wygrywa CTA z niższym priorytetem.</p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <input type="submit" name="blm_cta_save" class="button button-primary" value="<?php echo $is_edit ? 'Zapisz zmiany' : 'Dodaj CTA'; ?>">
    </p>
</form>
