<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Shows the Rma Contributor Box
 *
 * @return string Rma Contibutor Box HTML .
 */
function rma_add_contributor_box(){
	return Rma_Contributor_Box::view_posts() ;
}

/**
 * Rma Contributor Box class .
 */
class Rma_Contributor_Box {

	/** * Constructor */
	public function __construct() {
		// Load public-facing style sheet .
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Display the box .
		add_filter( 'the_content', array( $this, 'rma_display_contributors' ), 100 );
	}

	/**
	 * Register and enqueue public-facing style sheet .
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( !is_single() and !is_home() ) {
			return;
		}

		wp_register_style( 'rma-fontawesome', Rapo_MultiAuthors::get_assets_url() . 'css/font-awesome.min.css' );
		wp_enqueue_style( 'rma-fontawesome' );

		wp_register_style( 'rma-styles', Rapo_MultiAuthors::get_assets_url() . 'css/rma-contributor-box.min.css', array(), Rapo_MultiAuthors::RMA_VERSION, 'all' );

	}

	/**
	 * Display Contributors based on settings
	 *
	 * @param array $settings Rapo MultiAuthors settings .
	 *
	 * @return string Rapo MultiAuthors HTML .
	 */
 public static function rma_display_contributors( $content ) {
	 $settings = get_option( 'rma_settings' );

	 if( $settings['display'] == 'home_posts' ) {
		if ( is_home() ) {
			 return $content . self::view_home_posts( $content );
		} elseif ( is_single() ) {
			 return $content . self::view_posts( $content );
		}
	 } elseif( $settings['display'] == 'posts' ) {
		if ( is_single() ) {
			 return $content . self::view_posts( $content );
		}
	 }
	 return null;
 }

	/**
	 * HTML of the box for home page
	 *
	 * @param string $content Content of post
	 *
	 * @return string Rapo MultiAuthors HTML .
	 */
	public static function view_home_posts( $content ) {

		global $post;

		$settings = get_option( 'rma_settings' );

		// Load the styles .
		wp_enqueue_style( 'rma-styles' ); 

		// Set the gravatar size .
		$gravatar = ! empty( $settings['gravatar'] ) ? $settings['gravatar'] : 70;

		// Set the styles .
		$styles = sprintf(
			'background: %1$s; border: %2$spx %3$s %4$s; color: %5$s',
			$settings['background_color'],
			$settings['border_size'],
			$settings['border_style'],
			$settings['border_color'],
			$settings['text_color']
		 );

		// get the postmeta values of the contributors for the post
		$postmeta = maybe_unserialize( get_post_meta( $post->ID, 'rma-contributors', true ) );

		if( empty( $postmeta ) ) {
		 return;
		}

		$content .= '<div class="rma-wrap" style="' . $styles . '">'; // start rma-wrap div '<div id="rapo-multiauthors" style="' . $styles . '">';

		$content .= '<span class="rma-authorname">Contributors :</span>'; // Title

		foreach ( $postmeta as $author ) {
	 $author = get_user_by( 'login', $author );
		// contributor box name
		$content .= '<span><a href="' . esc_url( get_author_posts_url( $author->ID ) ) . '" title="' . esc_attr( __( 'All posts by', 'rapo-multiauthors' ) . ' ' . get_the_author_meta( 'display_name', $author->ID ) ) . '" rel="author" style="color:' . $settings['text_color'] . ';">' . get_the_author_meta( 'display_name', $author->ID ) . '</a></span> ';

	}
	$content .= '</div>'; // end of rma-wrap div

		return $content;
 }
	/**
	 * HTML of the box .
	 *
	 * @param string $content Content of post
	 *
	 * @return string Rapo MultiAuthors HTML .
	 */
	public static function view_posts( $content ) {

		global $post;

		$settings = get_option( 'rma_settings' );

		// Load the styles .
		wp_enqueue_style( 'rma-styles' ); 

		// Set the gravatar size .
		$gravatar = ! empty( $settings['gravatar'] ) ? $settings['gravatar'] : 70;

		// Set the styles .
		$styles = sprintf(
			'background: %1$s; border: %2$spx %3$s %4$s; color: %5$s',
			$settings['background_color'],
			$settings['border_size'],
			$settings['border_style'],
			$settings['border_color'],
			$settings['text_color']
		 );

		// Set the title styles .
		$titlestyles = sprintf(
			'background: %1$s; border: %2$spx %3$s %4$s; color: %5$s',
			$settings['border_color'],
			$settings['border_size'],
			$settings['border_style'],
			$settings['border_color'],
			$settings['title_color']
		 );

		// Set the link styles .
		$linkstyles = sprintf(
			'color: %1$s; ',
			$settings['text_color']
		 );


		// get the postmeta values of the contributors for the post
		$postmeta = maybe_unserialize( get_post_meta( $post->ID, 'rma-contributors', true ) );

		if( empty( $postmeta ) ) {
			return;
		}

		$content .= '<div class="rma-wrap" style="' . $styles . '">'; // start rma-wrap div '<div id="rapo-multiauthors" style="' . $styles . '">';

		$content .= '<h2 style="' . $titlestyles . '">Contributors</h2>'; // Title

		foreach ( $postmeta as $author ) {
		 	$author = get_user_by( 'login', $author );

			 // Set the social icons
			 $social = apply_filters( 'rma_social_media', array(
				'facebook'           => get_user_meta( $author->ID, 'facebook' ),
				'link'               => array( get_the_author_meta( 'user_url', $author->ID ) ),
				'twitter'            => get_user_meta( $author->ID, 'twitter' ),
				'google-plus-circle' => get_user_meta( $author->ID, 'googleplus' ),
				'linkedin'           => get_user_meta( $author->ID, 'linkedin' ),
				'flickr'	         => get_user_meta( $author->ID, 'flickr' ),
				'tumblr'	         => get_user_meta( $author->ID, 'tumblr' ),
				'vimeo'		         => get_user_meta( $author->ID, 'vimeo' ),
				'youtube'	         => get_user_meta( $author->ID, 'youtube' ),
				'instagram'	         => get_user_meta( $author->ID, 'instagram' ),
				'pinterest'	         => get_user_meta( $author->ID, 'pinterest' ),
			 ) );

			// contributor box gravatar
			$content .= '<div class="row rma-gravatar img-responsive"><center>' . get_avatar( $author->ID, $gravatar ) . '</center></div>';

			// contributor box name
			$content .= '<div class="rma-authorname"><a href="' . esc_url( get_author_posts_url( $author->ID ) ) . '" title="' . esc_attr( __( 'All posts by', 'rapo-multiauthors' ) . ' ' . get_the_author_meta( 'display_name', $author->ID ) ) . '" rel="author" style="color:' . $settings['text_color'] . ';">' . get_the_author_meta( 'display_name', $author->ID ) . '</a></div>';

			// contributor box description
			$content .= apply_filters( 'rma_author_description', '<div class="rma-desc">' . get_the_author_meta( 'description', $author->ID ) . '</div>' );

			// social media icons
			$content .= '&nbsp; &nbsp;';

			foreach ( $social as $key => $value ) {
				if ( ! empty( $value[0] ) ) {
					$content .= '<a style="' . $linkstyles . '" href="' . esc_url( $value[0] ) . '"><i class="fa fa-' . $key . '" aria-hidden="true"></i></a>';
				}
			}

		 	// contributor box clearfix
			$content .= '<div class="clearfix"></div>';
		}

		$content .= '</div>'; // end of rma-wrap div

		return $content;
 	}
}

new Rma_Contributor_Box();
