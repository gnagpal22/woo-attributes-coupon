<?php

/**
 * Woo_Attributes_Coupon_Admin class
 *
 * @package   Woo_Attributes_Coupon
 * @author    Gaurav Nagpal <nagpal.gaurav89@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.gauravnagpal.com
 * @copyright 2016 Gaurav Nagpal
 */
class Woo_Attributes_Coupon_Admin {

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;
	
	/**
     * name of the coupon category
     *
     * @since    2.2.0
     *
     * @var      string
     */
	protected $coupon_tax_name = 'coupon_categories';

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct() {

        /*
         * Call $plugin_slug from public plugin class.
         */
        $plugin = Woo_Attributes_Coupon::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();

        /*
         * get meta name from public class
         *              
         */

        $this->post_meta_name = $plugin->get_meta_name();
        $this->tags_meta_name = $plugin->get_tag_meta_name();

        /*
         * Show new options in user restrictions section
         */
        add_action('woocommerce_coupon_options_usage_restriction', array($this, 'wac_usage_restriction_options'), 10, 0);

        /*
         * save coupon extra parameters in admin
         */
        add_action('save_post', array($this, 'wac_admin_save_coupon'));

        /*
         * show coupon tags, attributes in listing page
         * @since 2.0.0
         */
        add_filter('manage_shop_coupon_posts_columns', array($this, 'wac_new_columns_head'),15,1);
        add_action('manage_shop_coupon_posts_custom_column', array( $this,'wac_columns_content'), 10, 2);
		
		/*
		 * coupon category section
		 * @since 2.2.0
		 */
		//add_action( 'init', array($this, 'wac_create_coupons_taxonomy') );
		add_action('admin_menu', array($this, 'wac_coupon_admin_menu')); 
		add_filter('manage_edit-shop_coupon_columns', array($this, 'wac_create_coupon_category_column'), 10,2 );
		add_action('manage_shop_coupon_posts_custom_column', array($this, 'wac_add_value_to_coupon_category_column'), 2 );
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
     * Save coupon settings in admin
     * @since    1.0.0
     */
    public function wac_admin_save_coupon($post_id) {

        // If this is just a revision, don't send the email.
        if (wp_is_post_revision($post_id))
            return;

        // check if you are in admin only
        if (is_admin()) {

            // check if post type is coupon
            $post_type = get_post_type($post_id);
            if ("shop_coupon" != $post_type)
                return;

            // save coupon attribute values
            $selected_values = isset($_POST['coupon_attributes']) ? $_POST['coupon_attributes'] : array();
            update_post_meta($post_id, $this->post_meta_name, serialize($selected_values));

            // save coupon product tags
            $selected_tags = isset($_POST['product_tags']) ? $_POST['product_tags'] : array();
            
            update_post_meta($post_id, $this->tags_meta_name, serialize($selected_tags));
        }
        return true;
    }

    /**
     * Show new options in user restriction section in coupon section in admin
     * @since    1.0.0
     * @updated  2.0.0
     */
    public function wac_usage_restriction_options() {
       
       global $thepostid, $post, $woocommerce;

        $thepostid = empty($thepostid) ? $post->ID : $thepostid;
        // get all attributes
        $attribute_options = array();
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attribute_taxonomy) {
            $all_terms = get_terms('pa_' . $attribute_taxonomy->attribute_name);
            foreach ($all_terms as $all_term) {
                $attribute_options[$all_term->term_id] = $attribute_taxonomy->attribute_label . ' > ' . $all_term->name;
            }
        }

        // get all product tags
        $product_tags = array();
        $terms = get_terms('product_tag');
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $product_tags[$term->term_id] = $term->name;
            }
        }
        $existing_coupon_tags = ( get_post_meta($thepostid, $this->tags_meta_name, true) ? unserialize(get_post_meta($thepostid, $this->tags_meta_name, true)) : array() );
        //echo"<pre>".$thepostid.$this->tags_meta_name;print_r(get_post_meta($thepostid, $this->tags_meta_name, true));echo"</pre>";
        $this->woocommerce_wp_select_multiple(
                array(
                    'id' => 'coupon_attributes',
                    'name' => 'coupon_attributes[]',
                    'label' => __('Attributes', 'woo-attributes-coupon'),
                    'description' => __('select any attribute you want to apply this coupon too. press SHIFT key for multiple selection', 'woo-attributes-coupon'),
                    'desc_tip' => true,
                    'class' => '',
                    'style' => 'width:50%;',
                    'style_option' => 'border-bottom:1px dotted #222',
                    'options' => $attribute_options
                )
        );

        $this->woocommerce_wp_select_multiple(
                array(
                    'id' => 'product_tags',
                    'name' => 'product_tags[]',
                    'label' => __('Product Tags', 'woo-attributes-coupon'),
                    'description' => __('a product must use this tag for coupon validation. press SHIFT key for multiple selection', 'woo-attributes-coupon'),
                    'desc_tip' => true,
                    'class' => '',
                    'style' => 'width:50%;',
                    'style_option' => 'border-bottom:1px dotted #222',
                    'options' => $product_tags,
                    'value' => $existing_coupon_tags
                )
        );
    }

    /*
     * add multiple select option
     */

    function woocommerce_wp_select_multiple($field) {
        global $thepostid, $post, $woocommerce;

        $thepostid = empty($thepostid) ? $post->ID : $thepostid;
        $field['class'] = isset($field['class']) ? $field['class'] : 'select ';
        $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
        $field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
        $field['style'] = isset($field['style']) ? $field['style'] : '';
        $field['style_option'] = isset($field['style_option']) ? $field['style_option'] : '';
        $field['value'] = isset($field['value']) ? $field['value'] : ( get_post_meta($thepostid, $this->post_meta_name, true) ? unserialize(get_post_meta($thepostid, $this->post_meta_name, true)) : array() );

        echo '<div class="options_group"><p class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '"><label for="' . esc_attr($field['id']) . '">' . wp_kses_post($field['label']) . '</label><select id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="' . esc_attr($field['class']) . '" style="' . esc_attr($field['style']) . '" multiple="multiple">';

        foreach ($field['options'] as $key => $value) {

            echo '<option  style="' . esc_attr($field['style_option']) . '"  value="' . esc_attr($key) . '" ' . ( in_array($key, $field['value']) ? 'selected="selected"' : '' ) . '>' . esc_html($value) . '</option>';
        }

        echo '</select> ';

        if (!empty($field['description'])) {

            if (isset($field['desc_tip']) && false !== $field['desc_tip']) {
                echo '<img class="help_tip" data-tip="' . esc_attr($field['description']) . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
            } else {
                echo '<span class="description">' . wp_kses_post($field['description']) . '</span>';
            }
        }
        echo '</p></div>';
    }

    function wac_new_columns_head($defaults) {
        $defaults['ptags'] = "Product Tags";
        return $defaults;
    }

    function wac_columns_content($column_name, $post_ID) {
        switch ($column_name) {
            case 'ptags':
                $existing_coupon_tags = ( get_post_meta($post_ID, $this->tags_meta_name, true) ? unserialize(get_post_meta($post_ID, $this->tags_meta_name, true)) : array() );
                if(!empty($existing_coupon_tags)){
                    $name = "";
                    foreach($existing_coupon_tags as $kk=>$vv){
                        $term = get_term($vv,'product_tag');
                        $name.= (is_object($term))?$term->name.', ':"";
                    }
                    $name = ($name!='')?rtrim(trim($name),','):$name;
                    echo $name;
                } else{
                    echo "-";
                }
                break;
        }
    }

	// Add Submenu for custom Coupon Category
	function wac_coupon_admin_menu() { 
		add_submenu_page('woocommerce', 'Coupon Categories', 'Coupon Categories', 'manage_options', 'edit-tags.php?taxonomy='.$this->coupon_tax_name.'&post_type=shop_coupon'); 
	}

	// Create Taxonomy for custom Coupon Category
	function wac_create_coupons_taxonomy() {
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
		register_taxonomy($this->coupon_tax_name,array('shop_coupon'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_in_menu'=>true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $this->coupon_tax_name ),
			'capabilities' => array('manage_terms','edit_terms','delete_terms','assign_terms')
		));
	}

	function wac_create_coupon_category_column( $columns ) {
		$new_columns = $columns;
		$new_columns[$this->coupon_tax_name] = 'Categories';
		return $new_columns;
	}

	function wac_add_value_to_coupon_category_column($column){
		global $post;
		if ( $column == $this->coupon_tax_name ) {    
			$category = get_the_terms( $post->ID,$this->coupon_tax_name );
			if(!empty($category)){
				$name = "";
				foreach($category as $kk=>$vv){
					$name.= $vv->name.', ';
				}
				$name = ($name!='')?rtrim(trim($name),','):$name;
				echo $name;
			} else{
				echo "-";
			}
		}
	}
	
}
