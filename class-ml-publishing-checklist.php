<?php
/**
 * Primary class for the Writers Checklist option and settings instantiation
 *
 */
//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
if ( ! class_exists( 'ML_Publishing_Checklist' ) ) {
	class ML_Publishing_Checklist {

		public $admin_url;

		public function __construct() {

			$this->admin_url = plugin_dir_url( __FILE__ );

			if ( current_user_can( 'manage_options' ) ) {
				// TODO: build Site Options page
			}

			// disable the meta box if the checklist is disabled via the Editors Checklist options page
			if ( get_option( 'ml_option_publishing_checklist_enable' ) != 'disabled' ) {
				// load and instantiate the checklist only if we're on post_type=post
				add_action( 'current_screen', array( $this, 'checklist_init' ) );
			}
		} // end constructor

		/**
		 * Initialize the class if we're on the correct post type screen
		 */
		function checklist_init() {
			// extra failsafe in case the function we rely on doesn't load
			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( 'post' === $screen->post_type ) {
				include_once( ML_CHECKLIST__PLUGIN_DIR . 'class-ml-pc-edit.php' );
				if ( class_exists( 'ML_PC_Edit' ) ) {
					new ML_PC_Edit(); // instance of Writers Checklist
				}
			}
		}

		/**
		 * Get the array of predefined checklist items
		 *
		 * The key in the checklist items array will be used to
		 * call the related "script" function in ML_Checklist_Items class.
		 * If no function is found, the checkbox won't display.
		 *
		 * @return array|mixed|void
		 */
		public static function get_registered_checklist_items() {

			$default_options = array(
				/**
				 * The array item's key is used as a unique identifier
				 * for things like the post meta that's saved for
				 * the checklist.
				 */
				'title'    => array(
					'description' => 'Make sure the title is filled out',
					/**
					 * The element ID is an optional key/value only used by
					 * the plugin's programmatic function that checks checklist
					 * items on initial page load.
					 */
					'element'     => '#title',
					/**
					 * The function that returns a javascript snippet w/ function
					 * to check whether this checklist item. The function must accept
					 * the $key value and follow the clearly setup structure found in
					 * the examples in ML_Checklist_Items class.
					 *
					 * string|array `check_script` Accepts a function name (string)
					 *                             or array of 'Class_Name', 'function_name'
					 *                             (expected to be a static function)
					 */
					'get_script'  => array( get_called_class(), 'title' ),
					'label'       => 'Post Title',
					'required'    => true,
				),
				'feat_img' => array(
					'description' => 'Featured image added',
					'element'     => '#postimagediv',
					'get_script'  => array( get_called_class(), 'feat_img' ),
					'label'       => 'Featured Image',
					'required'    => true,
				),
				'cat'      => array(
					'description' => 'At least 1 Category is selected',
					'element'     => '.categorychecklist',
					'get_script'  => array( get_called_class(), 'cat' ),
					'label'       => 'Category',
					'required'    => true,
				),
				'tags'     => array(
					'description' => 'At least 1 Tag is selected',
					'element'     => '.tagchecklist',
					'get_script'  => array( get_called_class(), 'tags' ),
					'label'       => 'Tags',
					'required'    => false,
				),
				'proofed'  => array(
					'description' => 'Previewed and proofread',
					'label'       => 'Proofed',
					'required'    => false,
					'type'        => 'editable',
				),
				'ext_link' => array(
					'description' => 'One external link to a quality source',
					'label'       => 'External Link in Content',
					'required'    => false,
					'type'        => 'editable',
				),
				'int_link' => array(
					'description' => 'One internal link to the category hub page the article belongs to',
					'label'       => 'Internal Link in Content',
					'required'    => false,
					'type'        => 'editable',
				),

			);

			$seo_checklist = array(
				'seo' => array(
					'description' => 'All available SEO options filled out',
					'get_script'  => array( get_called_class(), 'seo' ),
					'label'       => 'SEO Options Filled Out',
					'required'    => false,
					'type'        => 'editable',
				)
			);

			$checklist_items = array_merge( $default_options, $seo_checklist );

			if ( ! isset( $checklist_items ) ) {
				$checklist_items = $default_options;
			}

			return apply_filters( 'ml_registered_checklist_items', $checklist_items );
		}

		public static function get_available_checklist_items() {
			$cached = wp_cache_get( 'ml_available_checklist_items', 'ml_publishing_checklist' );
			if ( $cached ) {
				return $cached;
			}

			$checklist_items = self::get_registered_checklist_items();
			$site_option     = get_option( 'ml_option_publishing_checklist_available' );
			//
			/**
			 * sm_options multiselect is doing something goofy when you try to deselect
			 * previously selected items and re-save... accounting for that (╯°□°）╯︵ ┻━┻
			 *
			 * in short... don't set a `0` key for anything because of this multiselect bug
			 */
			if ( isset( $site_option[0] ) && 1 === count( $site_option ) ) {
				$site_option = false;
			}

			// check to see if there's a site option dictating which items are "active"
			// if there is no option or if the option is empty, all items will be returned
			if ( false !== $site_option && ! empty( $site_option ) ) {
				$available_items = array();

				foreach ( $site_option as $item_key ) {
					// double check that the item key in the site option is actually available
					if ( empty( $checklist_items[ $item_key ] ) ) {
						continue;
					}

					$available_items[ $item_key ] = $checklist_items[ $item_key ];
				}

				$checklist_items = $available_items;
			}

			wp_cache_set( 'ml_available_checklist_items', $checklist_items, 'ml_publishing_checklist' );

			return $checklist_items;
		}

		/**
		 * Contents of the Editors Checklist meta box
		 */
		public function publishing_checklist_meta_callback( $post ) {
			wp_nonce_field( basename( __FILE__ ), 'pc_nonce' );
			$pc_stored_meta = get_post_meta( $post->ID, 'ml_publishing_checklist', true );

			$checklist_items = self::get_available_checklist_items();

			?>
			<div class="publishing-checklist-meta">
				<p class="checklist-info">
					<em><?php _e( 'Use this checklist to make sure you\'ve completed the key components of your article before publishing.', 'ml_vip' ) ?></em>
				</p>

				<ul class="checklist">

					<?php
					// Build a list of checklist items and see if they're checked off already by looking at the
					// key from the get_available_checklist_items() array

					foreach ( $checklist_items as $key => $value ) {
						echo '<li><label for="' . $key . '">';
						echo sprintf( '<input type="checkbox" name="ml_publishing_checklist[]" id="%1$s" value="%1$s"', $key );

						// check to see if the current item is included in the saved ml_publishing_checklist post meta array
						if ( ! empty( $pc_stored_meta ) && in_array( $key, maybe_unserialize( $pc_stored_meta ) ) ) {
							echo 'checked="checked"';
						}

						echo ' /><span>';
						echo $value;
						echo '</span></label></li>';
					}
					?>
				</ul>
			</div>
			<?php

		} // end publishing_checklist_meta_callback( $post)

	} // END class ML_Publishing_Checklist
}
