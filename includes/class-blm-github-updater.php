<?php
/**
 * GitHub Updater — automatyczne aktualizacje wtyczki z GitHub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BLM_GitHub_Updater {

    private $github_user = 'lukelocksmith';
    private $github_repo = 'blog-lead-magnet';
    private $plugin_basename;
    private $current_version;
    private $plugin_slug;
    private $github_response = null;

    public function __construct() {
        $this->plugin_basename = BLM_PLUGIN_BASENAME;
        $this->plugin_slug     = dirname( $this->plugin_basename );
        $this->current_version = BLM_VERSION;

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
        add_action( 'admin_init', array( $this, 'maybe_clear_cache' ) );
    }

    private function get_github_data() {
        if ( null !== $this->github_response ) {
            return $this->github_response;
        }

        $cached = get_transient( 'blm_github_update_data' );
        if ( false !== $cached ) {
            if ( 'no_data' === $cached || ! is_object( $cached ) || ! isset( $cached->tag_name ) ) {
                $this->github_response = false;
                return false;
            }
            $this->github_response = $cached;
            return $cached;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_user,
            $this->github_repo
        );

        $args = array(
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
            ),
            'timeout' => 15,
        );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            // Fallback: tags API
            $url = sprintf(
                'https://api.github.com/repos/%s/%s/tags',
                $this->github_user,
                $this->github_repo
            );

            $response = wp_remote_get( $url, $args );

            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                $this->github_response = false;
                set_transient( 'blm_github_update_data', 'no_data', HOUR_IN_SECONDS );
                return false;
            }

            $tags = json_decode( wp_remote_retrieve_body( $response ) );
            if ( empty( $tags ) || ! is_array( $tags ) ) {
                $this->github_response = false;
                set_transient( 'blm_github_update_data', 'no_data', HOUR_IN_SECONDS );
                return false;
            }

            $latest_tag = $tags[0];
            $data = (object) array(
                'tag_name'     => $latest_tag->name,
                'body'         => '',
                'published_at' => '',
                'zipball_url'  => $latest_tag->zipball_url,
            );
        } else {
            $data = json_decode( wp_remote_retrieve_body( $response ) );
        }

        if ( empty( $data ) || ! isset( $data->tag_name ) ) {
            $this->github_response = false;
            set_transient( 'blm_github_update_data', 'no_data', HOUR_IN_SECONDS );
            return false;
        }

        $this->github_response = $data;
        set_transient( 'blm_github_update_data', $data, 6 * HOUR_IN_SECONDS );

        return $data;
    }

    private function normalize_version( $version ) {
        return ltrim( $version, 'vV' );
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $github_data = $this->get_github_data();
        if ( false === $github_data ) {
            return $transient;
        }

        $remote_version = $this->normalize_version( $github_data->tag_name );

        if ( version_compare( $remote_version, $this->current_version, '>' ) ) {
            $download_url = $this->get_download_url( $github_data );

            $transient->response[ $this->plugin_basename ] = (object) array(
                'slug'         => $this->plugin_slug,
                'plugin'       => $this->plugin_basename,
                'new_version'  => $remote_version,
                'url'          => sprintf( 'https://github.com/%s/%s', $this->github_user, $this->github_repo ),
                'package'      => $download_url,
                'icons'        => array(),
                'banners'      => array(),
                'tested'       => '',
                'requires'     => '5.8',
                'requires_php' => '7.4',
            );
        }

        return $transient;
    }

    private function get_download_url( $github_data ) {
        $url = isset( $github_data->zipball_url ) ? $github_data->zipball_url : '';

        if ( ! empty( $github_data->assets ) && is_array( $github_data->assets ) ) {
            foreach ( $github_data->assets as $asset ) {
                if ( isset( $asset->browser_download_url ) && substr( $asset->name, -4 ) === '.zip' ) {
                    $url = $asset->browser_download_url;
                    break;
                }
            }
        }

        return $url;
    }

    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $github_data = $this->get_github_data();
        if ( false === $github_data ) {
            return $result;
        }

        $remote_version = $this->normalize_version( $github_data->tag_name );

        return (object) array(
            'name'              => 'Blog Lead Magnet',
            'slug'              => $this->plugin_slug,
            'version'           => $remote_version,
            'author'            => '<a href="https://important.is">important.is</a>',
            'homepage'          => sprintf( 'https://github.com/%s/%s', $this->github_user, $this->github_repo ),
            'short_description' => 'Flexible CTA system for blog posts with analytics and floating bar.',
            'sections'          => array(
                'description' => 'Elastyczny system CTA do wpisów blogowych z analityką i pływającym paskiem.',
                'changelog'   => $this->format_changelog( $github_data ),
            ),
            'download_link'     => $this->get_download_url( $github_data ),
            'requires'          => '5.8',
            'requires_php'      => '7.4',
            'tested'            => '',
            'last_updated'      => isset( $github_data->published_at ) ? $github_data->published_at : '',
        );
    }

    private function format_changelog( $github_data ) {
        $body = isset( $github_data->body ) ? $github_data->body : '';

        if ( empty( $body ) ) {
            return '<p>Brak informacji o zmianach.</p>';
        }

        $html = nl2br( esc_html( $body ) );
        $html = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $html );
        $html = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html );

        return $html;
    }

    public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
        global $wp_filesystem;

        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
            return $source;
        }

        $correct_source = trailingslashit( $remote_source ) . trailingslashit( $this->plugin_slug );

        if ( trailingslashit( $source ) === $correct_source ) {
            return $source;
        }

        if ( $wp_filesystem->move( $source, $correct_source ) ) {
            return $correct_source;
        }

        return $source;
    }

    public function maybe_clear_cache() {
        if ( isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) {
            delete_transient( 'blm_github_update_data' );
        }
    }
}
