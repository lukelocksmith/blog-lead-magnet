<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Per-post CTA override meta box.
 *
 * All fields mirror the global CTA form. Empty field = use global value.
 * Overrides stored as JSON in _blm_post_ctas post meta.
 */
class BLM_Admin_Post_Meta {

    public function __construct() {
        add_action( 'add_meta_boxes',        array( $this, 'add_meta_box' ) );
        add_action( 'save_post',             array( $this, 'save_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }
        // Only load on post type 'post' — meta box is registered only there
        $screen = get_current_screen();
        if ( ! $screen || 'post' !== $screen->post_type ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_style(
            'blm-admin',
            BLM_PLUGIN_URL . 'admin/css/blm-admin.css',
            array( 'wp-color-picker' ),
            BLM_VERSION
        );
        wp_enqueue_script(
            'blm-admin',
            BLM_PLUGIN_URL . 'admin/js/blm-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            BLM_VERSION,
            true
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'blm_post_ctas',
            'Blog Lead Magnet',
            array( $this, 'render' ),
            'post',
            'normal',
            'default'
        );
    }

    public function render( $post ) {
        wp_nonce_field( 'blm_post_ctas_save', 'blm_post_ctas_nonce' );

        $all_ctas  = BLM_CTA_Model::get_all();
        $overrides = $this->get_overrides( $post->ID );
        $post_cats = $this->get_post_cat_slugs( $post->ID );

        if ( empty( $all_ctas ) ) {
            echo '<p style="color:#999;margin:8px 0;">Brak zdefiniowanych CTA.</p>';
            return;
        }

        echo '<p style="color:var(--blm-text-muted,#64748b);margin:0 0 12px;font-size:12px;">Puste pole = używa wartości globalnej. Kliknij kartę CTA żeby rozwinąć ustawienia.</p>';
        echo '<div class="blm-meta-cta-list">';

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

        foreach ( $all_ctas as $cta ) {
            $id       = (string) $cta->id;
            $override = isset( $overrides[ $id ] ) ? $overrides[ $id ] : array();
            $disabled = ! empty( $override['disabled'] );

            // Has any override fields set?
            $has_overrides = ! empty( array_filter( $override, function( $v, $k ) {
                return $k !== 'disabled' && '' !== $v;
            }, ARRAY_FILTER_USE_BOTH ) );

            $expanded  = ( $disabled || $has_overrides ) ? '1' : '0';

            $filter    = isset( $cta->category_filter ) ? trim( $cta->category_filter ) : '';
            $applies   = '' === $filter || ! empty( array_intersect( $post_cats, array_map( 'trim', explode( ',', $filter ) ) ) );
            $cta_type  = isset( $cta->type ) ? $cta->type : 'cta';
            $type_cls  = 'gate' === $cta_type ? 'blm-badge-yellow' : 'blm-badge-blue';
            $type_text = 'gate' === $cta_type ? 'Gate' : 'CTA';
            $edit_url  = admin_url( 'admin.php?page=blog-lead-magnet&tab=cta&action=edit&cta_id=' . $cta->id );

            // Helper: override value or empty
            $ov = function( $key ) use ( $override ) {
                return isset( $override[ $key ] ) ? $override[ $key ] : '';
            };

            echo '<div class="blm-meta-cta-card" data-expanded="' . esc_attr( $expanded ) . '">';

            // ── Header ────────────────────────────────────────────────────────
            echo '<div class="blm-meta-cta-header" role="button" tabindex="0">';
            // Chevron
            echo '<svg class="blm-meta-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';
            // Title + badges
            echo '<div style="flex:1;min-width:0;">';
            echo '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">';
            echo '<span style="font-size:12px;font-weight:600;color:var(--blm-text,#0f172a);">' . esc_html( $cta->heading ?: '(bez nagłówka)' ) . '</span>';
            echo '<span class="blm-badge ' . esc_attr( $type_cls ) . '">' . esc_html( $type_text ) . '</span>';
            if ( ! $applies ) {
                echo '<span class="blm-badge blm-badge-slate">Inna kategoria</span>';
            }
            echo '</div>';
            echo '<label class="blm-checkbox-row" style="margin-top:4px;" onclick="event.stopPropagation()">';
            echo '<input type="checkbox" name="blm_cta_disabled[' . esc_attr( $id ) . ']" value="1" ' . checked( $disabled, true, false ) . '>';
            echo '<span class="blm-checkbox-label" style="color:var(--blm-destructive,#dc2626);font-size:11px;">Wyłącz na tym artykule</span>';
            echo '</label>';
            echo '</div>';
            echo '<a href="' . esc_url( $edit_url ) . '" target="_blank" onclick="event.stopPropagation()" style="font-size:11px;color:var(--blm-primary,#2563eb);white-space:nowrap;text-decoration:none;flex-shrink:0;">Zmień globalnie →</a>';
            echo '</div>'; // .blm-meta-cta-header

            // ── Body (collapsible) ────────────────────────────────────────────
            echo '<div class="blm-meta-cta-body">';

            // ── Treść ─────────────────────────────────────────────────────────
            echo '<p class="blm-meta-section">Treść</p>';
            echo '<div class="blm-meta-grid">';

            // Heading
            echo '<div class="blm-meta-field-full">';
            echo '<label class="blm-meta-label">Nagłówek</label>';
            echo '<input type="text" name="blm_cta_override[' . esc_attr( $id ) . '][heading]" value="' . esc_attr( $ov( 'heading' ) ) . '" placeholder="' . esc_attr( $cta->heading ?: 'wartość globalna' ) . '" class="blm-input" style="font-size:12px;padding:5px 8px;">';
            echo '</div>';

            // Body
            echo '<div class="blm-meta-field-full">';
            echo '<label class="blm-meta-label">Treść</label>';
            echo '<textarea name="blm_cta_override[' . esc_attr( $id ) . '][body]" rows="3" placeholder="wartość globalna" class="blm-input" style="font-size:12px;padding:5px 8px;resize:vertical;width:100%;">' . esc_textarea( $ov( 'body' ) ) . '</textarea>';
            echo '</div>';

            // Shortcode
            echo '<div class="blm-meta-field-full">';
            echo '<label class="blm-meta-label">Shortcode</label>';
            echo '<input type="text" name="blm_cta_override[' . esc_attr( $id ) . '][shortcode]" value="' . esc_attr( $ov( 'shortcode' ) ) . '" placeholder="' . esc_attr( $cta->shortcode ?: 'wartość globalna' ) . '" class="blm-input" style="font-size:12px;padding:5px 8px;">';
            echo '</div>';

            echo '</div>'; // .blm-meta-grid

            // ── Przycisk ─────────────────────────────────────────────────────
            echo '<p class="blm-meta-section">Przycisk</p>';
            echo '<div class="blm-meta-grid">';

            echo '<div>';
            echo '<label class="blm-meta-label">Tekst</label>';
            echo '<input type="text" name="blm_cta_override[' . esc_attr( $id ) . '][button_text]" value="' . esc_attr( $ov( 'button_text' ) ) . '" placeholder="' . esc_attr( $cta->button_text ?: 'wartość globalna' ) . '" class="blm-input" style="font-size:12px;padding:5px 8px;">';
            echo '</div>';

            echo '<div>';
            echo '<label class="blm-meta-label">URL</label>';
            echo '<input type="url" name="blm_cta_override[' . esc_attr( $id ) . '][button_url]" value="' . esc_attr( $ov( 'button_url' ) ) . '" placeholder="' . esc_attr( $cta->button_url ?: 'https://...' ) . '" class="blm-input" style="font-size:12px;padding:5px 8px;">';
            echo '</div>';

            echo '</div>'; // .blm-meta-grid

            // ── Wygląd ───────────────────────────────────────────────────────
            echo '<p class="blm-meta-section">Wygląd</p>';
            echo '<div class="blm-meta-grid blm-meta-grid--4">';

            $color_fields = array(
                'bg_color'     => array( 'Tło',     $cta->bg_color ),
                'text_color'   => array( 'Tekst',   $cta->text_color ),
                'button_color' => array( 'Przycisk', $cta->button_color ),
            );
            foreach ( $color_fields as $field => $color_def ) {
                list( $label, $global ) = $color_def;
                $field_id = 'blm_ov_' . $id . '_' . $field;
                $val      = $ov( $field );
                echo '<div>';
                echo '<label class="blm-meta-label" for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . '</label>';
                echo '<div style="display:flex;align-items:center;gap:4px;">';
                echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="blm_cta_override[' . esc_attr( $id ) . '][' . esc_attr( $field ) . ']" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $global ) . '" class="blm-color-picker" style="width:80px;">';
                if ( $val ) {
                    echo '<button type="button" class="blm-meta-color-reset" data-target="' . esc_attr( $field_id ) . '" title="Usuń nadpisanie" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--blm-text-muted);font-size:14px;line-height:1;">×</button>';
                }
                echo '</div>';
                echo '</div>';
            }

            echo '<div>';
            echo '<label class="blm-meta-label" for="blm_ov_' . esc_attr( $id ) . '_text_size">Rozmiar</label>';
            echo '<div style="display:flex;align-items:center;gap:4px;">';
            echo '<input type="number" id="blm_ov_' . esc_attr( $id ) . '_text_size" name="blm_cta_override[' . esc_attr( $id ) . '][text_size]" value="' . esc_attr( $ov( 'text_size' ) ) . '" placeholder="' . esc_attr( $cta->text_size ) . '" min="10" max="48" class="blm-input blm-input-small" style="font-size:12px;padding:5px 8px;width:60px;">';
            echo '<span style="font-size:11px;color:var(--blm-text-muted);">px</span>';
            echo '</div>';
            echo '</div>';

            echo '</div>'; // .blm-meta-grid

            // ── Wyświetlanie ─────────────────────────────────────────────────
            echo '<p class="blm-meta-section">Wyświetlanie</p>';
            echo '<div class="blm-meta-grid">';

            echo '<div>';
            echo '<label class="blm-meta-label" for="blm_ov_' . esc_attr( $id ) . '_cond">Gdzie</label>';
            $cond_val = $ov( 'display_condition' );
            echo '<select id="blm_ov_' . esc_attr( $id ) . '_cond" name="blm_cta_override[' . esc_attr( $id ) . '][display_condition]" class="blm-select" style="font-size:12px;max-width:200px;">';
            echo '<option value="">— użyj globalnej —</option>';
            foreach ( $conditions_labels as $val => $lbl ) {
                echo '<option value="' . esc_attr( $val ) . '" ' . selected( $cond_val, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
            }
            echo '</select>';
            echo '</div>';

            echo '</div>'; // .blm-meta-grid

            echo '<p style="margin:10px 0 0;font-size:11px;color:var(--blm-text-subtle,#94a3b8);">Puste pole = używa wartości globalnej CTA.</p>';

            echo '</div>'; // .blm-meta-cta-body

            echo '</div>'; // .blm-meta-cta-card
        }

        echo '</div>'; // .blm-meta-cta-list
    }

    public function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['blm_post_ctas_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_POST['blm_post_ctas_nonce'], 'blm_post_ctas_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $sanitizers = array(
            'heading'          => 'sanitize_text_field',
            'body'             => 'wp_kses_post',
            'shortcode'        => 'sanitize_text_field',
            'button_text'      => 'sanitize_text_field',
            'button_url'       => 'esc_url_raw',
            'bg_color'         => 'sanitize_hex_color',
            'text_color'       => 'sanitize_hex_color',
            'button_color'     => 'sanitize_hex_color',
            'text_size'        => 'absint',
            'display_condition'=> 'sanitize_text_field',
        );

        $data      = array();
        $disabled  = isset( $_POST['blm_cta_disabled'] )  ? (array) $_POST['blm_cta_disabled']  : array();
        $overrides = isset( $_POST['blm_cta_override'] )  ? (array) $_POST['blm_cta_override']  : array();
        $all_ids   = array_unique( array_merge( array_keys( $disabled ), array_keys( $overrides ) ) );

        foreach ( $all_ids as $id ) {
            $id    = sanitize_text_field( $id );
            $entry = array();

            if ( isset( $disabled[ $id ] ) ) {
                $entry['disabled'] = true;
            }

            if ( isset( $overrides[ $id ] ) ) {
                foreach ( $overrides[ $id ] as $field => $val ) {
                    $field = sanitize_key( $field );
                    if ( ! isset( $sanitizers[ $field ] ) ) {
                        continue;
                    }
                    $clean = call_user_func( $sanitizers[ $field ], $val );
                    // 0 is valid for text_size — only skip truly empty strings
                    if ( '' !== $clean && ! ( 'text_size' === $field && 0 === $clean ) ) {
                        $entry[ $field ] = $clean;
                    }
                }
            }

            if ( ! empty( $entry ) ) {
                $data[ $id ] = $entry;
            }
        }

        if ( empty( $data ) ) {
            delete_post_meta( $post_id, '_blm_post_ctas' );
        } else {
            update_post_meta( $post_id, '_blm_post_ctas', wp_json_encode( $data ) );
        }
    }

    private function get_overrides( $post_id ) {
        $raw = get_post_meta( $post_id, '_blm_post_ctas', true );
        if ( ! $raw ) {
            return array();
        }
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : array();
    }

    private function get_post_cat_slugs( $post_id ) {
        $cats = get_the_category( $post_id );
        if ( empty( $cats ) ) {
            return array();
        }
        return array_map( function( $cat ) { return $cat->slug; }, $cats );
    }
}
