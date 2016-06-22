<?php
/**
 * @package croton_extensions
 * @version 1.0
 */
/*
Plugin Name: Croton Theme Extensions
Plugin URI: http://juarathemes.com
Description: Extensions for Croton theme, For Sliding gallery powered by jetpack, twitter widget, Images resizer etc.
Author: Juarathemes
Version: 1.0.0
Author URI: http://juarathemes.com
*/


require_once plugin_dir_path(__FILE__) . 'widgets/storm-twitter/TwitterAPIExchange.php';
require plugin_dir_path(__FILE__) . 'widgets/twitter-zl.php';
require plugin_dir_path(__FILE__) . 'slideshowgallery.php';
require plugin_dir_path(__FILE__) . 'imageresizer.php';
?>
