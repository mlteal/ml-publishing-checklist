<?php
/**
 * The Publishing Checklist edit screen functions
 *
 * Many pieces forked from the Post Type Requirements Checklist by David Winter
 * (https://wordpress.org/plugins/post-type-requirements-checklist/)
 */

/**
 * @package ML_Publishing_Checklist
 */
if ( ! class_exists( 'ML_PC_Edit' ) ) {
	class ML_PC_Edit {

		/**
		 * Initialize the plugin by loading admin scripts & styles and adding a
		 * settings page and menu.
		 *
		 * @since     1.0
		 */
		function __construct() {

			// Fire functions
			add_action( 'admin_enqueue_scripts', array( $this, 'is_edit_page' ) );
			add_action( 'post_submitbox_misc_actions', array( $this, 'insert_publish_metabox_checklist' ), 10, 1 );
			add_action( 'save_post', array( $this, 'publishing_checklist_meta_save' ) );
		}

		/**
		 * Get post type
		 *
		 * @return string Post type
		 *
		 * @since 1.0
		 */
		public function get_post_type() {

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( isset( $_REQUEST['post_id'] ) ) {
					$post = get_post( $_REQUEST['post_id'] );

					return $post->post_type;
				}
			}

			$screen = get_current_screen();

			return $screen->post_type;

		} // end get_post_type

		/**
		 * enqueue styles
		 *
		 * @since 1.0
		 */
		public function is_edit_page() {

			global $current_screen;  // Makes the $current_screen object available
			if ( $current_screen && ( $current_screen->base == "edit" || $current_screen->base == "post" ) ) {
				// only bother including the ML_Checklist_Items file if we're on a screen that uses the checklist
				include_once( ML_CHECKLIST__PLUGIN_DIR . 'class-ml-checklist-items.php' );

				// enqueue our stylesheet
				wp_enqueue_style( 'ml-publishing-checklist-style', ML_CHECKLIST__PLUGIN_URL . 'assets/css/publishing-checklist.css' );

			}

		} // end is_edit_page

		/**
		 * Insert Publish Metabox Checklist
		 *
		 * @since 1.0
		 *
		 * @param object $post The post object
		 */
		public function insert_publish_metabox_checklist( $post ) {
			$options = ML_Publishing_Checklist::get_available_checklist_items();
			wp_nonce_field( basename( __FILE__ ), 'pc_nonce' );

			$pc_meta = get_post_meta( $post->ID, 'ml_publishing_checklist', true );

			// print the JS for the hide/show publish button and element watcher
			echo static::get_list_watcher_js();

			// Section title
			echo '<div class="postbox">';
			echo '<button type="button" class="handlediv button-link" aria-expanded="true">
<span class="screen-reader-text">Toggle panel: Publishing Checklist</span>
<span class="toggle-indicator" aria-hidden="true"></span></button>';
			// #ml_pc_completed will be dynamically updated by the JS that checks # of checkboxes checked
			echo '<h3 class="hndle ui-sortable-handle"><span id="ml_pc_completed"></span><span>Publishing Checklist</span></h3>';
			echo '<div class="inside">';

			echo '<div id="requirements_list">';

			$required_checkboxes = '';
			$optional_checkboxes = '';

			foreach ( $options as $key => $atts ) {

				if ( is_array( $pc_meta ) ) {
					$initial_checked = ( in_array( $key, $pc_meta ) ? 'checked="checked"' : '' );
				} else {
					$initial_checked = '';
				}


				// we need to make sure "Featured Image" doesn't show up for contributor level users, as they don't have access to it
				if ( ! current_user_can( 'publish_posts' ) && 'feat_img' == $key ) {
					continue;
				}

				// print the checkbox and label markup
				$element = static::get_checkbox_markup( $key, $atts, $initial_checked );

				/**
				 * Verify that the item isn't marked as editable and
				 * has a working function set in its get_script attribute,
				 * then print the JS for it.
				 */
				if ( ! empty( $atts['element'] ) && method_exists( 'ML_Checklist_Items', $key ) ) {
					$element .= ML_Checklist_Items::check_on_pageload( $key, $atts );
					$element .= ML_Checklist_Items::$key( $key );
				} elseif (
					! empty( $atts['get_script'] )
					&& is_array( $atts['get_script'] )
					&& method_exists( $atts['get_script'][0], $atts['get_script'][1] )
				) {
					$element .= call_user_func( array( $atts['get_script'][0], $atts['get_script'][1] ) );
				} elseif (
					! empty( $atts['get_script'] )
					&& is_string( $atts['get_script'] )
					&& function_exists( $atts['get_script'] )
				) {
					$element .= call_user_func( $atts['get_script'] );
				}


				if ( ! empty( $atts['required'] ) && true == $atts['required'] ) {
					$required_checkboxes .= $element;
				} else {
					$optional_checkboxes .= $element;
				}

			} // end foreach

			echo '<div class="ml-pc-section required">';
			echo $required_checkboxes;
			echo '</div>';

			echo '<h4><span id="ml_pc_opt_completed"></span>Article Enhancements (Optional)</h4>';
			echo '<div class="ml-pc-section optional">';
			echo $optional_checkboxes;
			echo '</div>';

			echo '<span id="rlbot">' . __( 'Drafts may be saved above', 'aptrc' ) . '</span>';

			echo '</div>';
			echo '</div>'; // end .inside
			echo '</div>'; // end .postbox

		} // end insert_publish_metabox_checklist()

		/**
		 * Return the hide/show publish button and element watcher JS
		 *
		 * @return string
		 */
		static function get_list_watcher_js() {
			ob_start();
			?>
			<script>
				function hideShowPublish() {
					//hide or shows publish box based on whether all the boxes on the page are checked
					var number = jQuery( "#requirements_list input[type='checkbox'].required" );
					var numberChecked = jQuery( "#requirements_list input[type='checkbox'].required:checked" );

					var numberOptional = jQuery( "#requirements_list input[type='checkbox'].optional" );
					var numberOptionalChecked = jQuery( "#requirements_list input[type='checkbox'].optional:checked" );

					// Only display the "Update" button if either all checkboxes are checked.
					if ( number.length == numberChecked.length ) {
						jQuery( "#publish" ).prop( 'disabled', false );
						jQuery( "#rlbot" ).slideUp( "slow" );
						jQuery( "#requirements_list" ).css( "background-color", "transparent" );
					} else {
						jQuery( "#publish" ).prop( 'disabled', true );
						jQuery( "#rlbot" ).slideDown( "slow" );
						jQuery( "#requirements_list" ).css( "background-color", "#ffffe6" );
					}

					if ( number.length > 0 ) {
						jQuery( "#ml_pc_completed" ).html( numberChecked.length + '/' + number.length );

						if ( numberChecked.length == number.length ) {
							jQuery( "#ml_pc_completed" ).addClass( 'complete' );
						} else {
							jQuery( "#ml_pc_completed" ).removeClass( 'complete' );
						}
					}

					if ( numberOptional.length > 0 ) {
						jQuery( "#ml_pc_opt_completed" ).html( numberOptionalChecked.length + '/' + numberOptional.length );

						if ( numberOptionalChecked.length == numberOptional.length ) {
							jQuery( "#ml_pc_opt_completed" ).addClass( 'complete' );
						} else {
							jQuery( "#ml_pc_opt_completed" ).removeClass( 'complete' );
						}
					}

				}

				jQuery( "#rlbot" ).fadeIn();
				// disable publishing until we've determined items are checked
				jQuery( "#publish" ).prop( 'disabled', true );

				jQuery( document ).ready( function() {
					// add a watcher for all #requiremenets_list items regardless of whether they're required
					// this allows us to dynamically update elements on actions like change, keyup, etc
					jQuery( "#requirements_list input[type='checkbox']" ).each( function() {
						if ( 'onChange' == jQuery( this ).data( 'action' ) && jQuery( this ).data( 'callback' ).length >= 1 && jQuery( this ).data( 'observe' ).length >= 1 ) {
							jQuery( jQuery( this ).data( 'observe' ) ).on( 'change keyup DOMNodeInserted DOMNodeRemoved', { element: jQuery( this ).data( 'observe' ) }, window[jQuery( this ).data( 'callback' )] );
						}
					} );

					setInterval( hideShowPublish, 1000 );

				} );
			</script>
			<?php

			return ob_get_clean();
		}

		/**
		 * Prints a checkbox element for the requirements list
		 *
		 * @param $key
		 * @param $atts
		 * @param $initial_checked
		 *
		 * @return string
		 */
		static function get_checkbox_markup( $key, $atts, $initial_checked ) {
			if ( ! empty( $atts['element'] ) ) {
				$element = $atts['element'];
			} else {
				$element = '';
			}

			if ( ! empty( $atts['label'] ) ) {
				$label = $atts['label'];
			} else {
				$label = ucwords( str_replace( '_', ' ', $key ) );
			}

			if ( ! empty( $atts['required'] ) && true === $atts['required'] ) {
				$required = 'required';
			} else {
				$required = 'optional';
			}

			// handle editable vs non-editable checkboxes
			if ( ! empty( $atts['type'] ) && 'editable' == $atts['type'] ) {
				$html = '<span class="reqcb editable">';
				$html .= sprintf( '
				<input name="ml_publishing_checklist[]" id="%1$s_checkbox" value="%1$s"
				       data-action="onChange" data-callback="ml_check_%1$s" data-observe="%4$s" 
				       type="checkbox" %3$s class=" %5$s " />
				<label for="%1$s_checkbox">%2$s</label>',
					$key, $label, $initial_checked, $element, $required );
			} else {
				$html = '<span class="reqcb">';
				$html .= sprintf( '
				<input name="ml_publishing_checklist[]" id="%1$s_checkbox" value="%1$s"
				       data-action="onChange" data-callback="ml_check_%1$s" data-observe="%4$s" 
				       type="checkbox" %3$s onclick="return false;" onkeydown="return false;"
				       class=" %5$s " />
				<label for="%1$s_checkbox">%2$s</label>',
					$key, $label, $initial_checked, $element, $required );
			}

			if ( ! empty( $atts['description'] ) ) {
				$html .= '<a class="tooltip dashicons dashicons-editor-help" title="' . $atts['description'] . '"></a>';
			}

			$html .= '</span>'; // close the containing span


			return $html;
		}


		/**
		 * Saves the Editors Checklist meta
		 *
		 * @param $post_id
		 */
		function publishing_checklist_meta_save( $post_id ) {
			// check save status
			$is_autosave    = wp_is_post_autosave( $post_id );
			$is_revision    = wp_is_post_revision( $post_id );
			$is_valid_nonce = ( isset( $_POST['ml_pc_nonce'] ) && wp_verify_nonce( $_POST['ml_pc_nonce'], basename( __FILE__ ) ) ) ? 'true' : 'false';

			// exits script depending on save status
			if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
				return;
			}

			// and then update the Editors Checklist post meta with anything that's checked
			if ( isset( $_POST['ml_publishing_checklist'] ) ) {
				update_post_meta( $post_id, 'ml_publishing_checklist', $_POST['ml_publishing_checklist'] );
			} else {
				update_post_meta( $post_id, 'ml_publishing_checklist', '' );
			}

		} // end publishing_checklist_meta_save

	}
}
