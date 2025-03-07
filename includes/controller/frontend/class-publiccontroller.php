<?php

namespace AJ_SOCIAL\Includes\Controller\Frontend;

if (! defined('ABSPATH')) {
    exit; // Direktzugriff verhindern
}

class PublicController
{
    public function __construct()
    {
        
        add_action('wp_enqueue_scripts', [$this, 'aj_social_register_assets']);
        add_action('woocommerce_order_item_meta_end', [$this, 'aj_social_display_custom_fields_in_order_table'], 10, 4);
        new ProductController();
    }

    public function aj_social_display_custom_fields_in_order_table($item_id, $item, $order, $plain_text)
    {
        // Hole die benutzerdefinierten Daten
        $custom_data = $item->get_meta('_aj_social_design_data', true);

        // Überprüfen, ob die Daten existieren
        if ($custom_data) {
            $custom_data = maybe_unserialize($custom_data); // Daten ent-serialisieren

            if (is_array($custom_data)) {
                // Daten formatieren und anzeigen
                echo '<p><strong>Wunschtext:</strong> ' . esc_html($custom_data['text']) . '</p>';
                echo '<p><strong>Breite:</strong> ' . esc_html($custom_data['width']) . ' cm</p>';
                echo '<p><strong>Oberfläche:</strong> ' . esc_html($custom_data['surface']) . '</p>';
                echo '<p><strong>Farbe:</strong> ' . esc_html($custom_data['color']) . '</p>';
            }
        }
    }


    public function aj_social_register_assets()
    {

        $url =  trailingslashit(ajsocial_get_setting('url')) . 'assets/';
        $version = ajsocial_get_setting('version');
        wp_enqueue_script('jquery');

        wp_enqueue_style(
            'aj-social-for-woo-style', // Handle des Styles
            $url . 'css/aj-social-style.css', // Pfad zur CSS-Datei
            array(), // Abhängigkeiten
            $version // Version des Styles
        );

        wp_enqueue_script(
            'aj-social-for-woo-script', // Handle des Skripts
            $url . 'js/aj-social-frontend.js', // Pfad zur JS-Datei
            array('jquery', 'fabricjs', 'openjs', 'texttosvg'), // Abhängigkeiten (z.B. jQuery)
            $version, // Version des Skripts
            true // Das Skript wird im Footer geladen
        );
    }
}