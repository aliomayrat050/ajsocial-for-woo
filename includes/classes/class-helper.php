<?php

namespace AJ_SOCIAL\Includes\Classes;

class Helper
{
    public static function is_social_enabled($product_id)
    {
        $enabled = get_post_meta($product_id, '_aj_social_enabled', true);
        return $enabled === 'yes';
    }

    public static function get_icon_path($product_id)
    {
        $path = get_post_meta($product_id, '_aj_social_icons_path', true);
        return $path;
    }

    public static function get_icon_settings($product_id)
    {
        $settings = get_post_meta($product_id, '_aj_social_icon_settings', true);
        return $settings;
    }

    public static function get_price($product_id)
    {
        return [

            15 => 4.49,
            20 => 4.99,
            25 => 5.49,
            30 => 5.99,
            35 => 6.49,
            40 => 7.49,
            45 => 8.49,
            50 => 9.49,
            55 => 10.49,
            60 => 13.99,
            65 => 16.99

        ];
    }

    public static function getDiscountRule()
    {
        return [
            ['quantity' => 2, 'discount' => 0.05],
            ['quantity' => 10, 'discount' => 0.15],
            ['quantity' => 20, 'discount' => 0.2],
            ['quantity' => 50, 'discount' => 0.3],
            ['quantity' => 100, 'discount' => 0.5],
        ];
    }



    public static function calcPrice($width, $product_id)
    {
        // Preisstaffelung abrufen
        $price_table = self::get_price($product_id);

        // Prüfen, ob die Breite in der Preisstaffelung vorhanden ist
        if (isset($price_table[$width])) {
            return round($price_table[$width], 2);
        }

        // Falls die Breite nicht definiert ist, einen Fehler ausgeben oder Standardwert
        return 999; // Oder alternativ: throw new Exception('Ungültige Breite ausgewählt.');
    }
}
