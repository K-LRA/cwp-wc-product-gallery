<?php

/**
 * Prevent direct access to this file.
 */
if (!defined('ABSPATH')) {
    die;
}

/**
 * Check to see if a plugin is installed and active. Search by entry .php file name. 
 * usage e.g.: ws_is_plugin_active('wp-seo') // returns true if Yoast SEO is installed
 */
function ws_is_plugin_active($plugin_name)
{
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

    foreach ($active_plugins as $plugin) {
        if (strpos($plugin, '/' . $plugin_name)) {
            return true;
        }
    }
    return false;
}

/**
 * Removes trailing 0s from the decimal place in the provided number.
 */
function ws_is_plugin_activenumber_round_trim_0($number)
{
    $rounded = round($number, 2);

    if (substr($rounded, -3) === ".00") {
        $rounded = substr($rounded, 0, -3);
    }

    return rtrim($rounded, '0');
}

/**
 * Parses the provided string and removes trailing 0s from numbers where they may exist.
 */
function ws_string_round_trailing_0($input_string)
{
    $pattern = '/(\d+)\.00\b/';
    $replacement = '$1';

    $output_string = preg_replace($pattern, $replacement, $input_string);

    return $output_string;
}


function ws_get_contrast_colour($color)
{
    // Function to calculate the relative luminance of a color
    function calculate_relative_luminance($color)
    {
        $color = str_replace('#', '', $color);
        $r = hexdec(substr($color, 0, 2)) / 255;
        $g = hexdec(substr($color, 2, 2)) / 255;
        $b = hexdec(substr($color, 4, 2)) / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    // Check if the input color is in hex format
    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
        $luminance = calculate_relative_luminance($color);
    }
    // Check if the input color is in rgb or rgba format
    elseif (preg_match('/^rgb(a)?\((\d{1,3},\s*\d{1,3},\s*\d{1,3}(,\s*(0(\.\d{1,2})?|1(\.0{1,2})?))?)\)$/', $color)) {
        $rgba = explode(',', str_replace(['rgb(', 'rgba(', ')', ' '], '', $color));
        $rgba = array_map('intval', $rgba);
        if (count($rgba) === 3) {
            $rgba[] = 1; // Add alpha (opacity) if not specified
        }
        $luminance = calculate_relative_luminance(sprintf("#%02x%02x%02x", $rgba[0], $rgba[1], $rgba[2]));
    }
    // Check if the input color is in hsl or hsla format
    elseif (preg_match('/^hsl(a)?\((\d{1,3},\s*\d{1,3}%,\s*\d{1,3}%(,\s*(0(\.\d{1,2})?|1(\.0{1,2})?))?)\)$/', $color)) {
        // Extract HSL values
        preg_match_all('/\d+(\.\d+)?/', $color, $matches);
        $h = (float)$matches[0][0];
        $s = (float)$matches[0][1];
        $l = (float)$matches[0][2];
        $luminance = calculate_relative_luminance(hslToHex($h, $s, $l));
    } else {
        // Handle unsupported color formats here, or return a default value
        return null;
    }

    // Determine the contrast color based on luminance
    return ($luminance > 0.5) ? "2d2d2d" : "ffffff";
}

// Function to convert HSL to HEX
function hslToHex($h, $s, $l)
{
    $h /= 360;
    $s /= 100;
    $l /= 100;

    if ($s == 0) {
        $r = $g = $b = $l;
    } else {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h * 6, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 1) {
            $r = $c;
            $g = $x;
            $b = 0;
        } elseif ($h < 2) {
            $r = $x;
            $g = $c;
            $b = 0;
        } elseif ($h < 3) {
            $r = 0;
            $g = $c;
            $b = $x;
        } elseif ($h < 4) {
            $r = 0;
            $g = $x;
            $b = $c;
        } elseif ($h < 5) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }

        $r = ($r + $m) * 255;
        $g = ($g + $m) * 255;
        $b = ($b + $m) * 255;
    }

    return sprintf("#%02x%02x%02x", round($r), round($g), round($b));
}
