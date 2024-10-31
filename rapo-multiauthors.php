<?php
/*
 * Plugin Name: Rapo MultiAuthors
 * Plugin URI: http://wordpress.org/extend/plugins/rapo-multiauthors/
 * Description: Enable display of multiple authors for a post
 * Version: 1.0
 * Author: Menaka S.
 * Author URI: http://smenaka.rapo.in/
 * Author Email: menakas@yahoo.com
 * Text Domain: rapo-multiauthors
 * Domain Path: /lang
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 2 of the License,
 * or ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if it is accessed directly.*/
}

if ( ! class_exists( 'Rapo_MultiAuthors' ) ) {

/* The Main class of Rapo MultiAuthors*/

class Rapo_MultiAuthors {
	/* Plugin Version
	 * @var string
	 */
	const RMA_VERSION ='1.0';
	
	/* Instance of this class
	 * @var object
	 */
	protected static $instance = null;
	
	/* Constructor for the object - initialize */
	
	private function __construct() {
		/* Load plugin text domain */
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$this->admin_includes();
		}
		
		$this->includes();
		
		  /* Fire the contributors meta box setup function on the post editor screen. */
		add_action( 'load-post.php', array( $this,'rma_post_meta_boxes_setup' ) );
		
		add_action( 'load-post-new.php', array( $this,'rma_post_meta_boxes_setup' ) );
	}
	
	/**
	 * Return an instance of this class.
	 * @return object
	 */
	public static function get_instance() {
	// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
	
		return self::$instance;
	}
	
	/**
	 * Get assets url.
	 * @return string
	 */
	public static function get_assets_url() {
		return plugins_url( 'assets/', __FILE__ );
	}
	
	/**
	 * Load the plugin text domain for translation.
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'rapo-multiauthors' );
	
		load_textdomain( 'rapo-multiauthors', trailingslashit( WP_LANG_DIR ) . 'rapo-multiauthors/rapo-multiauthors-' . $locale . '.mo' );
		load_plugin_textdomain( 'rapo-multiauthors', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}
	
	/**
	 * Includes.
	 * @return void
	 */
	private function includes() {
		include_once 'includes/class-rma-contributor-box.php';
	}
	
	/**
	 * Admin includes.
	 * @return void
	 */
	private function admin_includes() {
		include_once 'includes/admin/class-rma-admin.php';
	}
	/**
	 * Called when the plugin is activated.
	 * @return void
	 */
	public static function rma_activate() {
		$options = array( 
			'display'          => 'posts',
			'gravatar'         => 70,
			'background_color' => '#777777',
			'text_color'       => '#1a2930',
			'title_color'      => '#0a1612',
			'border_size'      => 2,
			'border_style'     => 'solid',
			'border_color'     => '#f7ce3e',
		 );
	
		update_option( 'rma_settings', $options );
	}
	
	/**
	 * Called when the plugin is deactivated.
	 * @return void
	 */
	public static function rma_deactivate() {
		delete_option( 'rma_settings' );
	}
	
	/*
	 * Meta box setup function.
	 * */
	public function rma_post_meta_boxes_setup() {
	  /* Add meta boxes through the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( $this,'rma_add_post_meta_boxes' ) );
	
	  /* Save post meta on the 'save_post' hook. */
		add_action( 'save_post', array( $this,'rma_save_contributors_meta' ), 10, 2 );
	}
	
	/* Create the contributors meta box to be displayed on the post editor screen. */
	public function rma_add_post_meta_boxes() {
		add_meta_box( 'rma-contributors', // Unique ID
	    	esc_html__( 'Contributors', 'rapo-multiauthors' ), // Title
	    	array( $this,'rma_contributors_meta_box' ), // Callback function
	    	'post', // Admin page ( or post type )
	    	'side', // Context
	    	'default' // Priority
		);
	}
	
	/* Display the post meta box. */
	public function rma_contributors_meta_box( $post ) {
		wp_nonce_field( basename( __FILE__ ), 'rma_contributors_nonce' );
		$postmeta = maybe_unserialize( get_post_meta( $post->ID, 'rma-contributors', true ) );
		
		/* Populate potential contributors from the users list */
		$blogusers = get_users( 'blog_id=1&orderby=nicename' );
		if ( empty( $blogusers ) ) {
			return;
		}
		echo __( "Add one or more contributors for this post", 'rapo-multiauthors' ); 
		?>
		<p>
		<div id="rma-contributors-all" >
			<ul id="contributorchecklist" data-wp-lists="list:meta" >
			<?php
		  	foreach ( $blogusers as $bloguser ) {
				$usr_id = $bloguser->user_login;
				$checked = is_array( $postmeta ) && in_array( $usr_id, $postmeta ) ? ' checked="checked"' : '';
				echo '<li id="rma-contributor-', $usr_id, '"><label for="in-rma-contributor-', $usr_id, '" class="selectit"><input value="', $usr_id, '" type="checkbox" name="rma_contributor[]" id="in-rma-contributor-', $usr_id, '"', $checked, '/> ', $usr_id, "</label></li>";
		  	} 
			?>
		  	</ul>
		</div>
		</p>
		<?php
	}
	
	/* Save the meta box's post metadata. */
	public function rma_save_contributors_meta( $post_id, $post ) {
		$is_autosave  = wp_is_post_autosave( $post_id );
		$is_revision  = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST['rma_contributors_nonce'] ) && wp_verify_nonce( $_POST['rma_contributors_nonce'], basename( __FILE__ ) ) ) ? 'true' : 'false';
		
		if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
			return;
		}
		
		// If some checkbox is checked, save it as an array in post meta
		if ( !empty( $_POST['rma_contributor'] ) ) {
			update_post_meta( $post_id, 'rma-contributors', $_POST['rma_contributor'] );
		}
		else { // Otherwise just delete it if it is blank value.
			delete_post_meta( $post_id, 'rma-contributors' );
		}
	}
}

/**
 * Perform installation.
 */
register_activation_hook( __FILE__, array( 'Rapo_MultiAuthors', 'rma_activate' ) );
register_deactivation_hook( __FILE__, array( 'Rapo_MultiAuthors', 'rma_deactivate' ) );

add_action( 'plugins_loaded', array( 'Rapo_MultiAuthors', 'get_instance' ), 0 );

}
