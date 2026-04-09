<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Content_Injector {

    private static $ctas_cache = null;

    public function __construct() {
        add_filter( 'the_content', array( $this, 'inject_ctas' ), 20 );
    }

    public function inject_ctas( $content ) {
        if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $ctas = $this->get_active_ctas();
        if ( empty( $ctas ) ) {
            return $content;
        }

        // Calculate all injection points
        $injections = $this->calculate_injections( $content, $ctas );

        if ( empty( $injections ) ) {
            return $content;
        }

        // Sort by offset descending so we insert from end to start (preserves earlier offsets)
        usort( $injections, function( $a, $b ) {
            return $b['offset'] - $a['offset'];
        } );

        // Insert CTA HTML at each offset
        foreach ( $injections as $injection ) {
            $cta_html = $this->render_cta( $injection['cta'] );
            $content = substr_replace( $content, $cta_html, $injection['offset'], 0 );
        }

        return $content;
    }

    private function get_active_ctas() {
        if ( null !== self::$ctas_cache ) {
            return self::$ctas_cache;
        }

        // Try transient cache first (persists across page loads)
        $cached = get_transient( 'blm_active_ctas' );
        if ( false !== $cached ) {
            self::$ctas_cache = $cached;
            return $cached;
        }

        self::$ctas_cache = BLM_CTA_Model::get_all( true );
        set_transient( 'blm_active_ctas', self::$ctas_cache, HOUR_IN_SECONDS );
        return self::$ctas_cache;
    }

    private function calculate_injections( $content, $ctas ) {
        $injections     = array();
        $claimed_offsets = array();
        $plain_text     = wp_strip_all_tags( $content );
        $total_length   = mb_strlen( $plain_text, 'UTF-8' );

        // Group CTAs by condition, already sorted by priority
        $grouped = array();
        foreach ( $ctas as $cta ) {
            $grouped[ $cta->display_condition ][] = $cta;
        }

        // Pre-calculate H2 section offsets (shared across after_h2_N conditions)
        $h2_offsets = $this->find_h2_offsets( $content );

        // Process each condition type
        foreach ( $grouped as $condition => $condition_ctas ) {
            // after_h2_1, after_h2_2, ... after_h2_5
            if ( preg_match( '/^after_h2_(\d+)$/', $condition, $m ) ) {
                $section_num = (int) $m[1];
                $index = $section_num - 1; // 0-based
                if ( isset( $h2_offsets[ $index ] ) ) {
                    $offset = $h2_offsets[ $index ];
                    if ( ! $this->is_offset_claimed( $offset, $claimed_offsets ) ) {
                        $injections[]    = array( 'offset' => $offset, 'cta' => $condition_ctas[0] );
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
                        $injections[]    = array( 'offset' => $offset, 'cta' => $condition_ctas[0] );
                        $claimed_offsets[] = $offset;
                    }
                    break;

                case 'end':
                    $offset = strlen( $content );
                    if ( ! $this->is_offset_claimed( $offset, $claimed_offsets ) ) {
                        $injections[]    = array( 'offset' => $offset, 'cta' => $condition_ctas[0] );
                        $claimed_offsets[] = $offset;
                    }
                    break;
            }
        }

        return $injections;
    }

    /**
     * Find insertion offsets after each H2 section.
     * Returns positions just before the next heading tag.
     */
    private function find_h2_offsets( $content ) {
        $offsets = array();

        // Find all heading positions
        if ( ! preg_match_all( '/<h[2-6][^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $offsets;
        }

        $headings = $matches[0];

        for ( $i = 0; $i < count( $headings ); $i++ ) {
            $tag = strtolower( substr( $headings[ $i ][0], 0, 3 ) );

            // Only process H2 headings
            if ( '<h2' !== $tag ) {
                continue;
            }

            // Insertion point: after the last </p> in this section
            $search_start = $headings[ $i ][1];

            if ( isset( $headings[ $i + 1 ] ) ) {
                $search_end = $headings[ $i + 1 ][1];
            } else {
                // Last H2 — section extends to end of content
                $search_end = strlen( $content );
            }

            $section = substr( $content, $search_start, $search_end - $search_start );
            $last_p  = strrpos( $section, '</p>' );

            if ( false !== $last_p ) {
                $offsets[] = $search_start + $last_p + 4;
            } else {
                $offsets[] = $search_end;
            }
        }

        return $offsets;
    }

    /**
     * Find the nearest </p> position at or after a percentage of the content.
     */
    private function find_percent_offset( $content, $plain_text, $total_length, $percent ) {
        if ( $total_length < 100 ) {
            return false;
        }

        $target_chars = (int) ( $total_length * $percent / 100 );

        // Find all </p> positions and pick the one closest to the target percentage
        $offset    = 0;
        $best_pos  = false;
        $char_seen = 0;

        while ( ( $p_pos = strpos( $content, '</p>', $offset ) ) !== false ) {
            // Count plain text chars up to this </p> by stripping tags from content so far
            $segment   = substr( $content, 0, $p_pos );
            $char_seen = mb_strlen( wp_strip_all_tags( $segment ), 'UTF-8' );

            if ( $char_seen >= $target_chars ) {
                $best_pos = $p_pos + 4; // after </p>
                break;
            }

            $offset = $p_pos + 4;
        }

        return $best_pos;
    }

    /**
     * Check if an offset is too close to an already claimed offset.
     */
    private function is_offset_claimed( $offset, $claimed_offsets, $threshold = 200 ) {
        foreach ( $claimed_offsets as $claimed ) {
            if ( abs( $offset - $claimed ) < $threshold ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render a single CTA block HTML.
     */
    private function render_cta( $cta ) {
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
