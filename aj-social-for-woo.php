<?php

/**
 * Plugin Name: AJ Social for Woo
 * Description: let your Customers Design social media stickers
 * Version: 1.0.2
 * Author: AJ Creative Agency
 * Text Domain: aj-social-for-woo
 */


// Sicherheitshalber sicherstellen, dass WordPress geladen ist
if (!defined('ABSPATH')) {
    exit;
}

const AJ_SOCIAL_TEXTDOMAIN = 'aj-social-for-woo';

function AJ_SOCIAL_auto_loader($class_name)
{
    // Not loading a class from our plugin.
    if (!is_int(strpos($class_name, 'AJ_SOCIAL')))
        return;
    // Remove root namespace as we don't have that as a folder.
    $class_name = str_replace('AJ_SOCIAL\\', '', $class_name);
    $class_name = str_replace('\\', '/', strtolower($class_name)) . '.php';
    // Get only the file name.
    $pos =  strrpos($class_name, '/');
    $file_name = is_int($pos) ? substr($class_name, $pos + 1) : $class_name;
    // Get only the path.
    $path = str_replace($file_name, '', $class_name);
    // Append 'class-' to the file name and replace _ with -
    $new_file_name = 'class-' . str_replace('_', '-', $file_name);
    // Construct file path.
    $file_path = plugin_dir_path(__FILE__)  . str_replace('\\', DIRECTORY_SEPARATOR, $path . strtolower($new_file_name));

    if (file_exists($file_path))
        require_once($file_path);
}

spl_autoload_register('AJ_SOCIAL_auto_loader');



function ajsocial()
{

    // version
    $version = '1.0.2';

    // globals
    global $ajsocial;

    // initialize
    if (!isset($ajsocial)) {
        $ajsocial = new \AJ_SOCIAL\init();
        $ajsocial->initialize($version, __FILE__);
    }

    return $ajsocial;
}

// initialize

ajsocial();






// Declare HPOS compatibility.
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {

        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
