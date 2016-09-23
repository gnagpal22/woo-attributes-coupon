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
                         
		/*
		 * Show new options in user restrictions section
		 */
                add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'wac_usage_restriction_options' ),10 ,0 );
                
                /*
		 * save coupon extra parameters in admin
		 */
		add_action( 'save_post', array( $this, 'wac_admin_save_coupon' ) );
                
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
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

        /**
	 * Save coupon settings in admin
	 * @since    1.0.0
	 */
        public function wac_admin_save_coupon($post_id){
            
            // If this is just a revision, don't send the email.
            if ( wp_is_post_revision( $post_id ) )
                    return;
            
            // check if you are in admin only
            if(is_admin()){
                
                // check if post type is coupon
                $post_type = get_post_type($post_id);
                if ( "shop_coupon" != $post_type ) 
                    return;
                
                $selected_values = array();
                if(isset($_POST['coupon_attributes'])){
                    $selected_values = $_POST['coupon_attributes'];
                }
                update_post_meta($post_id, $this->post_meta_name, serialize($selected_values));
            }
            return true;
        }
        
        /**
	 * Show new options in user restriction section in coupon section in admin
	 * @since    1.0.0
         */        
        public function wac_usage_restriction_options(){
            
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            
            $attribute_options = array();
            //$attribute_options['0']= 'Select Attribute';
            foreach($attribute_taxonomies as $attribute_taxonomy){
                $all_terms = get_terms('pa_'.$attribute_taxonomy->attribute_name);
                foreach($all_terms as $all_term){
                    $attribute_options[$all_term->term_id] = $attribute_taxonomy->attribute_label.' > '.$all_term->name;
                }
            }
            $this->woocommerce_wp_select_multiple( 
                    array( 
                        'id' => 'coupon_attributes',
                        'name' => 'coupon_attributes[]',
                        'label' => __( 'Attributes', 'woo-attributes-coupon' ),
                        'description' => __( 'select any attribute you want to apply this coupon too. press SHIFT key for multiple selection', 'woo-attributes-coupon' ), 
                        'desc_tip' => true,
                        'class' => '',
                        'style' => '',
                        'style_option' => 'border-bottom:1px dotted #222',
                        'options' => $attribute_options
                        )
                    );
        }
        
        /*
         * add multiple select option
         */
        function woocommerce_wp_select_multiple( $field ) {
            global $thepostid, $post, $woocommerce;

            $thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
            $field['class']         = isset( $field['class'] ) ? $field['class'] : 'select ';
            $field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
            $field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
            $field['style']         = isset( $field['style'] ) ? $field['style'] : '';
            $field['style_option']  = isset( $field['style_option'] ) ? $field['style_option'] : '';
            $field['value']         = isset( $field['value'] ) ? $field['value'] : ( get_post_meta( $thepostid, $this->post_meta_name, true ) ? unserialize(get_post_meta( $thepostid, $this->post_meta_name, true )): array() );

            echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" multiple="multiple">';

            foreach ( $field['options'] as $key => $value ) {

                echo '<option  style="' . esc_attr( $field['style_option'] ) . '"  value="' . esc_attr( $key ) . '" ' . ( in_array( $key, $field['value'] ) ? 'selected="selected"' : '' ) . '>' . esc_html( $value ) . '</option>';

            }

            echo '</select> ';

            if ( ! empty( $field['description'] ) ) {

                if ( isset( $field['desc_tip'] ) && false !== $field['desc_tip'] ) {
                    echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( WC()->plugin_url() ) . '/assets/images/help.png" height="16" width="16" />';
                } else {
                    echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
                }

            }
            echo '</p>';
        }

}
