<?php
/*
Plugin Name: Web Dev Agent - Testimonials
Plugin URI: 
Description: Display Web Agency Client Testimonials
Version: 1.0.0
Author: edk
Author URI: 
*/

// ensure application access only
if( !defined('ABSPATH') ) {
   exit;
}


class WedDevAgentTestimonials {

	public function __construct() {

      // create custom post type 'wda_testimonial'
      add_action( 'init', array($this,'create_testimonial_post_type' ));

      // assets
      add_action('wp_enqueue_scripts',array($this,'enqueue_assets'));
      add_action('admin_enqueue_scripts', array($this,'enqueue_admin_assets'));

      // 'edit post' page
		add_action('add_meta_boxes', array( $this,'add_testimonial_meta_box')); 
		add_action('save_post',array($this,'save_custom_meta'));

      // front-end UI
      add_shortcode('testimonials',array($this,'shortcode_html'));

   }


   // to do : activate / deactivate / uninstall (remove all data)  - rollout all web-dev-agent plugins


   //
   // create custom post type 'wda_testimonial'
   //
   public function create_testimonial_post_type() {

      $labels = array(
         'name' => __('WDA Testimonials','web-dev-agent'),
         'singular_name' =>  __('WDA Testimonial','web-dev-agent'),
         'menu_name' => 'Testimonials',
      );
      $args = array(
         'labels' => $labels,
         'description' => 'Testimonial Custom Post Type',
         'supports' => array('title','editor','thumbnail'),
         'hierarchical' => true,
         'taxonomies' => array('category'),
         'public' => true,
         'show_ui' => true,
         'show_in_menu' => true,
         'show_in_nav_menus' => true,
         // 'show_in_rest' => true, // in the REST API. Set this to true for the post type to be available in the block editor.
         'has_archive' => true,
         'rewrite' => array( 'slug' => 'testimonial' ),  // custom slug
         'exclude_from_search' => true,
         'publicly_queryable' => true,    // false will exclude archive- and single- templates
         'menu_icon' => 'dashicons-media-text',
      );
      register_post_type('wda_testimonial',$args);
   }


   //
   // assets
   //
   public function enqueue_assets() {

   }   
   public function enqueue_admin_assets() {
      wp_enqueue_style('wda_custom_wp_admin_css',plugin_dir_url( __FILE__ ) . 'css/wda-admin-style.css',array(),1,'all'); 
      // wp_enqueue_style( 'wda_custom_wp_admin_css' );
   }
   

   //
   // 'edit post' page
   //
	public function add_testimonial_meta_box( $post_type ) {

		// Limit meta box to certain post types
		$post_types = array( 'wda_testimonial' );

		if ( in_array( $post_type, $post_types ) ) {

			add_meta_box(
				'wda_testimonial',
				__( 'Testimonial', 'textdomain' ),
				array( $this, 'render_testimonial_meta_box' ),
				$post_types,
				'advanced',
				'high'
			);
		}
	}

   // future : we want this list configurable by site owner
   private function get_default_client_details() {
      return array(
			'name' => '',
         'position' => '',
			'company' => '',
			'website' => '',
		);
   }

   public function render_testimonial_meta_box($post) {

		wp_nonce_field('wda_testimonials_meta_box','wda_testimonials_meta_nonce');

      //$client_details = (get_post_meta($post->ID,'_testimonial_meta_key',true)) ? get_post_meta($post->ID,'_testimonial_meta_key',true) : array();
      
		$saved_details= get_post_meta( $post->ID, '_wda_testimonial_details_meta_key', true );
		$default_details = $this->get_default_client_details();
		$details = wp_parse_args( $saved_details, $default_details ); // Merge the two in case any fields don't exist in the saved data

      ?>

      <h3><?php _e('Customer Details','wda-dev-agent_packages');?></h3>      

      <label class="wda_label">
         <span class="wda_title">Name</span>
            <input
               id="_wda_custom_metabox_name"
               name="_wda_testimonial_array_fields[name]"
               class="wda_input"
               type="text"
               value="<?php echo esc_attr( $details['name'] ); ?>">
      </label>
            
      <label class="wda_label">
         <span class="wda_title">Position</span>
            <input
               id="_wda_custom_metabox_position"
               name="_wda_testimonial_array_fields[position]"
               class="wda_input"
               type="text"
               value="<?php echo esc_attr( $details['position'] ); ?>">
      </label>

      <label class="wda_label">
         <span class="wda_title">Company</span>
            <input
               id="_wda_custom_metabox_company"
               name="_wda_testimonial_array_fields[company]"
               class="wda_input"
               type="text"
               value="<?php echo esc_attr( $details['company'] ); ?>">
      </label>
            
      <label class="wda_label">
         <span class="wda_title">Website</span>
            <input
               id="_wda_custom_metabox_website"
               name="_wda_testimonial_array_fields[website]"
               class="wda_input"
               type="text"
               value="<?php echo esc_attr( $details['website'] ); ?>">
      </label>

      <?php
   }

	public function save_custom_meta($post_id) {

      // if (isset($_POST)) die(print_r($_POST));     // debug

		if ( ! isset( $_POST['wda_testimonials_meta_nonce'] ) ) {
			return $post_id;
		}
		$nonce = $_POST['wda_testimonials_meta_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'wda_testimonials_meta_box' ) ) {
			return $post_id;
		}

		// autosave, our form has not been submitted
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
      if (!current_user_can('edit_page',$post_id)) {
         return $post_id;
      }

		// Set up an empty array
		$sanitized = array();

		// Loop through each of our fields
		foreach ( $_POST['_wda_testimonial_array_fields'] as $key => $detail ) {
			// Sanitize the data and push it to our new array
			// `wp_filter_post_kses` strips our dangerous server values
			// and allows through anything you can include a post.
			$sanitized[$key] = wp_filter_post_kses( $detail );
		}

		// Save our submissions to the database
		update_post_meta( $post_id, '_wda_testimonial_details_meta_key', $sanitized );
	}


   //
   // front-end UI - shortcode
   //
   public function shortcode_html() {

      ob_start(); // buffer output

      $args = array(
         'post_type' => 'wda_testimonial',
         'posts_per_page' => 10,
      );
      $loop = new WP_Query($args);

      ?>
      <section class="feature_tiles bg_white">
         <h3>What our customers say:</h3>
         <ul>
         <?php
         while($loop->have_posts()) {
            $loop->the_post();
            
		      $details= (array) get_post_meta( get_the_ID(),'_wda_testimonial_details_meta_key', true );
               ?>
               <li>
                  <h5><?php echo get_the_title();?></h5>
                  <p>"<?php echo get_the_content();?>"</p>
                  <div><h5 style="padding:0;"><?php echo isset($details['name']) ? $details['name'] : '';?></h5>
                     <p style="padding:0;text-align:center;">
                     <?php echo isset($details['position']) ? $details['position'] : '';?>,
                     <?php echo isset($details['company']) ? $details['company'] : '';?><br>
                  <a style="font-size:.9rem;" href="<?php echo isset($details['website']) ? $details['website'] : '';?>"><?php echo isset($details['website']) ? $details['website'] : '';?></a></p>
                  <p><?php echo get_post_meta( get_the_ID(), 'wda_testimonial_tagline', true );?></p>
               </div>
               </li>
            <?php
         }
         ?>
         </ul>
      </section>
      <?php

      $buffered_data = ob_get_clean();    // return buffered output
      return $buffered_data;
   }

}


new WedDevAgentTestimonials;