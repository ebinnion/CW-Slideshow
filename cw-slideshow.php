<?php
/**
 * Plugin Name: Crane|WEST Slideshow
 * Plugin URI: http://crane-west.com
 * Description: Plugin that provides basic slideshow functionality with the Nivo jQuery plugin.
 * Version: 0.1
 * Author: Eric Binnion
 * Author URI: http://manofhustle.com
 * License: GPLv2 or later
 * Text Domain: cw-slideshow
 */

class CW_Slideshow {

	function __construct( $args = array() ) {

		$defaults = array(
			'enqueue_css' => true,
			'enqueue_js'  => true,
			'resize'      => false,
		);

		$this->args = wp_parse_args( $args, $defaults );

		add_action( 'init',                                    array( $this, 'init' ), 1 );
		add_action( 'manage_cw_slideshow_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
		add_action( 'wp_enqueue_scripts',                      array( $this, 'add_nivo_scripts' ) );

		add_filter( 'cmb_meta_boxes',                          array( $this, 'init_meta_boxes' ) );
		add_filter( 'manage_cw_slideshow_posts_columns',       array( $this, 'add_slide_columns' ) );

		add_shortcode( 'cw-slideshow',                         array( $this, 'do_cw_slideshow' ) );
	}

	/**
	 * This method is called on the init WordPress action
	 */
	function init() {
		$this->init_cpts();

		if( ! class_exists( 'cmb_Meta_Box' ) ) {
			require_once( 'lib/metabox/init.php' );
		}

		require_once( 'lib/aq_resizer.php' );
	}

	function init_meta_boxes( $meta_boxes ) {
		$prefix = '_cw_slides_';

		$meta_boxes[] = array(
			'id'         => 'test_metabox',
			'title'      => 'Slideshow Information',
			'pages'      => array('cw_slideshow'), // post type
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			'fields'     => array(
				array(
				    'id'          => $prefix . 'slideshow',
				    'type'        => 'group',
				    'description' => '',
				    'options'     => array(
				        'group_title'   => 'Slide {#}', // since version 1.1.4, {#} gets replaced by row number
				        'add_button'    => 'Add another slide',
				        'remove_button' => 'Remove slide',
				        'sortable'      => true, // beta
				    ),
				    // Fields array works the same, except id's only need to be unique for this group. Prefix is not needed.
				    'fields'      => array(
				        array(
				            'name' => 'Slide Title',
				            'id'   => 'title',
				            'type' => 'text',
				            // 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
				        ),
				        array(
				            'name' => 'Link',
				            'id'   => 'link',
				            'type' => 'text_url',
				        ),
				        array(
				            'name' => 'Image',
				            'id'   => 'image',
				            'type' => 'file',
				        ),
				        array(
				            'name' => 'Image Caption',
				            'id'   => 'image_caption',
				            'type' => 'textarea_small',
				        ),
				    ),
				),
			),
		);

		return $meta_boxes;
	}

	function add_slide_columns( $cols ) {
		return array(
			'cb'        => '<input type="checkbox" />',
    		'title'     => 'Title',
    		'shortcode' => esc_html__( 'Shortcode', 'cw-slideshow' ),
    		'date'      => 'Date',
		);
	}

	function custom_columns( $column, $post_id ) {
		echo '[cw-slideshow id="'.$post_id.'"]';
	}

	function do_cw_slideshow( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0
			),
			$atts,
			'cw-slideshow'
		);

		if( $atts['id'] > 0 ) {
			return $this->generate_slideshow( $atts['id'] );
		}
	}

	function add_nivo_scripts() {
		wp_enqueue_script(
			'nivo_slider',
			plugins_url( 'js/jquery.nivo.slider.pack.js', __FILE__ ),
			array( 'jquery' )
		);

		wp_enqueue_script(
			'cw_slideshow',
			plugins_url( 'js/cw_slideshow.js', __FILE__ ),
			array( 'jquery', 'nivo_slider' )
		);

		wp_enqueue_style(
			'nivo_slider',
			plugins_url( 'css/nivo-slider.css', __FILE__ )
		);
	}

	function generate_slideshow( $slideshow_id, $args = array() ) {
		$entries = get_post_meta( $slideshow_id, '_cw_slides_slideshow', true );

		if ( ! empty( $entries ) ) {
			echo '<div class="cw-slider nivoSlider">';
				$captions = '';

				foreach ( (array) $entries as $key => $entry ) {

					// Initialize all values to empty string
					$img = $title = $link = $caption = '';

					if ( isset( $entry['title'] ) ) {
						$title = $entry['title'];
					}

					if ( isset( $entry['link'] ) ) {
						$link = $entry['link'];
					}

					if ( isset( $entry['image'] ) ) {
						$img = $entry['image'];

						if ( false != $this->args['resize'] ) {
							$img = aq_resize( $img, $this->args['resize']['width'], $this->args['resize']['height'], true, true, true );
						}
					}

					if( isset( $entry['image_caption'] ) ) {
						$caption = $entry['image_caption'];
					}

					$caption = isset( $entry['image_caption'] ) ? wpautop( $entry['image_caption'] ) : '';

					if ( ! empty( $caption ) ) {
						echo "<img src='{$img}' alt='{$title}' title='#slide-{$key}'>";
						$captions .= "<div class='nivo-html-caption' id='slide-{$key}'><h3>{$title}</h3> {$caption}</div>";
					} else {
						echo "<img src='{$img}' alt='{$title}'>";
					}
				}

			echo '</div>';

			if( ! empty( $captions ) ) {
				echo $captions;
			}
		}
	}

	/**
	 * This method is used to init custom post types used for the Rider Raider Sports Site
	 */
	private function init_cpts() {
		$field_args = array(
			'labels' => array(
				'name'               => 'Slideshows',
				'singular_name'      => 'Slideshow',
				'add_new'            => 'Add Slideshow',
				'add_new_item'       => 'Add Slideshow',
				'edit_item'          => 'Edit Slideshow',
				'new_item'           => 'New Slideshow',
				'view_item'          => 'View Slideshow',
				'search_items'       => 'Search Slideshows',
				'not_found'          => 'No slideshows found',
				'not_found_in_trash' => 'No slideshows found in trash'
			),
			'public'          => true,
			'show_ui'         => true,
			'capability_type' => 'post',
			'hierarchical'    => true,
			'rewrite'         => true,
			'menu_position'   => 20,
			'supports'        => array('title')
		);

		register_post_type( 'cw_slideshow', $field_args );
	}
}

new CW_Slideshow();