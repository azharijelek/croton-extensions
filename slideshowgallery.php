<?php 

/**
 * Slideshow shortcode usage: [gallery type="slideshow"] or the older [slideshow]
 */
if(!class_exists('zl_add_jetPack_slideshow')){
	class zl_add_jetPack_slideshow {
		public $instance_count = 0;

		function __construct() {
			global $shortcode_tags;

			$needs_scripts = false;

			// Only if the slideshow shortcode has not already been defined.
			if ( ! array_key_exists( 'slideshow', $shortcode_tags ) ) {
				add_shortcode( 'slideshow', array( $this, 'shortcode_callback' ) );
				$needs_scripts = true;
			}

			// Only if the gallery shortcode has not been redefined.
			if ( isset( $shortcode_tags['gallery'] ) && $shortcode_tags['gallery'] == 'gallery_shortcode' ) {
				add_filter( 'post_gallery', array( $this, 'post_gallery' ), 1002, 2 );
				add_filter( 'jetpack_gallery_types', array( $this, 'add_gallery_type' ), 10 );
				$needs_scripts = false;
			}
		}

		/**
		 * Responds to the [gallery] shortcode, but not an actual shortcode callback.
		 *
		 * @param $value An empty string if nothing has modified the gallery output, the output html otherwise
		 * @param $attr The shortcode attributes array
		 *
		 * @return string The (un)modified $value
		 */
		function post_gallery( $value, $attr ) {
			// Bail if somebody else has done something
			if ( ! empty( $value ) )
				return $value;

			// If [gallery type="slideshow"] have it behave just like [slideshow]
			if ( ! empty( $attr['type'] ) && 'slideshow' == $attr['type'] )
				return $this->shortcode_callback( $attr );

			return $value;
		}

		/**
		 * Add the Slideshow type to gallery settings
		 *
		 * @param $types An array of types where the key is the value, and the value is the caption.
		 * @see Jetpack_Tiled_Gallery::media_ui_print_templates
		 */
		function add_gallery_type( $types = array() ) {
			$types['slideshow'] = esc_html__( 'Slideshow', 'jetpack' );
			return $types;
		}

		function settings_select( $name, $values, $extra_text = '' ) {
			if ( empty( $name ) || empty( $values ) || ! is_array( $values ) ) {
				return;
			}
			$option = get_option( $name );
			?>
			<fieldset>
				<select name="<?php echo esc_attr( $name ); ?>" id="<?php esc_attr( $name ); ?>">
					<?php foreach ( $values as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $option ); ?>>
							<?php echo esc_html( $value ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php if ( ! empty( $extra_text ) ) : ?>
					<p class="description"><?php echo esc_html( $extra_text ); ?></p>
				<?php endif; ?>
			</fieldset>
			<?php
		}

		function shortcode_callback( $attr, $content = null ) {
			global $post, $content_width;

			$attr = shortcode_atts( array(
				'trans'     => 'slide',
				'order'     => 'ASC',
				'orderby'   => 'menu_order ID',
				'id'        => $post->ID,
				'include'   => '',
				'exclude'   => '',
				), $attr );

			if ( 'rand' == strtolower( $attr['order'] ) )
				$attr['orderby'] = 'none';

			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( ! $attr['orderby'] )
				$attr['orderby'] = 'menu_order ID';

			// Don't restrict to the current post if include
			$post_parent = ( empty( $attr['include'] ) ) ? intval( $attr['id'] ) : null;

			$attachments = get_posts( array(
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'post_parent'    => $post_parent,
				'order'          => $attr['order'],
				'orderby'        => $attr['orderby'],
				'include'        => $attr['include'],
				'exclude'        => $attr['exclude'],
				) );

			if ( count( $attachments ) < 2 )
				return;

			$gallery_instance = sprintf( "gallery-%d-%d", $attr['id'], ++$this->instance_count );

			$gallery = array();
			foreach ( $attachments as $attachment ) {
				$attachment_image_src = wp_get_attachment_image_src( $attachment->ID, 'full' );
				$attachment_image_src = $attachment_image_src[0]; // [url, width, height]
				$caption = apply_filters( 'jetpack_slideshow_slide_caption', wptexturize( strip_tags( $attachment->post_excerpt ) ), $attachment->ID );

				$gallery[] = (object) array(
					'src'     => (string) esc_url_raw( $attachment_image_src ),
					'id'      => (string) $attachment->ID,
					'caption' => (string) $caption,
					);
			}

			$max_width = intval( get_option( 'large_size_w' ) );
			$max_height = 450;
			if ( intval( $content_width ) > 0 )
				$max_width = min( intval( $content_width ), $max_width );

			$color = Jetpack_Options::get_option( 'slideshow_background_color', 'black' );

			$js_attr = array(
				'gallery'  => $gallery,
				'selector' => $gallery_instance,
				'width'    => $max_width,
				'height'   => $max_height,
				'trans'    => $attr['trans'] ? $attr['trans'] : 'slide',
				'color'    => $color,
				);

			// Show a link to the gallery in feeds.
			if ( is_feed() )
				return sprintf( '<a href="%s">%s</a>',
					esc_url( get_permalink( $post->ID ) . '#' . $gallery_instance . '-slideshow' ),
					esc_html__( 'Click to view slideshow.', 'jetpack' )
					);

			return $this->slideshow_js( $js_attr );
		}

		/**
		 * Render the slideshow js
		 *
		 * Returns the necessary markup and js to fire a slideshow.
		 *
		 * @uses $this->enqueue_scripts()
		 */
		function slideshow_js( $attr ) {
			

			$output = '';

			//$output .= '<p class="jetpack-slideshow-noscript robots-nocontent">' . esc_html__( 'This slideshow requires JavaScript.', 'jetpack' ) . '</p>';
			ob_start();
			$items = $attr['gallery'];
				/*echo '<pre>'; 
				print_r($items);
				echo '</pre>';*/
				if($items){
					echo '<div class="owl-carousel post-gallery">';
					foreach ($items as $item) {
						$item_id = $caption = '';

						$item_id =  $item->id;
						$caption =  $item->caption;

						$image_size = 'post_thumb';
						$rawurl = wp_get_attachment_image_src( $item_id, $image_size );
						$linkto = wp_get_attachment_url( $item_id ); 
						$image_url = $rawurl[0];

						echo '<div>';
						echo '<a href="'.$linkto.'">';
						echo '<img src="'.$image_url.'" alt="" />';
						echo '</a>';
						if($caption){
							echo '<div class="zl_post_gallery_caption">'.$caption.'</div>';
						}
						echo '</div>';
					}
					echo '</div>';
				}
				$output .= ob_get_clean();
				$output .= '';
				

				return $output;
			}

			

		/**
		 * Actually enqueues the scripts and styles.
		 */
		function enqueue_scripts() {
			static $enqueued = false;

			if ( $enqueued )
				return;

			//wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );

			$enqueued = true;
		}

		public static function init() {
			$gallery = new zl_add_jetPack_slideshow;
		}
	}
}
add_action( 'init', array( 'zl_add_jetPack_slideshow', 'init' ) );

?>