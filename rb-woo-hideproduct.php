	
<?php
/**
* The plugin bootstrap file
*
* @wordpress-plugin
* Plugin Name:    Hide product from Shop for Woocommerce.
* Plugin URI:     https://github.com/Rabdan/rb-woo-hideproduct
* Description:    Hide product from Shop for Woocommerce.
* Version:        1.0.0
* Author:         Rabdan
* Author URI:     https://github.com/Rabdan
*/
defined( 'WPINC' ) || die;
/**
* Admin class.
*/

class RB_Custom_WooCommerce_Field {
 
    private $chfield_id;
 
    public function __construct() {
        $this->chfield_id = 'is_rbhidden';
    }
 
    public function init() {
 
            add_action(
                'woocommerce_product_options_general_product_data',
                array( $this, 'RB_Custom_WooCommerce_Field_checkbox' )
            );
            add_action( 
            	'woocommerce_process_product_meta', 
                array( $this, 'RB_Custom_WooCommerce_Field_checkbox_save' )
            );

    }
 
    public function RB_Custom_WooCommerce_Field_checkbox() {
	    global $post;

	   $input_checkbox = get_post_meta( $post->ID, $this->chfield_id, true );
	   if( empty( $input_checkbox ) ) $input_checkbox = '';

	    woocommerce_wp_checkbox(array(
	        'id'            => $this->chfield_id,
	        'label'         => __('Hidden product', 'woocommerce' ),
	        'description'   => __('Hidden to shop gallery', 'woocommerce' ),
	        'value'         => $input_checkbox,
	    ));
         
    }

    public function RB_Custom_WooCommerce_Field_checkbox_save( $post_id ) {
    	$_custom_check_option = isset( $_POST[ $this->chfield_id ] ) ? 'yes' : '';
    	update_post_meta( $post_id, $this->chfield_id, $_custom_check_option );
    }


}
/**
* Filter out class.
*/

class RB_Custom_WooCommerce_Display {

   private $chfield_id;

   public function __construct() {
       $this->chfield_id = 'is_rbhidden';
   }

   public function init() {
       add_filter(
           'woocommerce_product_query_meta_query',
            array( $this, 'RB_hide_from_query' ),
           10 ,2
       );
   }

    public function RB_hide_from_query( $meta_query, $query ) {
	    // Only on shop pages
	    if( ! is_shop() ) return $meta_query;

	    $meta_query[] = array(
		   'relation' => 'OR',
		    array(
		        'key'     => $this->chfield_id,
		        'value'   => 'yes',
		        'compare' => '!='
		    ),
		    array(
		        'key'     => $this->chfield_id,
		        'compare' => 'NOT EXISTS',
		    ),

	    );
	    return $meta_query;
	}
}


add_action( 'plugins_loaded', 'rb_woo_hideproduct_start' );
/**
* Start the plugin.
*/
function rb_woo_hideproduct_start() {
   if ( is_admin() ) {
       $admin = new RB_Custom_WooCommerce_Field( 'is_rbhidden' );
       $admin->init();

   } else {
       $plugin = new RB_Custom_WooCommerce_Display( 'is_rbhidden' );
       $plugin->init();

   }
}