<?php
/**
 * Woo Attributes Coupon
 *
 * Woocommerce coupon section extension for adding coupon for special attributes and tags. Also, you can categorized the coupons.
 *
 * Plugin Name: 	  Woo Attributes Coupon
 * Plugin URI:  	  https://github.com/gnagpal22/woo-attributes-coupon
 * Description: 	  Woocommerce coupon section extension for adding coupon for special attributes and tags. Also, you can categorized the coupons.
 * Version:     	  2.2.0
 * Author:      	  Gaurav Nagpal
 * Author URI:  	  http://www.gauravnagpal.com/
 * Text Domain: 	  woo-attributes-coupon
 * License:     	  GPL-2.0+
 * License URI:           http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: 	  /languages
 * 
 * WC tested up to:   3.6.2
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
	
	
	// Create Taxonomy for custom Coupon Category
	add_action( 'init', 'create_topics_hierarchical_taxonomy', 0 );
	function create_topics_hierarchical_taxonomy() {
	  $labels = array(
		'name' => _x( 'Coupon Category', 'taxonomy general name' ),
		'singular_name' => _x( 'Category', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Categories' ),
		'all_items' => __( 'All Categories' ),
		'parent_item' => __( 'Parent Category' ),
		'parent_item_colon' => __( 'Parent Category:' ),
		'edit_item' => __( 'Edit Category' ), 
		'update_item' => __( 'Update Category' ),
		'add_new_item' => __( 'Add New Category' ),
		'new_item_name' => __( 'New Category Name' ),
		'menu_name' => __( 'Coupon Categories' ),
	  );    
	 
	// Now register the taxonomy 
	  register_taxonomy('coupon_categories',array('shop_coupon'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'coupon_categories' ),
	  ));
	 
	}
	
}


