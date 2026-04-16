<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$c = BLM_Floating_Bar::get();
?>

<div class="blm-card">
    <div class="blm-card-header">
        <h2 class="blm-card-title">Pływający pasek</h2>
    </div>
    <div class="blm-card-body">
        <p style="margin:0 0 20px;color:var(--blm-text-muted);font-size:13px;">Pasek na dole ekranu z przyciskiem CTA i/lub spisem treści + pasek postępu na górze — widoczny na artykułach.</p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blog-lead-magnet&tab=floating-bar' ) ); ?>">
            <?php wp_nonce_field( 'blm_floating_bar_save', 'blm_fb_nonce' ); ?>

            <!-- ── Ogólne ─────────────────────────────────────── -->
            <p class="blm-section-title" style="margin-top:0;">Ogólne</p>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label" for="fb_enabled">Status</label>
                </div>
                <div>
                    <label class="blm-checkbox-row">
                        <input type="checkbox" name="fb_enabled" id="fb_enabled" value="1" <?php checked( $c['enabled'], 1 ); ?>>
                        <span class="blm-checkbox-label">Aktywny — wyświetlaj na artykułach</span>
                    </label>
                </div>
            </div>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label" for="fb_mode">Tryb</label>
                    <p class="blm-description">Co wyświetla pasek.</p>
                </div>
                <div>
                    <select name="fb_mode" id="fb_mode" class="blm-select" style="max-width:320px;">
                        <option value="both"     <?php selected( $c['mode'], 'both' ); ?>>Przycisk CTA + Spis treści</option>
                        <option value="cta_only" <?php selected( $c['mode'], 'cta_only' ); ?>>Tylko przycisk CTA</option>
                        <option value="toc_only" <?php selected( $c['mode'], 'toc_only' ); ?>>Tylko Spis treści</option>
                    </select>
                </div>
            </div>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label" for="fb_progress_bar">Pasek postępu</label>
                    <p class="blm-description">Cienka linia na górze strony pokazująca postęp czytania.</p>
                </div>
                <div>
                    <label class="blm-checkbox-row">
                        <input type="checkbox" name="fb_progress_bar" id="fb_progress_bar" value="1" <?php checked( $c['progress_bar'], 1 ); ?>>
                        <span class="blm-checkbox-label">Włącz pasek postępu czytania</span>
                    </label>
                </div>
            </div>

            <!-- ── Autor + Przycisk CTA (hidden in toc_only mode) ── -->
            <div id="blm-fb-cta-sections">

            <p class="blm-section-title">Autor</p>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label" for="fb_author_name">Imię</label>
                </div>
                <div>
                    <input type="text" name="fb_author_name" id="fb_author_name" class="blm-input" value="<?php echo esc_attr( $c['author_name'] ); ?>" placeholder="np. Jan Kowalski">
                </div>
            </div>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label" for="fb_author_role">Rola / tytuł</label>
                </div>
                <div>
                    <input type="text" name="fb_author_role" id="fb_author_role" class="blm-input" value="<?php echo esc_attr( $c['author_role'] ); ?>" placeholder="np. Redaktor naczelny">
                </div>
            </div>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label">Avatar</label>
                </div>
                <div>
                    <div id="blm_fb_avatar_preview" style="margin-bottom:10px;">
                        <?php if ( $c['author_avatar'] ) : ?>
                            <img src="<?php echo esc_url( $c['author_avatar'] ); ?>" style="width:56px;height:56px;border-radius:50%;object-fit:cover;display:block;">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="fb_author_avatar" id="blm_fb_avatar_url" value="<?php echo esc_attr( $c['author_avatar'] ); ?>">
                    <button type="button" class="blm-btn blm-btn-secondary blm-media-upload" data-target="blm_fb_avatar_url" data-preview="blm_fb_avatar_preview" data-remove="blm_fb_avatar_remove" data-mode="url">Wybierz z biblioteki</button>
                    <button type="button" class="blm-btn blm-btn-ghost blm-media-remove" data-target="blm_fb_avatar_url" data-preview="blm_fb_avatar_preview" data-mode="url" id="blm_fb_avatar_remove" <?php echo $c['author_avatar'] ? '' : 'style="display:none"'; ?>>Usuń</button>
                </div>
            </div>

            <!-- ── Przycisk CTA ───────────────────────────────── -->
            <p class="blm-section-title">Przycisk CTA</p>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label" for="fb_btn_text">Przycisk</label>
                </div>
                <div style="display:grid;gap:8px;">
                    <input type="text" name="fb_btn_text" id="fb_btn_text" class="blm-input" value="<?php echo esc_attr( $c['btn_text'] ); ?>" placeholder="Tekst przycisku">
                    <input type="url"  name="fb_btn_url"  id="fb_btn_url"  class="blm-input" value="<?php echo esc_attr( $c['btn_url'] ); ?>"  placeholder="https://...">
                </div>
            </div>

            </div><!-- #blm-fb-cta-sections -->

            <!-- ── Wygląd ─────────────────────────────────────── -->
            <p class="blm-section-title">Wygląd</p>

            <div class="blm-field">
                <div class="blm-field-label">
                    <label class="blm-label">Kolory</label>
                </div>
                <div class="blm-colors-grid">
                    <div>
                        <label class="blm-label" for="fb_bar_bg">Tło paska</label>
                        <input type="text" name="fb_bar_bg" id="fb_bar_bg" class="blm-color-picker" value="<?php echo esc_attr( $c['bar_bg'] ); ?>">
                    </div>
                    <div>
                        <label class="blm-label" for="fb_btn_color">Przycisk</label>
                        <input type="text" name="fb_btn_color" id="fb_btn_color" class="blm-color-picker" value="<?php echo esc_attr( $c['btn_color'] ); ?>">
                    </div>
                    <div>
                        <label class="blm-label" for="fb_progress_color">Pasek postępu</label>
                        <input type="text" name="fb_progress_color" id="fb_progress_color" class="blm-color-picker" value="<?php echo esc_attr( $c['progress_color'] ); ?>">
                    </div>
                </div>
            </div>

            <div class="blm-form-footer">
                <input type="submit" name="blm_floating_bar_save" class="blm-btn blm-btn-primary" value="Zapisz ustawienia">
            </div>

        </form>
    </div><!-- .blm-card-body -->
</div><!-- .blm-card -->

<script>
(function() {
    var modeSelect = document.getElementById('fb_mode');
    var ctaSections = document.getElementById('blm-fb-cta-sections');
    if (!modeSelect || !ctaSections) return;

    function applyMode(mode) {
        ctaSections.style.display = (mode === 'toc_only') ? 'none' : '';
    }

    applyMode(modeSelect.value);
    modeSelect.addEventListener('change', function() { applyMode(this.value); });
})();
</script>
