<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$c = wp_parse_args( $config, array(
    'enabled'      => 0,
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
?>

<h2>Pływający pasek</h2>

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
            <th><label for="fb_heading">Nagłówek</label></th>
            <td><input type="text" name="fb_heading" id="fb_heading" class="large-text" value="<?php echo esc_attr( $c['heading'] ); ?>"></td>
        </tr>
        <tr>
            <th><label for="fb_body">Treść</label></th>
            <td><textarea name="fb_body" id="fb_body" class="large-text" rows="3"><?php echo esc_textarea( $c['body'] ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="fb_button_text">Przycisk</label></th>
            <td>
                <input type="text" name="fb_button_text" id="fb_button_text" class="regular-text" value="<?php echo esc_attr( $c['button_text'] ); ?>" placeholder="Tekst przycisku">
                <input type="url" name="fb_button_url" id="fb_button_url" class="regular-text" value="<?php echo esc_attr( $c['button_url'] ); ?>" placeholder="https://..." style="margin-top:5px;display:block;">
            </td>
        </tr>
        <tr>
            <th>Kolory</th>
            <td>
                <div class="blm-colors-grid">
                    <div>
                        <label for="fb_bg_color">Tło</label><br>
                        <input type="text" name="fb_bg_color" id="fb_bg_color" class="blm-color-picker" value="<?php echo esc_attr( $c['bg_color'] ); ?>">
                    </div>
                    <div>
                        <label for="fb_button_color">Przycisk</label><br>
                        <input type="text" name="fb_button_color" id="fb_button_color" class="blm-color-picker" value="<?php echo esc_attr( $c['button_color'] ); ?>">
                    </div>
                    <div>
                        <label for="fb_text_color">Tekst</label><br>
                        <input type="text" name="fb_text_color" id="fb_text_color" class="blm-color-picker" value="<?php echo esc_attr( $c['text_color'] ); ?>">
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="fb_position">Pozycja</label></th>
            <td>
                <select name="fb_position" id="fb_position">
                    <option value="bottom" <?php selected( $c['position'], 'bottom' ); ?>>Na dole ekranu</option>
                    <option value="top" <?php selected( $c['position'], 'top' ); ?>>Na górze ekranu</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="fb_show_delay">Opóźnienie (sekundy)</label></th>
            <td>
                <input type="number" name="fb_show_delay" id="fb_show_delay" class="small-text" value="<?php echo esc_attr( $c['show_delay'] ); ?>" min="0" max="60">
                <p class="description">Po ilu sekundach od załadowania strony pasek się pojawi.</p>
            </td>
        </tr>
        <tr>
            <th><label for="fb_dismissible">Zamykalny</label></th>
            <td>
                <label>
                    <input type="checkbox" name="fb_dismissible" id="fb_dismissible" value="1" <?php checked( $c['dismissible'], 1 ); ?>>
                    Użytkownik może zamknąć pasek (X)
                </label>
            </td>
        </tr>
    </table>

    <p class="submit">
        <input type="submit" name="blm_floating_bar_save" class="button button-primary" value="Zapisz ustawienia">
    </p>
</form>
