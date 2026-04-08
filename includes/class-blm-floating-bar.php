<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Floating_Bar {

    public function __construct() {
        add_action( 'wp_footer', array( $this, 'render' ) );
    }

    public function render() {
        if ( ! is_singular( 'post' ) ) {
            return;
        }

        $config = get_option( 'blm_floating_bar', array() );

        if ( empty( $config['enabled'] ) ) {
            return;
        }

        $c = wp_parse_args( $config, array(
            'heading'      => '',
            'body'         => '',
            'button_text'  => '',
            'button_url'   => '',
            'bg_color'     => '#1e293b',
            'button_color' => '#2563eb',
            'text_color'   => '#ffffff',
            'position'     => 'bottom',
            'show_delay'   => 3,
            'dismissible'  => 1,
        ) );

        $bar_style = sprintf(
            'background-color:%s;color:%s;',
            esc_attr( $c['bg_color'] ),
            esc_attr( $c['text_color'] )
        );

        $position_class = 'bottom' === $c['position'] ? 'blm-floating-bar--bottom' : 'blm-floating-bar--top';
        ?>
        <div class="blm-floating-bar <?php echo esc_attr( $position_class ); ?>"
             style="<?php echo esc_attr( $bar_style ); ?>"
             data-delay="<?php echo esc_attr( $c['show_delay'] ); ?>">

            <div class="blm-floating-bar__text">
                <?php if ( $c['heading'] ) : ?>
                    <div class="blm-floating-bar__heading"><?php echo esc_html( $c['heading'] ); ?></div>
                <?php endif; ?>
                <?php if ( $c['body'] ) : ?>
                    <div class="blm-floating-bar__body"><?php echo wp_kses_post( $c['body'] ); ?></div>
                <?php endif; ?>
            </div>

            <?php if ( $c['button_text'] && $c['button_url'] ) : ?>
                <a href="<?php echo esc_url( $c['button_url'] ); ?>"
                   class="blm-floating-bar__button"
                   style="background-color:<?php echo esc_attr( $c['button_color'] ); ?>;">
                    <?php echo esc_html( $c['button_text'] ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $c['dismissible'] ) : ?>
                <button class="blm-floating-bar__dismiss" aria-label="Zamknij">&times;</button>
            <?php endif; ?>
        </div>
        <?php
    }
}
