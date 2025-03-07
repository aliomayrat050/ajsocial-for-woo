<?php

if (!defined('ABSPATH')) {
    exit;
}

function ajsocial_has_setting($name = '')
{

    return ajsocial()->has_setting($name);
}

function ajsocial_get_setting($name, $value = null)
{

    if (ajsocial_has_setting($name)) {
        $value =  ajsocial()->get_setting($name);
    }

    $value = apply_filters("ajsocial/setting/{$name}", $value);

    return $value;
}

function ajsocial_sanitize_input($input)
{
    return sanitize_text_field($input);
}

