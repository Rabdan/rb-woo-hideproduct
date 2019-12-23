	
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
    private $terms;
 
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

            add_action( 'product_cat_edit_form_fields', 
                array( $this, 'RB_Custom_taxonomy_edit_meta_field')
            );

            add_action('product_cat_add_form_fields', 
                array( $this, 'RB_Custom_taxonomy_add_new_meta_field')
            );

            add_action('edited_product_cat', 
                array( $this, 'RB_Custom_save_taxonomy_custom_meta')
            );
            add_action('create_product_cat', 
                array( $this, 'RB_Custom_save_taxonomy_custom_meta')
            );


            add_filter( 'manage_edit-product_columns', 
                array( $this, 'RB_Custom_extra_column'), 
            20 );

            add_action( 'manage_posts_custom_column', 
                array( $this, 'RB_Custom_populate_hidden' )
            );

            add_action('admin_head', 
                array( $this, 'RB_Custom_column_width')
            );


    }


    //Product Cat Create page
    public function RB_Custom_taxonomy_add_new_meta_field() {
      echo '<tr class="form-field term-name-is_meta_hidden checkbox">
        <th scope="row"><label for="is_meta_hidden">' . wp_kses_post( __('Hidden category', 'woocommerce' ) ) . '</label></th>';
      echo '<td><input type="checkbox" class="checkbox" name="is_meta_hidden" id="is_meta_hidden" value=""/> ';
      echo '<p class="description">' . wp_kses_post( __('Hidden category to shop gallery', 'woocommerce' ) ) . '</p></td>';
      echo '</tr>';
    }

    //Product Cat Edit page
    public function RB_Custom_taxonomy_edit_meta_field($term) {

      //getting term ID
      $term_id = $term->term_id;

      $input_checkbox = get_term_meta($term_id, 'is_meta_hidden', true );
      if( empty( $input_checkbox ) ) $input_checkbox = '';

      echo '<tr class="form-field term-name-is_meta_hidden checkbox">
        <th scope="row"><label for="is_meta_hidden">' . wp_kses_post( __('Hidden category', 'woocommerce' ) ) . '</label></th>';
      echo '<td><input type="checkbox" class="checkbox" name="is_meta_hidden" id="is_meta_hidden" value="' . esc_attr( $input_checkbox ) . '" ' . checked( $input_checkbox, 'yes', false ) . '/> ';
      echo '<p class="description">' . wp_kses_post( __('Hidden category to shop gallery', 'woocommerce' ) ) . '</p></td>';
      echo '</tr>';
    }


    // Save extra taxonomy fields callback function.
    public function RB_Custom_save_taxonomy_custom_meta($term_id) {
      $is_meta_title = isset( $_POST[ 'is_meta_hidden' ] ) ? 'yes' : '';
      update_term_meta($term_id, 'is_meta_hidden', $is_meta_title);
    }



    public function RB_Custom_column_width() {
        echo '<style type="text/css"> body.wp-admin table.wp-list-table .column-is_hidden { width:60px; } </style>';
    }
 

    public function RB_Custom_extra_column( $columns_array ) {
      $columns_array['is_hidden'] = 'Hidden';
      return $columns_array;     
    }

    public function RB_Custom_populate_hidden( $column_name ) {
     
      if( $column_name  == 'is_hidden' ) {
        echo '<input type="checkbox" data-productid="' . get_the_ID() .'" class="some_checkbox" ' . checked( 'yes', get_post_meta( get_the_ID(), $this->chfield_id, true ), false ) . ' disabled readonly /><small style="display:block;color:#7ad03a;"></small>';
      }
     
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
      $args = array(
          'taxonomy' => 'product_cat',
          'hide_empty' => false,
          'meta_query' => array(
              array(
                  'key'     => 'is_meta_hidden',
                  'value'   => 'yes',
                  'compare' => '=='
              ),
          )
      );
      $this->terms = array();

      $obj = new WP_Term_Query( $args );
      if ( ! empty( $obj->terms ) ) {
        foreach ( $obj ->terms as $term ) {
          $this->terms[] = $term->slug;
        }
      }
    //print_r($this->terms);


       add_filter(
           'woocommerce_product_query_meta_query',
            array( $this, 'RB_hide_from_query' ),
           10 ,2
       );
       add_filter(
            'woocommerce_product_query_tax_query', 
            array( $this, 'RB_product_query_tax_query'),
           10, 2 
       );

   }

  public function RB_product_query_tax_query( $tax_query, $query ) {
      // Only on shop or category pages
      if( ( is_shop() || is_category() ) && is_array( $this->terms ) )  {
        // Таксономия в которой нужно скрыть категорию
        $taxonomy = 'product_cat';
        $tax_query[] = array(
          'taxonomy' => $taxonomy,
          'field' => 'slug', // категорию которую нужно скрыть Or 'name' or 'term_id'
          'terms' => $this->terms,
          'operator' => 'NOT IN', // Excluded
        );
      }
      return $tax_query;
  }
  public function RB_hide_from_query( $meta_query, $query ) {
	    // Only on shop or category pages
      if( is_shop() || is_category() ) {

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
      }
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