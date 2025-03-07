<?php

namespace AJ_SOCIAL\Includes\Controller\Admin;

if (! defined('ABSPATH')) {
    exit; // Direktzugriff verhindern
}

class AdminController
{
    public function __construct()
    {
        add_filter('woocommerce_product_data_tabs', [$this, 'aj_add_social_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'aj_add_social_tab_content']);
        add_action('woocommerce_process_product_meta', [$this, 'aj_save_social_fields']);
        add_filter('woocommerce_hidden_order_itemmeta', function ($hidden_meta) {
            $hidden_meta[] = '_aj_social_design_data'; // Unterdrücke die Anzeige
            return $hidden_meta;
        });

        add_action('woocommerce_after_order_itemmeta', [$this, 'aj_social_display_custom_fields_in_admin_order'], 10, 3);
    }

    public function aj_add_social_tab($tabs)
    {
        $tabs['aj_social'] = array(
            'label'    => __('AJ SOCIAL', AJ_SOCIAL_TEXTDOMAIN),
            'target'   => 'aj_social_data',
            'class'    => array(),
            'priority' => 21,
        );
        return $tabs;
    }


    public function aj_add_social_tab_content()
    {
        global $post;

        // Hole bestehende Werte
        $enabled = get_post_meta($post->ID, '_aj_social_enabled', true);
        $iconsPath = get_post_meta($post->ID, '_aj_social_icons_path', true);

        $socialSettings = get_post_meta($post->ID, '_aj_social_icon_settings', true); // Einstellungen als Array
        $socialSettings = is_array($socialSettings) ? $socialSettings : []; // Sicherstellen, dass es ein Array ist
    
        // Standardwerte, falls keine vorhanden
        $defaultSettings = [
            'iconsPath' => '',
            'fontsize' => '',
            'spacing' => '',
            'iconwidth' => '',
            'iconheight' => '',
        ];
        $socialSettings = wp_parse_args($socialSettings, $defaultSettings);

?>
        <div id="aj_social_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // Checkbox
                woocommerce_wp_checkbox(
                    array(
                        'id'    => '_aj_social_enabled',
                        'label' => __('Enable AJ SOCIAL', AJ_SOCIAL_TEXTDOMAIN),
                        'desc_tip' => true,
                        'description' => __('Activate AJ SOCIAL for this product.', AJ_SOCIAL_TEXTDOMAIN),
                        'value' => $enabled === 'yes' ? 'yes' : 'no'
                    )
                );

                // Textfeld für Dateiformate
                woocommerce_wp_text_input(
                    array(
                        'id'          => '_aj_social_icons_path',
                        'label'       => __('SVG NAME', AJ_SOCIAL_TEXTDOMAIN),
                        'desc_tip'    => true,
                        'description' => __('Nur den Svg namen, die Datei muss sich im Plugin Ordner svg befinden. OHNE .svg!', AJ_SOCIAL_TEXTDOMAIN),
                        'value'       => $iconsPath,
                        'placeholder' => 'instagram-icon',
                    )
                );

                woocommerce_wp_text_input(
                    array(
                        'id'          => '_aj_social_icon_settings_fontsize',
                        'label'       => __('Font Size', AJ_SOCIAL_TEXTDOMAIN),
                        'desc_tip'    => true,
                        'description' => __('Größe der Schriftart in Pixel.', AJ_SOCIAL_TEXTDOMAIN),
                        'value'       => esc_attr($socialSettings['fontsize']),
                        'placeholder' => '100',
                    )
                );
    
                woocommerce_wp_text_input(
                    array(
                        'id'          => '_aj_social_icon_settings_spacing',
                        'label'       => __('Spacing', AJ_SOCIAL_TEXTDOMAIN),
                        'desc_tip'    => true,
                        'description' => __('Abstand zwischen Icon und Text.', AJ_SOCIAL_TEXTDOMAIN),
                        'value'       => esc_attr($socialSettings['spacing']),
                        'placeholder' => '10',
                    )
                );
    
                woocommerce_wp_text_input(
                    array(
                        'id'          => '_aj_social_icon_settings_iconwidth',
                        'label'       => __('Icon Width', AJ_SOCIAL_TEXTDOMAIN),
                        'desc_tip'    => true,
                        'description' => __('Breite des Icons in Pixel.', AJ_SOCIAL_TEXTDOMAIN),
                        'value'       => esc_attr($socialSettings['iconwidth']),
                        'placeholder' => '115',
                    )
                );
    
                woocommerce_wp_text_input(
                    array(
                        'id'          => '_aj_social_icon_settings_iconheight',
                        'label'       => __('Icon Height', AJ_SOCIAL_TEXTDOMAIN),
                        'desc_tip'    => true,
                        'description' => __('Höhe des Icons in Pixel.', AJ_SOCIAL_TEXTDOMAIN),
                        'value'       => esc_attr($socialSettings['iconheight']),
                        'placeholder' => '115',
                    )
                );
                ?>
            </div>
        </div>
<?php
    }

    // Daten speichern

    public function aj_save_social_fields($post_id)
    {
        // Checkbox-Wert speichern
        $enabled = isset($_POST['_aj_social_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_aj_social_enabled', sanitize_text_field($enabled));

        // Dateiformate speichern
        if (isset($_POST['_aj_social_icons_path'])) {
            $iconsPath = sanitize_text_field($_POST['_aj_social_icons_path']);
            update_post_meta($post_id, '_aj_social_icons_path', $iconsPath);
        }

        $socialSettings = [
            'fontsize'   => intval($_POST['_aj_social_icon_settings_fontsize'] ?? 0),
            'spacing'    => intval($_POST['_aj_social_icon_settings_spacing'] ?? 0),
            'iconwidth'  => intval($_POST['_aj_social_icon_settings_iconwidth'] ?? 0),
            'iconheight' => intval($_POST['_aj_social_icon_settings_iconheight'] ?? 0),
        ];
    
        // Speichern der sozialen Einstellungen als Array
        update_post_meta($post_id, '_aj_social_icon_settings', $socialSettings);
    }

    public function aj_social_display_custom_fields_in_admin_order($item_id, $item, $order)
    {
        // Überprüfen, ob die benutzerdefinierten Daten vorhanden sind
        $custom_data = $item->get_meta('_aj_social_design_data', true);


        if ($custom_data) {
            // Die serialisierten Daten de-serialisieren
            $custom_data = unserialize($custom_data);

            // Daten anzeigen
            echo '<p><strong>Text:</strong> ' . esc_html($custom_data['text']) . '</p>';
            echo '<p><strong>Höhe:</strong> ' . esc_html($custom_data['height']) . ' cm</p>';
            echo '<p><strong>Breite:</strong> ' . esc_html($custom_data['width']) . ' cm</p>';
            echo '<p><strong>Oberfläche:</strong> ' . esc_html($custom_data['surface']) . '</p>';
            echo '<p><strong>Farbe:</strong> ' . esc_html($custom_data['color']) . '</p>';
            echo '<p><strong>font:</strong> ' . esc_html($custom_data['font']) . '</p>';
        }
    }
    
}