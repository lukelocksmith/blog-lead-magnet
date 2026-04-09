<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$c = BLM_Floating_Bar::get();
?>

<h2>Pływający pasek</h2>
<p class="description">Pasek na dole ekranu z przyciskiem CTA i/lub spisem treści + pasek postępu na górze — widoczny na artykułach.</p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=floating-bar' ) ); ?>">
    <?php wp_nonce_field( 'blm_floating_bar_save', 'blm_fb_nonce' ); ?>

    <table class="form-table">
        <tr>
            <th><label for="fb_enabled">Włączony</label></th>
            <td>
                <label>
                    <input type="checkbox" name="fb_enabled" id="fb_enabled" value="1" <?php checked( $c['enabled'], 1 ); ?>>
                    Wyświetlaj pływający pasek na postach
                </label>
            </td>
        </tr>
        <tr>
            <th><label for="fb_mode">Tryb wyświetlania</label></th>
            <td>
                <select name="fb_mode" id="fb_mode">
                    <option value="both" <?php selected( $c['mode'], 'both' ); ?>>Przycisk CTA + Spis treści</option>
                    <option value="cta_only" <?php selected( $c['mode'], 'cta_only' ); ?>>Tylko przycisk CTA</option>
                    <option value="toc_only" <?php selected( $c['mode'], 'toc_only' ); ?>>Tylko Spis treści</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="fb_progress_bar">Pasek postępu</label></th>
            <td>
                <label>
                    <input type="checkbox" name="fb_progress_bar" id="fb_progress_bar" value="1" <?php checked( $c['progress_bar'], 1 ); ?>>
                    Pasek postępu czytania (na górze strony)
                </label>
            </td>
        </tr>
        <tr>
            <th><label for="fb_author_name">Imię autora</label></th>
            <td>
                <input type="text" name="fb_author_name" id="fb_author_name" class="regular-text" value="<?php echo esc_attr( $c['author_name'] ); ?>">
            </td>
        </tr>
        <tr>
            <th><label for="fb_author_role">Rola / tytuł</label></th>
            <td>
                <input type="text" name="fb_author_role" id="fb_author_role" class="regular-text" value="<?php echo esc_attr( $c['author_role'] ); ?>">
            </td>
        </tr>
        <tr>
            <th><label>Avatar autora</label></th>
            <td>
                <div class="blm-image-field">
                    <input type="hidden" name="fb_author_avatar" id="blm_fb_avatar_url" value="<?php echo esc_attr( $c['author_avatar'] ); ?>">
                    <div id="blm_fb_avatar_preview" style="margin-bottom:8px;">
                        <?php if ( $c['author_avatar'] ) : ?>
                            <img src="<?php echo esc_url( $c['author_avatar'] ); ?>" style="max-width:60px;height:60px;border-radius:50%;object-fit:cover;">
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button blm-media-upload" data-target="blm_fb_avatar_url" data-preview="blm_fb_avatar_preview" data-remove="blm_fb_avatar_remove" data-mode="url">Wybierz z biblioteki</button>
                    <button type="button" class="button blm-media-remove" data-target="blm_fb_avatar_url" data-preview="blm_fb_avatar_preview" data-mode="url" id="blm_fb_avatar_remove" <?php echo $c['author_avatar'] ? '' : 'style="display:none"'; ?>>Usuń avatar</button>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="fb_btn_text">Przycisk</label></th>
            <td>
                <input type="text" name="fb_btn_text" id="fb_btn_text" class="regular-text" value="<?php echo esc_attr( $c['btn_text'] ); ?>" placeholder="Tekst przycisku">
                <input type="url" name="fb_btn_url" id="fb_btn_url" class="regular-text" value="<?php echo esc_attr( $c['btn_url'] ); ?>" placeholder="https://..." style="margin-top:5px;display:block;">
            </td>
        </tr>
        <tr>
            <th>Kolory</th>
            <td>
                <div class="blm-colors-grid">
                    <div>
                        <label for="fb_bar_bg">Tło paska</label><br>
                        <input type="text" name="fb_bar_bg" id="fb_bar_bg" class="blm-color-picker" value="<?php echo esc_attr( $c['bar_bg'] ); ?>">
                    </div>
                    <div>
                        <label for="fb_btn_color">Przycisk</label><br>
                        <input type="text" name="fb_btn_color" id="fb_btn_color" class="blm-color-picker" value="<?php echo esc_attr( $c['btn_color'] ); ?>">
                    </div>
                    <div>
                        <label for="fb_progress_color">Pasek postępu</label><br>
                        <input type="text" name="fb_progress_color" id="fb_progress_color" class="blm-color-picker" value="<?php echo esc_attr( $c['progress_color'] ); ?>">
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <p class="submit">
        <input type="submit" name="blm_floating_bar_save" class="button button-primary" value="Zapisz ustawienia">
    </p>
</form>
