<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Floating_Bar {

    public function __construct() {
        add_action( 'wp_footer', array( $this, 'render' ), 5 );
        add_filter( 'the_content', array( $this, 'inject_toc' ), 5 );
        add_action( 'template_redirect', array( $this, 'start_toc_schema_buffer' ), 20 );
    }

    /**
     * Structured data for the table of contents (schema.org SiteNavigationElement, JSON-LD).
     *
     * There is ONE table of contents: the visible Bricks post TOC (.brxe-post-toc). The schema is
     * derived from exactly that element's configuration (data-content-selector / data-heading-selectors),
     * read from the final page HTML, so the markup shown to users and the data given to crawlers
     * always match (including headings rendered by Bricks modules, e.g. FAQ, which are not in post_content).
     */
    public function start_toc_schema_buffer() {
        if ( ! is_singular( 'post' ) || is_admin() || is_feed() || is_preview() ) {
            return;
        }

        $permalink = get_permalink( get_queried_object_id() );
        if ( ! $permalink ) {
            return;
        }

        ob_start( function ( $html ) use ( $permalink ) {
            return BLM_Floating_Bar::append_toc_schema( $html, $permalink );
        } );
    }

    /**
     * Turn a simple CSS selector (tag, .class, #id) into an XPath expression.
     * Returns null for anything more complex, so callers can fall back to a default.
     */
    private static function simple_selector_to_xpath( $selector ) {
        $selector = trim( $selector );
        if ( preg_match( '/^[a-z][a-z0-9]*$/i', $selector ) ) {
            return strtolower( $selector );
        }
        if ( preg_match( '/^\.([\w-]+)$/', $selector, $m ) ) {
            return "*[contains(concat(' ', normalize-space(@class), ' '), ' " . $m[1] . " ')]";
        }
        if ( preg_match( '/^#([\w-]+)$/', $selector, $m ) ) {
            return "*[@id='" . $m[1] . "']";
        }
        return null;
    }

    /**
     * Build the JSON-LD <script> for the TOC from the final page HTML and insert it before </body>.
     * Returns the HTML unchanged when there is no visible TOC or fewer than 2 entries.
     */
    public static function append_toc_schema( $html, $permalink ) {
        if ( ! is_string( $html ) || strpos( $html, 'brxe-post-toc' ) === false || ! class_exists( 'DOMDocument' ) ) {
            return $html;
        }

        $prev = libxml_use_internal_errors( true );
        $dom  = new DOMDocument();
        $ok   = $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET | LIBXML_COMPACT );
        libxml_clear_errors();
        libxml_use_internal_errors( $prev );
        if ( ! $ok ) {
            return $html;
        }

        $xp  = new DOMXPath( $dom );
        $toc = $xp->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' brxe-post-toc ')]" )->item( 0 );
        if ( ! $toc ) {
            return $html;
        }

        $content_xp = self::simple_selector_to_xpath( $toc->getAttribute( 'data-content-selector' ) );
        $content_xp = $content_xp ? $content_xp : self::simple_selector_to_xpath( '.blog-content' );

        $tags = array();
        foreach ( explode( ',', $toc->getAttribute( 'data-heading-selectors' ) ) as $sel ) {
            $t = self::simple_selector_to_xpath( $sel );
            if ( $t && preg_match( '/^h[1-6]$/', $t ) ) {
                $tags[] = $t;
            }
        }
        $tags = $tags ? $tags : array( 'h2' );

        $root = $xp->query( '//' . $content_xp )->item( 0 );
        if ( ! $root ) {
            return $html;
        }

        $conds    = array();
        foreach ( $tags as $t ) {
            $conds[] = 'self::' . $t;
        }
        $items = array();
        foreach ( $xp->query( './/*[' . implode( ' or ', $conds ) . ']', $root ) as $h ) {
            $id   = $h->getAttribute( 'id' );
            $text = trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', $h->textContent ) );
            if ( $id === '' || $text === '' ) {
                continue;
            }
            $items[] = array(
                '@type' => 'SiteNavigationElement',
                'name'  => $text,
                'url'   => $permalink . '#' . $id,
            );
        }

        if ( count( $items ) < 2 ) {
            return $html;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'SiteNavigationElement',
            '@id'      => $permalink . '#toc',
            'name'     => 'Spis treści',
            'isPartOf' => array( '@id' => $permalink . '#webpage' ),
            'hasPart'  => $items,
        );

        $script = "\n<script type=\"application/ld+json\" class=\"blm-toc-schema\">\n"
            . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG )
            . "\n</script>\n";

        $pos = strripos( $html, '</body>' );
        return $pos === false ? $html . $script : substr( $html, 0, $pos ) . $script . substr( $html, $pos );
    }

    public static function defaults() {
        return array(
            'enabled'        => 0,
            'mode'           => 'both', // 'both', 'cta_only', 'toc_only'
            'progress_bar'   => 1,
            'btn_text'       => 'Umów się',
            'btn_url'        => '',
            'author_name'    => '',
            'author_role'    => '',
            'author_avatar'  => '',
            'bar_bg'         => '#ffffff',
            'btn_color'      => '#2563eb',
            'progress_color' => '#e22007',
        );
    }

    private static $config_cache = null;

    public static function get() {
        if ( null === self::$config_cache ) {
            self::$config_cache = array_merge( self::defaults(), get_option( 'blm_floating_bar', array() ) );
        }
        return self::$config_cache;
    }

    /**
     * Server-side TOC injection for SEO/AI visibility.
     * Adds hidden <nav> with schema.org markup + anchor IDs to headings.
     */
    public function inject_toc( $content ) {
        if ( ! is_singular( 'post' ) || is_admin() ) {
            return $content;
        }

        $d = self::get();
        if ( ! $d['enabled'] ) {
            return $content;
        }

        $show_toc = in_array( $d['mode'], array( 'both', 'toc_only' ), true );
        if ( ! $show_toc ) {
            return $content;
        }

        if ( ! preg_match_all( '/<(h[23])[^>]*>(.*?)<\/\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
            return $content;
        }

        if ( count( $matches ) < 2 ) {
            return $content;
        }

        $i = 0;

        $content = preg_replace_callback( '/<(h[23])([^>]*)>(.*?)<\/\1>/is', function ( $m ) use ( &$i ) {
            $tag   = $m[1];
            $attrs = $m[2];
            $id    = 'h-' . $i;

            // Real id attribute only: \bid also matched data-section-id (ChatGPT paste leftovers) and skipped assigning an anchor.
            if ( ! preg_match( '/(?<![\w-])id\s*=/i', $attrs ) ) {
                $attrs .= ' id="' . $id . '"';
            }

            $i++;
            return "<{$tag}{$attrs}>{$m[3]}</{$tag}>";
        }, $content );

        // Headings only get stable ids (anchor targets for the floating bar TOC).
        // No hidden nav: the TOC is built client-side from the same headings
        // as the visible Bricks post TOC, so users and crawlers get one TOC.
        return $content;
    }

    /**
     * Render floating bar in footer.
     */
    public function render() {
        if ( ! is_singular( 'post' ) || is_admin() ) {
            return;
        }

        $d = self::get();
        if ( ! $d['enabled'] ) {
            return;
        }

        $show_cta = in_array( $d['mode'], array( 'both', 'cta_only' ), true );
        $show_toc = in_array( $d['mode'], array( 'both', 'toc_only' ), true );

        $bar_style = sprintf( 'background:%s; --blm-bar-bg:%s;', esc_attr( $d['bar_bg'] ), esc_attr( $d['bar_bg'] ) );
        $btn_style = sprintf( 'background:%s;', esc_attr( $d['btn_color'] ) );

        $initials = '';
        foreach ( explode( ' ', $d['author_name'] ) as $part ) {
            if ( $part ) {
                $initials .= mb_substr( $part, 0, 1 );
            }
        }

        // Progress bar
        if ( ! empty( $d['progress_bar'] ) ) : ?>
        <div class="blm-progress" id="blm-progress" aria-hidden="true">
            <div class="blm-progress__bar" id="blm-progress-bar" style="background:<?php echo esc_attr( $d['progress_color'] ); ?>"></div>
        </div>
        <?php endif; ?>

        <div class="blm-float" id="blm-float" aria-expanded="false" style="<?php echo esc_attr( $bar_style ); ?>">
            <div class="blm-float__bar">
                <div class="blm-float__inner">

                    <?php if ( $show_cta ) : ?>
                    <div class="blm-float__expert">
                        <div class="blm-float__avatar" aria-hidden="true">
                            <?php if ( $d['author_avatar'] ) : ?>
                                <img src="<?php echo esc_url( $d['author_avatar'] ); ?>" alt="<?php echo esc_attr( $d['author_name'] ); ?>">
                            <?php else : ?>
                                <?php echo esc_html( $initials ); ?>
                            <?php endif; ?>
                        </div>
                        <div class="blm-float__info">
                            <span class="blm-float__name"><?php echo esc_html( $d['author_name'] ); ?></span>
                            <span class="blm-float__role"><?php echo esc_html( $d['author_role'] ); ?></span>
                        </div>
                        <a href="<?php echo esc_url( $d['btn_url'] ); ?>" class="blm-float__btn" style="<?php echo esc_attr( $btn_style ); ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">
                            <?php echo esc_html( $d['btn_text'] ); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_cta && $show_toc ) : ?>
                    <div class="blm-float__sep" aria-hidden="true"></div>
                    <?php endif; ?>

                    <?php if ( $show_toc ) : ?>
                    <div class="blm-float__toc-area">
                        <div class="blm-float__panel">
                            <div class="blm-float__panel-inner">
                                <ol class="blm-float__toc-list" id="blm-toc-list"></ol>
                            </div>
                        </div>
                        <button class="blm-float__toc-toggle" onclick="toggleBlmFloat()" aria-label="Otwórz spis treści">
                            <div class="blm-float__toc-text">
                                <span class="blm-float__toc-label">Spis treści</span>
                                <span class="blm-float__toc-active" id="blm-toc-active">&mdash;</span>
                            </div>
                            <svg class="blm-float__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <polyline points="18 15 12 9 6 15"/>
                            </svg>
                        </button>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php
    }
}
