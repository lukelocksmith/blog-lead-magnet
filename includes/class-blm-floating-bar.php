<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Floating_Bar {

    public function __construct() {
        add_action( 'wp_footer', array( $this, 'render' ), 5 );
        add_filter( 'the_content', array( $this, 'inject_toc' ), 5 );
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

    public static function get() {
        return array_merge( self::defaults(), get_option( 'blm_floating_bar', array() ) );
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

        $i         = 0;
        $toc_items = array();

        $content = preg_replace_callback( '/<(h[23])([^>]*)>(.*?)<\/\1>/is', function ( $m ) use ( &$i, &$toc_items ) {
            $tag   = $m[1];
            $attrs = $m[2];
            $text  = strip_tags( $m[3] );
            $id    = 'h-' . $i;

            if ( ! preg_match( '/\bid\s*=/i', $attrs ) ) {
                $attrs .= ' id="' . $id . '"';
            } else {
                preg_match( '/\bid\s*=\s*["\']([^"\']+)/i', $attrs, $id_match );
                if ( $id_match ) {
                    $id = $id_match[1];
                }
            }

            $toc_items[] = array(
                'id'    => $id,
                'text'  => $text,
                'level' => $tag === 'h3' ? 3 : 2,
            );

            $i++;
            return "<{$tag}{$attrs}>{$m[3]}</{$tag}>";
        }, $content );

        // Hidden nav for Google/AI crawlers (schema.org SiteNavigationElement)
        $toc_html = '<nav class="blm-toc-seo" aria-label="Spis treści" itemscope itemtype="https://schema.org/SiteNavigationElement">';
        $toc_html .= '<ol>';
        foreach ( $toc_items as $item ) {
            $sub = $item['level'] === 3 ? ' class="toc-sub"' : '';
            $toc_html .= '<li' . $sub . '><a href="#' . esc_attr( $item['id'] ) . '" itemprop="url"><span itemprop="name">' . esc_html( $item['text'] ) . '</span></a></li>';
        }
        $toc_html .= '</ol></nav>';

        return $toc_html . $content;
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
