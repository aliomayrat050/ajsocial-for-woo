<?php

namespace AJ_SOCIAL\Includes\Controller\Frontend;

if (! defined('ABSPATH')) {
    exit; // Direktzugriff verhindern
}

use AJ_SOCIAL\Includes\Classes\Helper;
use AJ_SOCIAL\Includes\Classes\Render;

class ProductController
{

    private $required_fields = [
        'aj-social-text',
        'aj-social-height',
        'aj-social-width',
        'aj-social-surface',
        'aj-social-color',
        'aj-social-font',
    ];

    public function __construct()
    {
        add_action('woocommerce_before_add_to_cart_button', [$this, 'aj_social_fields_before_add_to_cart_button'], 10);
        add_filter('woocommerce_add_cart_item_data', [$this, 'aj_social_add_custom_fields_to_cart'], 10, 2);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'aj_social_validate_custom_fields'], 20, 3);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'aj_social_save_custom_fields_in_order'], 10, 4);
        add_filter('woocommerce_get_item_data', [$this, 'aj_social_display_custom_fields_in_cart'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'aj_social_adjust_cart_item_pricing']);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'aj_social_adjust_cart_item_pricing_on_session'], 10, 3);
        add_action('woocommerce_after_add_to_cart_form', [$this, 'aj_social_discount_table'], 10);
    }


    private function are_required_fields_set($data)
    {
        foreach ($this->required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false; // Ein erforderliches Feld fehlt
            }
        }
        return true; // Alle erforderlichen Felder sind vorhanden
    }

    public function aj_social_fields_before_add_to_cart_button()
    {
        global $product;
        $product_id = $product->get_id();

        $pricedata = Helper::get_price($product_id);
        $iconName = Helper::get_icon_path($product_id);
        $iconSettings = Helper::get_icon_settings($product_id);
        $colors = get_option('aj_vinyl_colorsdata', serialize([]));
        $colors = unserialize($colors);

        // Farb- und Finish-Daten als JSON codieren, damit sie in JavaScript verwendet werden können
        $colors_json = json_encode($colors);
        $fonts = get_option('aj_vinyl_fontsdata', serialize([]));
        $fonts = unserialize($fonts);

        $discountdata_jeson = json_encode(Helper::getDiscountRule());


        if (Helper::is_social_enabled($product_id)) {
            echo Render::view('productfields', [
                'widthdata' => $pricedata,
                'colors_json' => $colors_json,
                'fontdata' => $fonts,
                'icon_path' => $iconName,
                'iconSettings' => $iconSettings,
                'discountData' => $discountdata_jeson

            ]);
        }
    }

    public function aj_social_add_custom_fields_to_cart($cart_item_data, $product_id)
    {

        if (!Helper::is_social_enabled($product_id)) {
            return $cart_item_data; // Produkt nicht aktiv, daher keine Validierung nötig
        }
        if (!$this->are_required_fields_set($_POST)) {
            return $cart_item_data; // Rückgabe, wenn nicht alle erforderlichen Felder vorhanden sind
        }

        $cart_item_data['aj_social_text'] = sanitize_text_field($_POST['aj-social-text']);
        $cart_item_data['aj_social_height'] = floatval($_POST['aj-social-height']);
        $cart_item_data['aj_social_width'] = floatval($_POST['aj-social-width']);
        $cart_item_data['aj_social_surface'] = sanitize_text_field($_POST['aj-social-surface']);
        $cart_item_data['aj_social_color'] = sanitize_text_field($_POST['aj-social-color']);
        $cart_item_data['aj_social_font'] = sanitize_text_field($_POST['aj-social-font']);
        $cart_item_data['aj_social_price'] = Helper::calcPrice($cart_item_data['aj_social_width'], $product_id);


        // Sicherstellen, dass die Daten im Warenkorb beibehalten werden
        $cart_item_data['aj_social_unique_key'] = uniqid($product_id . '_', true);

        return $cart_item_data;
    }

    public function aj_social_validate_custom_fields($passed, $product_id, $quantity)
    {
        // Überprüfen, ob das Produkt für das Plugin aktiv ist
        if (!Helper::is_social_enabled($product_id)) {
            return $passed; // Keine Validierung nötig
        }

        // Überprüfen, ob alle erforderlichen Felder ausgefüllt sind
        if (!$this->are_required_fields_set($_POST)) {
            wc_add_notice(__('Bitte füllen Sie alle erforderlichen Felder aus.', AJ_SOCIAL_TEXTDOMAIN), 'error');
            return false;
        }

        // Höhe validieren
        $ajuploadheight = $_POST['aj-social-height'] ?? 0;
        $ajuploadwidth = $_POST['aj-social-width'] ?? 0;

        if ($ajuploadheight < 2.60) {
            wc_add_notice(__('Die Höhe muss mindestens 2,60 cm betragen.', AJ_SOCIAL_TEXTDOMAIN), 'error');
            return false;
        }

        // Breitenliste abrufen und validieren
        $allowed_widths = array_keys(Helper::get_price($product_id));
        if (!in_array((int)$ajuploadwidth, $allowed_widths, true)) {
            wc_add_notice(
                sprintf(
                    __('Die ausgewählte Breite (%d cm) ist nicht gültig. Erlaubte Breiten sind: %s.', AJ_SOCIAL_TEXTDOMAIN),
                    $ajuploadwidth,
                    implode(', ', $allowed_widths)
                ),
                'error'
            );
            return false;
        }

        // Farben und Finish-Daten abrufen (serialisiert, daher mit unserialize decodieren)
        $serialized_colors = get_option('aj_vinyl_colorsdata', ''); // Abrufen der serialisierten Farbdaten
        $colors = unserialize($serialized_colors); // Deserialisieren

        // Überprüfen, ob die unserialisierten Farbdaten gültig sind
        if ($colors === false) {
            wc_add_notice(__('Fehler beim Laden der Farbdaten.', AJ_SOCIAL_TEXTDOMAIN), 'error');
            return false;
        }

        // Holen der ausgewählten Farbe und Finish
        $selected_color = $_POST['aj-social-color'] ?? '';
        $selected_finish = $_POST['aj-social-surface'] ?? '';

        // Überprüfen, ob die ausgewählte Farbe und Finish in der Liste der verfügbaren Farben und Finishes vorhanden sind
        $valid_color = false;
        foreach ($colors as $color) {
            if ($color['color'] === $selected_color && $color['finish'] === $selected_finish) {
                $valid_color = true;
                break;
            }
        }

        // Wenn die ausgewählte Farbe und Finish nicht gültig sind, eine Fehlermeldung ausgeben
        if (!$valid_color) {
            wc_add_notice(__('Die ausgewählte Farbe oder das Finish ist nicht gültig.', AJ_SOCIAL_TEXTDOMAIN), 'error');
            return false;
        }

        // Schriftarten-Daten abrufen (serialisiert, daher mit unserialize decodieren)
        $serialized_fonts = get_option('aj_vinyl_fontsdata', ''); // Abrufen der serialisierten Schriftart-Daten
        $fonts = unserialize($serialized_fonts); // Deserialisieren

        // Überprüfen, ob die unserialisierten Schriftart-Daten gültig sind
        if ($fonts === false) {
            wc_add_notice(__('Fehler beim Laden der Schriftart-Daten.', AJ_SOCIAL_TEXTDOMAIN), 'error');
            return false;
        }

        // Holen der ausgewählten Schriftart und deren Eigenschaften
        $selected_font = $_POST['aj-social-font'] ?? ''; // Ausgewählte Schriftart

        // Überprüfen, ob die ausgewählte Schriftart und deren Eigenschaften in der Liste der verfügbaren Schriftarten vorhanden sind
        $valid_font = false;
        foreach ($fonts as $font) {
            if ($font['name'] === $selected_font) {
                // Überprüfen, ob die ausgewählte Schriftart die gewünschten Eigenschaften hat

                $valid_font = true;
                break;
            }
        }

        // Wenn die ausgewählte Schriftart nicht gültig ist, eine Fehlermeldung ausgeben
        if (!$valid_font) {
            wc_add_notice(__('Die ausgewählte Schriftart oder deren Eigenschaften sind nicht gültig.', AJ_SOCIAL_TEXTDOMAIN), 'error');
            return false;
        }

        return $passed;
      
    }

    public function aj_social_save_custom_fields_in_order($item, $cart_item_key, $values, $order)
    {
        $product_id = $values['data']->get_id();

        if (!Helper::is_social_enabled($product_id)) {
            return; // Produkt nicht aktiv, daher keine Validierung nötig
        }


        // Überprüfen, ob die Felder gesetzt sind
        $text = isset($values['aj_social_text']) ? $values['aj_social_text'] : '';
        $height = isset($values['aj_social_height']) ? $values['aj_social_height'] : '';
        $width = isset($values['aj_social_width']) ? $values['aj_social_width'] : '';
        $surface = isset($values['aj_social_surface']) ? $values['aj_social_surface'] : '';
        $color = isset($values['aj_social_color']) ? $values['aj_social_color'] : '';
        $font = isset($values['aj_social_font']) ? $values['aj_social_font'] : '';

        // Die Felder in einem Array zusammenfassen
        $custom_data = [
            'text' => $text,
            'height' => $height,
            'width' => $width,
            'surface' => $surface,
            'color' => $color,
            'font' => $font
        ];


        $item->add_meta_data('_aj_social_design_data', serialize($custom_data), true);
        $item->save();
    }

    public function aj_social_display_custom_fields_in_cart($item_data, $cart_item)
    {
        // Überprüfen, ob die benötigten Felder vorhanden sind
        if (isset($cart_item['aj_social_text'], $cart_item['aj_social_width'], $cart_item['aj_social_surface'], $cart_item['aj_social_color'])) {

            // Array mit den Feldnamen und Werten
            $custom_fields = [
                'Wunschtext' => esc_html($cart_item['aj_social_text']),
                'Breite' => esc_html($cart_item['aj_social_width'] . ' cm'),
                'Oberfläche' => esc_html($cart_item['aj_social_surface']),
                'Farbe' => esc_html($cart_item['aj_social_color']),

            ];

            // Füge die benutzerdefinierten Felder zum Item-Daten-Array hinzu
            foreach ($custom_fields as $name => $value) {
                $item_data[] = array(
                    'name' => $name,
                    'value' => $value,
                );
            }
        }

        return $item_data;
    }

    public function aj_social_adjust_cart_item_pricing($cart)
    {
        $discountdata = Helper::getDiscountRule();

        if (is_admin() && !defined('DOING_AJAX')) {
            return; // Verhindert Ausführung im Admin-Bereich
        }

        if (empty($cart->get_cart())) {
            return; // Keine Artikel im Warenkorb, keine Aktionen notwendig
        }

        // Warenkorb durchlaufen und Preise anpassen
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];


            if (isset($cart_item['aj_social_price']) && isset($cart_item['aj_social_width'])) {
                $price = Helper::calcPrice($cart_item['aj_social_width'], $product_id);

                // Passenden Rabatt anhand der Menge aus der Rabatt-Tabelle finden
                $applicable_discount = 0; // Standardmäßig kein Rabatt

                foreach ($discountdata as $discount) {
                    if ($quantity >= $discount['quantity']) {
                        $applicable_discount = $discount['discount'];
                    } else {
                        break; // Überspringe die verbleibenden Werte, da die Tabelle aufsteigend ist
                    }
                }

                // Rabatt auf den angepassten Preis anwenden, falls vorhanden
                $discounted_price = $price * (1 - $applicable_discount);
                $product->set_price(round($discounted_price, 2)); // Rabattierten Preis setzen
                return $product;
            }
        }
    }

    public function aj_social_adjust_cart_item_pricing_on_session($cart_item, $values, $key)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return; // Verhindert Ausführung im Admin-Bereich
        }

        // Hier kannst du die gleiche Logik wie in adjust_cart_item_pricing anwenden
        $product = $cart_item['data'];
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];

        if (isset($cart_item['aj_social_price']) && isset($cart_item['aj_social_width'])) {
            $price = Helper::calcPrice($cart_item['aj_social_width'], $product_id);

            $discountdata = Helper::getDiscountRule();
            $applicable_discount = 0;

            // Rabatt anwenden
            foreach ($discountdata as $discount) {
                if ($quantity >= $discount['quantity']) {
                    $applicable_discount = $discount['discount'];
                } else {
                    break;
                }
            }

            $discounted_price = $price * (1 - $applicable_discount);
            $product->set_price(round($discounted_price, 2));

            return $cart_item;
        }
        return $cart_item;
    }

    public function aj_social_discount_table()
    {
        global $product;
        $product_id = $product->get_id();
        $discountData = Helper::getDiscountRule();

        if (Helper::is_social_enabled($product_id)) {
            echo Render::view('discount_table', [
                'discountData' => $discountData
            ]);
        }
    }

}
