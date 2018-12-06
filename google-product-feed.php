<?php
/*
Plugin Name: Google Product Feed
Plugin URI: https://www.graphedia.ie
Description: Generate valid Google Product Feeds for Google Merchant Center
Version: 1.0
Author: Brendan Doyle @ Graphedia
Author URI: https://www.graphedia.ie
*/
defined('ABSPATH') or die("Cannot access pages directly.");

define("GPF_BASE", plugin_dir_path(__FILE__));
define("GPF_URL", plugin_dir_url(__FILE__));
define("GPF_VERSION", "1.0.0");
function gpf_init()
{


    if (class_exists('WooCommerce')) {


        include_once("includes/GFeed.php");
        include_once("includes/FeedTable.php");


    } else {


        add_action('admin_notices', 'gsf_admin_notice');


        function gsf_admin_notice()
        {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>WooCommerce is required for this plugin (Google Product feed) to work.</p>
            </div>
            <?php
        }


    }

}
add_action("plugins_loaded", "gpf_init");