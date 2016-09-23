<?php

/**
 * Woo Attributes Coupon
 *
 * @package   Woo_Attributes_Coupon
 * @author    Gaurav Nagpal <nagpal.gaurav89@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.gauravnagpal.com
 * @copyright 2016 Gaurav Nagpal
 */
class Woo_Attributes_Coupon {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    const VERSION = '1.0';

    /**
     * Unique identifier for plugin.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_slug = 'Woo_Attributes_Coupon';

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * post meta for attribute field
     * 
     * @since    1.0.0
     *
     * @var string
     */
    protected $post_meta_name = 'attribute_id';

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     1.0.0
     */
    private function __construct() {

        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));

        // check if coupon valid or not
        add_filter('woocommerce_coupon_is_valid_for_product', array($this, 'wac_check_coupon_valid'), 10, 4);
        
    }

    /**
     * Return the plugin slug.
     *
     * @since    1.0.0
     *
     * @return    Woo_Attributes_Coupon slug variable.
     */
    public function get_plugin_slug() {
        return $this->plugin_slug;
    }

    /**
     * Return the coupon meta name
     *
     * @since    1.0.0
     *
     * @return    Woo_Attributes_Coupon meta name variable.
     */
    public function get_meta_name() {
        return $this->post_meta_name;
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate($network_wide) {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_activate();
                }

                restore_current_blog();
            } else {
                self::single_activate();
            }
        } else {
            self::single_activate();
        }
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate($network_wide) {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_deactivate();
                }

                restore_current_blog();
            } else {
                self::single_deactivate();
            }
        } else {
            self::single_deactivate();
        }
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    1.0.0
     *
     * @param    int    $blog_id    ID of the new blog.
     */
    public function activate_new_site($blog_id) {

        if (1 !== did_action('wpmu_new_blog')) {
            return;
        }

        switch_to_blog($blog_id);
        self::single_activate();
        restore_current_blog();
    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    1.0.0
     *
     * @return   array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids() {

        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

        return $wpdb->get_col($sql);
    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since    1.0.0
     */
    private static function single_activate() {
        // No activation functionality needed... yet
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    private static function single_deactivate() {
        // No deactivation functionality needed... yet
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname(__FILE__))) . '/languages/');
    }

    /**
     * check coupon valid or not
     *
     * @since    1.0.0
     */
    public function wac_check_coupon_valid($false, $product, $instance, $values) {
        
        $return = $false;
        
        $coupon_attribute_ids = get_post_meta($instance->id, $this->post_meta_name, true);
        $coupon_attribute_ids_arr = unserialize($coupon_attribute_ids);
        if (is_array($coupon_attribute_ids_arr) && !empty($coupon_attribute_ids_arr)) {

            $product_attributes = $product->get_attributes();
            $current_product_texnomies = array();
            if (!empty($product_attributes)):
                foreach ($product_attributes as $attribute) :
                    if ($attribute['is_taxonomy']) {
                        $values = wc_get_product_terms($product->id, $attribute['name'], array('fields' => 'ids'));
                        if (is_array($values) && !empty($values)) {
                            foreach ($values as $kk => $vv) {
                                $current_product_texnomies[] = $vv;
                            }
                        }
                    }
                endforeach;
            endif;
            
            $compare_status = "";
            foreach($coupon_attribute_ids_arr as $kk=>$vv){
                if(in_array($vv, $current_product_texnomies)){
                    $compare_status = "1";
                }
            }
            if($compare_status==''){
                $return = false;
            } else{
                $return = true;
            }
        }
        return $return;
    }
}
