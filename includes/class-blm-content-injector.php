<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Content_Injector {

    private static $ctas_cache = null;

    public function __construct() {
        // Gate runs first (priority 15), then CTA injection (priority 20)
        add_filter( 'the_content', array( $this, 'apply_gate' ), 15 );
        add_filter( 'the_content', array( $this, 'inject_ctas' ), 20 );
    }

    // ── Gate ────────────────────────────────────────────────────────────────

    public function apply_gate( $content ) {
        if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();

        // Check global localStorage unlock — handled in JS, so PHP always renders gate HTML.
        // JS will reveal .blm-gated-content immediately if blm_unlocked is set in localStorage.

        $gates = $this->get_ctas_for_post( $post_id, 'gate' );
        if ( empty( $gates ) ) {
            return $content;
        }

        // Use the highest-priority (lowest priority number) gate
        $gate = $gates[0];

        $offset = $this->find_condition_offset( $content, $gate->display_condition );
        if ( false === $offset ) {
            return $content;
        }

        $visible_html = substr( $content, 0, $offset );
        $hidden_html  = substr( $content, $offset );

        return $visible_html
            . $this->render_gate( $gate )
            . '<div class="blm-gated-content" style="display:none">'
            . $hidden_html
            . '</div>';
    }

    // ── CTA injection ────────────────────────────────────────────────────────

    public function inject_ctas( $content ) {
        if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();
        $ctas    = $this->get_ctas_for_post( $post_id, 'cta' );

        if ( empty( $ctas ) ) {
            return $content;
        }

        $injections = $this->calculate_injections( $content, $ctas );

        if ( empty( $injections ) ) {
            return $content;
        }

        usort( $injections, function( $a, $b ) {
            return $b['offset'] - $a['offset'];
        } );

        foreach ( $injections as $injection ) {
            $cta_html = $this->render_cta( $injection['cta'] );
            $content  = substr_replace( $content, $cta_html, $injection['offset'], 0 );
        }

        return $content;
    }

    // ── CTA resolution ───────────────────────────────────────────────────────

    /**
     * Get active CTAs of a given type for a specific post.
     * Applies category filter and per-post overrides.
     */
    private function get_ctas_for_post( $post_id, $type = 'cta' ) {
        $all_ctas = $this->get_active_ctas();

        // Filter by type
        $ctas = array_filter( $all_ctas, function( $cta ) use ( $type ) {
            $cta_type = isset( $cta->type ) ? $cta->type : 'cta';
            return $cta_type === $type;
        } );

        if ( empty( $ctas ) ) {
            return array();
        }

        // Filter by category
        $post_cats = $this->get_post_category_slugs( $post_id );
        $ctas = array_filter( $ctas, function( $cta ) use ( $post_cats ) {
            $filter = isset( $cta->category_filter ) ? trim( $cta->category_filter ) : '';
            if ( '' === $filter ) {
                return true; // no filter = show on all posts
            }
            $allowed = array_map( 'trim', explode( ',', $filter ) );
            return ! empty( array_intersect( $post_cats, $allowed ) );
        } );

        if ( empty( $ctas ) ) {
            return array();
        }

        // Apply per-post overrides
        $overrides = $this->get_post_overrides( $post_id );
        if ( ! empty( $overrides ) ) {
            $ctas = array_filter( array_map( function( $cta ) use ( $overrides ) {
                $id = (string) $cta->id;
                if ( ! isset( $overrides[ $id ] ) ) {
                    return $cta;
                }
                $override = $overrides[ $id ];
                // Disabled on this post
                if ( ! empty( $override['disabled'] ) ) {
                    return null;
                }
                // Merge override fields
                foreach ( $override as $key => $val ) {
                    if ( $key !== 'disabled' ) {
                        $cta->$key = $val;
                    }
                }
                return $cta;
            }, $ctas ) );
        }

        return array_values( $ctas );
    }

    private function get_active_ctas() {
        if ( null !== self::$ctas_cache ) {
            return self::$ctas_cache;
        }

        $cached = get_transient( 'blm_active_ctas' );
        if ( false !== $cached ) {
            self::$ctas_cache = $cached;
            return $cached;
        }

        self::$ctas_cache = BLM_CTA_Model::get_all( true );
        set_transient( 'blm_active_ctas', self::$ctas_cache, HOUR_IN_SECONDS );
        return self::$ctas_cache;
    }

    private function get_post_category_slugs( $post_id ) {
        $cats = get_the_category( $post_id );
        if ( empty( $cats ) ) {
            return array();
        }
        return array_map( function( $cat ) {
            return $cat->slug;
        }, $cats );
    }

    private function get_post_overrides( $post_id ) {
        $raw = get_post_meta( $post_id, '_blm_post_ctas', true );
        if ( ! $raw ) {
            return array();
        }
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : array();
    }

    // ── Injection point calculation ──────────────────────────────────────────

    private function calculate_injections( $content, $ctas ) {
        $injections      = array();
        $claimed_offsets = array();
        $plain_text      = wp_strip_all_tags( $content );
        $total_length    = mb_strlen( $plain_text, 'UTF-8' );

        $grouped = array();
        foreach ( $ctas as $cta ) {
            $grouped[ $cta->display_condition ][] = $cta;
        }

        $h2_offsets = $this->find_h2_offsets( $content );

        foreach ( $grouped as $condition => $condition_ctas ) {
            if ( preg_match( '/^after_h2_(\d+)$/', $condition, $m ) ) {
                $index = (int) $m[1] - 1;
                if ( isset( $h2_offsets[ $index ] ) ) {
                    $offset = $h2_offsets[ $index ];
                    if ( ! $this->is_offset_claimed( $offset, $claimed_offsets ) ) {
                        $injections[]     = array( 'offset' => $offset, 'cta' => $condition_ctas[0] );
                        $claimed_offsets[] = $offset;
                    }
                }
                continue;
            }

            switch ( $condition ) {
                case 'after_30':
                case 'after_50':
                case 'after_70':
                    $percent = (int) str_replace( 'after_', '', $condition );
                    $offset  = $this->find_percent_offset( $content, $plain_text, $total_length, $percent );
                    if ( $offset !== false && ! $this->is_offset_claimed( $offset, $claimed_offsets ) ) {
                        $injections[]     = array( 'offset' => $offset, 'cta' => $condition_ctas[0] );
                        $claimed_offsets[] = $offset;
                    }
                    break;

                case 'end':
                    $offset = strlen( $content );
                    if ( ! $this->is_offset_claimed( $offset, $claimed_offsets ) ) {
                        $injections[]     = array( 'offset' => $offset, 'cta' => $condition_ctas[0] );
                        $claimed_offsets[] = $offset;
                    }
                    break;
            }
        }

        return $injections;
    }

    /**
     * Find a single content offset for a given display_condition (used by gate).
     */
    private function find_condition_offset( $content, $condition ) {
        $plain_text   = wp_strip_all_tags( $content );
        $total_length = mb_strlen( $plain_text, 'UTF-8' );

        if ( preg_match( '/^after_h2_(\d+)$/', $condition, $m ) ) {
            $h2_offsets = $this->find_h2_offsets( $content );
            $index = (int) $m[1] - 1;
            return isset( $h2_offsets[ $index ] ) ? $h2_offsets[ $index ] : false;
        }

        if ( in_array( $condition, array( 'after_30', 'after_50', 'after_70' ), true ) ) {
            $percent = (int) str_replace( 'after_', '', $condition );
            return $this->find_percent_offset( $content, $plain_text, $total_length, $percent );
        }

        if ( 'end' === $condition ) {
            return strlen( $content );
        }

        return false;
    }

    private function find_h2_offsets( $content ) {
        $offsets = array();

        if ( ! preg_match_all( '/<h[2-6][^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $offsets;
        }

        $headings = $matches[0];

        for ( $i = 0; $i < count( $headings ); $i++ ) {
            $tag = strtolower( substr( $headings[ $i ][0], 0, 3 ) );

            if ( '<h2' !== $tag ) {
                continue;
            }

            $search_start = $headings[ $i ][1];
            $search_end   = isset( $headings[ $i + 1 ] ) ? $headings[ $i + 1 ][1] : strlen( $content );
            $section      = substr( $content, $search_start, $search_end - $search_start );
            $last_p       = strrpos( $section, '</p>' );

            $offsets[] = false !== $last_p
                ? $search_start + $last_p + 4
                : $search_end;
        }

        return $offsets;
    }

    private function find_percent_offset( $content, $plain_text, $total_length, $percent ) {
        if ( $total_length < 100 ) {
            return false;
        }

        $target_chars = (int) ( $total_length * $percent / 100 );
        $offset       = 0;
        $char_count   = 0;
        $best_pos     = false;

        // O(n): strip only the incremental segment between </p> tags instead of
        // re-stripping from the start each iteration (was O(n²) on long posts).
        while ( ( $p_pos = strpos( $content, '</p>', $offset ) ) !== false ) {
            $segment     = substr( $content, $offset, $p_pos + 4 - $offset );
            $char_count += mb_strlen( wp_strip_all_tags( $segment ), 'UTF-8' );

            if ( $char_count >= $target_chars ) {
                $best_pos = $p_pos + 4;
                break;
            }

            $offset = $p_pos + 4;
        }

        return $best_pos;
    }

    private function is_offset_claimed( $offset, $claimed_offsets, $threshold = 200 ) {
        foreach ( $claimed_offsets as $claimed ) {
            if ( abs( $offset - $claimed ) < $threshold ) {
                return true;
            }
        }
        return false;
    }

    // ── Renderers ────────────────────────────────────────────────────────────

    private function render_gate( $cta ) {
        $style = sprintf(
            'background-color:%s;color:%s;',
            esc_attr( $cta->bg_color ),
            esc_attr( $cta->text_color )
        );

        $inner = '';

        if ( $cta->heading ) {
            $inner .= '<h3 class="blm-gate__heading">' . esc_html( $cta->heading ) . '</h3>';
        }

        if ( $cta->body ) {
            $inner .= '<div class="blm-gate__body">' . wp_kses_post( $cta->body ) . '</div>';
        }

        if ( $cta->shortcode ) {
            $inner .= '<div class="blm-gate__form">' . do_shortcode( $cta->shortcode ) . '</div>';
        }

        if ( $cta->button_text && $cta->button_url ) {
            $btn_style = sprintf( 'background-color:%s;', esc_attr( $cta->button_color ) );
            $inner .= '<div class="blm-gate__button-wrap">';
            $inner .= '<a href="' . esc_url( $cta->button_url ) . '" class="blm-gate__button" style="' . $btn_style . '">';
            $inner .= esc_html( $cta->button_text );
            $inner .= '</a></div>';
        }

        return '<div class="blm-gate" data-cta-id="' . esc_attr( $cta->id ) . '" style="' . $style . '">'
            . '<div class="blm-gate__fade"></div>'
            . '<div class="blm-gate__inner">' . $inner . '</div>'
            . '</div>';
    }

    private function render_cta( $cta ) {
        if ( ! empty( $cta->is_bare ) ) {
            $inner = '';

            if ( $cta->image_id ) {
                $img = wp_get_attachment_image( $cta->image_id, 'medium', false, array( 'class' => 'blm-cta__image' ) );
                if ( $img ) {
                    $inner .= '<div class="blm-cta__image-wrap">' . $img . '</div>';
                }
            }

            if ( $cta->heading ) {
                $inner .= '<h3 class="blm-cta__heading">' . esc_html( $cta->heading ) . '</h3>';
            }

            if ( $cta->body ) {
                $inner .= '<div class="blm-cta__body">' . wp_kses_post( $cta->body ) . '</div>';
            }

            if ( $cta->shortcode ) {
                $inner .= do_shortcode( $cta->shortcode );
            }

            if ( $cta->button_text && $cta->button_url ) {
                $btn_style = sprintf( 'background-color:%s;', esc_attr( $cta->button_color ) );
                $inner .= '<div class="blm-cta__button-wrap">';
                $inner .= '<a href="' . esc_url( $cta->button_url ) . '" class="blm-cta__button" style="' . $btn_style . '">';
                $inner .= esc_html( $cta->button_text );
                $inner .= '</a></div>';
            }

            return '<div class="blm-cta blm-cta--bare" data-cta-id="' . esc_attr( $cta->id ) . '">'
                . $inner
                . '</div>';
        }

        $style = sprintf(
            'background-color:%s;color:%s;font-size:%dpx;',
            esc_attr( $cta->bg_color ),
            esc_attr( $cta->text_color ),
            intval( $cta->text_size )
        );

        $html = '<div class="blm-cta" data-cta-id="' . esc_attr( $cta->id ) . '" style="' . $style . '">';

        if ( $cta->image_id ) {
            $img = wp_get_attachment_image( $cta->image_id, 'medium', false, array( 'class' => 'blm-cta__image' ) );
            if ( $img ) {
                $html .= '<div class="blm-cta__image-wrap">' . $img . '</div>';
            }
        }

        $html .= '<div class="blm-cta__content">';

        if ( $cta->heading ) {
            $html .= '<h3 class="blm-cta__heading">' . esc_html( $cta->heading ) . '</h3>';
        }

        if ( $cta->body ) {
            $html .= '<div class="blm-cta__body">' . wp_kses_post( $cta->body ) . '</div>';
        }

        if ( $cta->shortcode ) {
            $html .= '<div class="blm-cta__shortcode">' . do_shortcode( $cta->shortcode ) . '</div>';
        }

        if ( $cta->button_text && $cta->button_url ) {
            $btn_style = sprintf( 'background-color:%s;', esc_attr( $cta->button_color ) );
            $html .= '<div class="blm-cta__button-wrap">';
            $html .= '<a href="' . esc_url( $cta->button_url ) . '" class="blm-cta__button" style="' . $btn_style . '">';
            $html .= esc_html( $cta->button_text );
            $html .= '</a></div>';
        }

        $html .= '</div>'; // .blm-cta__content
        $html .= '</div>'; // .blm-cta

        return $html;
    }
}
