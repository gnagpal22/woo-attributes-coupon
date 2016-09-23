<?php
/**
 * Woo Attributes Coupon
 *
 * Woocommerce coupon section extension for adding coupon for special attributes.
 *
 * Plugin Name: 	  Woo Attributes Coupon
 * Plugin URI:  	  https://github.com/gnagpal22/woo-attributes-coupon
 * Description: 	  Woocommerce coupon section extension for adding coupon for special attributes.
 * Version:     	  1.0
 * Author:      	  Gaurav Nagpal
 * Author URI:  	  http://www.gauravnagpal.com/
 * Text Domain: 	  woo-attributes-coupon
 * License:     	  GPL-2.0+
 * License URI:           http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: 	  /languages
 * 
 * @package   Woo_Attributes_Coupon
 * @author    Gaurav Nagpal <nagpal.gaurav89@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.gauravnagpal.com
 * @copyright 2016 Gaurav Nagpal
 */


/**
 * If this file is called directly, abort.
 **/
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/*----------------------------------------------------------------------------*
	 * Public-Facing Functionality
	 *----------------------------------------------------------------------------*/

	/*
	 * Require public facing functionality
	 */
	require_once( plugin_dir_path( __FILE__ ) . 'public/class.woo-attributes-coupon.php' );

	/*
	 * Register hooks that are fired when the plugin is activated or deactivated.
	 * When the plugin is deleted, the uninstall.php file is loaded.
	 */
	register_activation_hook( __FILE__, array( 'Woo_Attributes_Coupon', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Woo_Attributes_Coupon', 'deactivate' ) );

	/*
	 * Get instance
	 */
	add_action( 'plugins_loaded', array( 'Woo_Attributes_Coupon', 'get_instance' ) );

	/*----------------------------------------------------------------------------*
	 * Dashboard and Administrative Functionality
	 *----------------------------------------------------------------------------*/

	/*
	 * Require admin functionality
	 */
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

		require_once( plugin_dir_path( __FILE__ ) . 'admin/class.woo-attributes-coupon-admin.php' );
		add_action( 'plugins_loaded', array( 'Woo_Attributes_Coupon_Admin', 'get_instance' ) );

	}

}
