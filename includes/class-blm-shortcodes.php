<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BLM_Shortcodes {

    public function __construct() {
        add_shortcode( 'blm_product', array( $this, 'render_product' ) );
        add_shortcode( 'blm_related_posts', array( $this, 'render_related_posts' ) );
    }

    /**
     * Product card shortcode.
     *
     * Usage: [blm_product name="Nazwa" price="199 zł" url="https://..." image="https://..." description="Krótki opis" button="Kup teraz"]
     */
    public function render_product( $atts ) {
        $atts = shortcode_atts( array(
            'name'        => '',
            'price'       => '',
            'url'         => '#',
            'image'       => '',
            'description' => '',
            'button'      => 'Sprawdź',
        ), $atts, 'blm_product' );

        ob_start();
        ?>
        <div class="blm-product">
            <?php if ( $atts['image'] ) : ?>
                <div class="blm-product__image">
                    <img src="<?php echo esc_url( $atts['image'] ); ?>" alt="<?php echo esc_attr( $atts['name'] ); ?>" loading="lazy">
                </div>
            <?php endif; ?>
            <div class="blm-product__info">
                <?php if ( $atts['name'] ) : ?>
                    <div class="blm-product__name"><?php echo esc_html( $atts['name'] ); ?></div>
                <?php endif; ?>
                <?php if ( $atts['description'] ) : ?>
                    <div class="blm-product__desc"><?php echo esc_html( $atts['description'] ); ?></div>
                <?php endif; ?>
                <div class="blm-product__bottom">
                    <?php if ( $atts['price'] ) : ?>
                        <span class="blm-product__price"><?php echo esc_html( $atts['price'] ); ?></span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $atts['url'] ); ?>" class="blm-product__button">
                        <?php echo esc_html( $atts['button'] ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Related posts shortcode.
     *
     * Usage: [blm_related_posts count="3" heading="Przeczytaj również"]
     * Or with specific IDs: [blm_related_posts ids="12,15,18"]
     */
    public function render_related_posts( $atts ) {
        $atts = shortcode_atts( array(
            'count'   => 3,
            'heading' => 'Przeczytaj również',
            'ids'     => '',
        ), $atts, 'blm_related_posts' );

        if ( $atts['ids'] ) {
            $post_ids = array_map( 'absint', explode( ',', $atts['ids'] ) );
            $posts = get_posts( array(
                'post__in'       => $post_ids,
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'orderby'        => 'post__in',
                'posts_per_page' => count( $post_ids ),
            ) );
        } else {
            // Get related posts from same category
            $categories = get_the_category();
            $cat_ids    = $categories ? wp_list_pluck( $categories, 'term_id' ) : array();

            $args = array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => absint( $atts['count'] ),
                'post__not_in'   => array( get_the_ID() ),
                'orderby'        => 'rand',
            );

            if ( $cat_ids ) {
                $args['category__in'] = $cat_ids;
            }

            $posts = get_posts( $args );
        }

        if ( empty( $posts ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="blm-related">
            <?php if ( $atts['heading'] ) : ?>
                <div class="blm-related__heading"><?php echo esc_html( $atts['heading'] ); ?></div>
            <?php endif; ?>
            <div class="blm-related__list">
                <?php foreach ( $posts as $p ) :
                    $thumb = get_the_post_thumbnail_url( $p->ID, 'medium' );
                ?>
                    <a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="blm-related__item">
                        <?php if ( $thumb ) : ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $p->post_title ); ?>" class="blm-related__thumb" loading="lazy">
                        <?php else : ?>
                            <div class="blm-related__thumb blm-related__thumb--empty"></div>
                        <?php endif; ?>
                        <span class="blm-related__title"><?php echo esc_html( $p->post_title ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
