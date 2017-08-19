<?php
/**
 * Functions that print individual JS snippets on
 * the edit screen for checking an element's completeness.
 */

/**
 * @package ML_Publishing_Checklist
 */
class ML_Checklist_Items {

	static $func_prefix = 'ml_check_';

	static function check_on_pageload( $key, $atts ) {
		// If there's no element defined, don't bother returning any of the JS
		if ( empty( $atts['element'] ) ) {
			return '';
		}

		ob_start();
		?>
		<script>
			jQuery( document ).ready( function() {
				// run initial check on page load
				var ml_initial_<?php echo $key; ?> = {
					data: { element: '<?php echo $atts['element']; ?>' }
				}
				<?php echo self::$func_prefix . $key; ?>( ml_initial_<?php echo $key; ?> );
			} );
		</script>

		<?php
		return ob_get_clean();
	}

	static function title( $key ) {
		ob_start();
		?>
		<script>
			function <?php echo self::$func_prefix . $key; ?>( event ) {
				var ml_title_val = jQuery( event.data.element ).val();

				if ( ml_title_val.length < 1 ) {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', false );
				} else {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', true );
				}
			}
		</script>
		<?php
		return ob_get_clean();
	}

	static function cat( $key ) {
		ob_start();
		?>
		<script>
			function <?php echo self::$func_prefix . $key; ?>( event ) {
				// make sure there's 1 or more category checked
				if ( jQuery( event.data.element + " input[type='checkbox']:checked" ).length < 1 ) {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', false );
				} else {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', true );
				}
			}
		</script>
		<?php
		return ob_get_clean();
	}

	static function feat_img( $key ) {
		ob_start();
		?>
		<script>
			function <?php echo self::$func_prefix . $key; ?>( event ) {
				if ( jQuery( event.data.element ).find( 'img' ).length < 1 ) {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', false );
				} else {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', true );
				}
			}
		</script>
		<?php
		return ob_get_clean();
	}

	static function tags( $key ) {
		ob_start();
		?>
		<script>
			function <?php echo self::$func_prefix . $key; ?>( event ) {
				if ( event.type == 'DOMNodeRemoved' && jQuery( event.data.element ).find( 'span' ).length <= 1 ) {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', false );
				} else if ( jQuery( event.data.element ).find( 'span' ).length >= 1 ) {
					jQuery( "input[type='checkbox'][id='<?php echo $key ?>_checkbox']" ).prop( 'checked', true );
				}
			}
		</script>
		<?php
		return ob_get_clean();
	}

} // end class ML_Checklist_Items
