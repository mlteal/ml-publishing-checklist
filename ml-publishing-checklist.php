<?php
/*
 * Plugin Name: ML Writers Checklist
 * Plugin URI: https://github.com/mlteal/ml-publishing-checklist
 * Description: Adds a javascript-enforced checklist by the post publish meta box. Validates some Post fields by default and can be extended to validate other custom fields via filters.
 * Author: mlteal
 * Version: 1.0.0
 * Author URI:
 * License: GPL2+
 * Text Domain: ml_wc
 *
 * GitHub Plugin URI: https://github.com/mlteal/ml-publishing-checklist
 * GitHub Branch: master
 */

define( 'ML_CHECKLIST__VERSION',   '1.0.0' );
define( 'ML_CHECKLIST__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ML_CHECKLIST__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function ml_publishing_checklist_init() {
	require_once( ML_CHECKLIST__PLUGIN_DIR . 'class-ml-publishing-checklist.php' );
	new ML_Publishing_Checklist();
}

// by constructing/instantiating right from a VIP action, we confirm that the plugin is active
add_action( 'plugins_loaded', 'ml_publishing_checklist_init' );
