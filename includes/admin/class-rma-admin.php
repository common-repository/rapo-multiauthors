<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Rapo MultiAuthors Admin class.
 */
class Rma_Admin {

	/**
	 * Slug of the rma screen.
	 * @var string
	 */
	protected $rma_screen_hook_suffix = null;

	/**
	 * Initialize the rma admin.
	 */
	public function __construct() {

		// Custom contact methods.
		add_filter( 'user_contactmethods', array( $this, 'contact_methods' ), 10, 1 );

		// Load admin JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_rma_admin_menu' ) );

		// Init rma options form.
		add_action( 'admin_init', array( $this, 'rma_settings' ) );

        /* Add meta boxes on the 'add_meta_boxes' hook. */
        add_action('add_meta_boxes', array($this, 'rma_add_post_meta_boxes'));
	}

    /* Create the contributors meta box to be displayed on the post editor screen. */
    public function rma_add_post_meta_boxes() {
        add_meta_box('rma-contributors', // Unique ID
            esc_html__('Contributors', 'rapo-multiauthors'), // Title
            array($this,'rma_contributors_meta_box'), // Callback function
            'post', // Admin page (or post type)
            'side', // Context
            'default' // Priority
        );
    }

    /* Display the post meta box. */
    public function rma_contributors_meta_box($post) {
        wp_nonce_field(basename(__FILE__), 'rma_contributors_nonce');
        $postmeta = maybe_unserialize(get_post_meta($post->ID, 'rma-contributors', true));

        $blogusers = get_users('blog_id=1&orderby=nicename');
        if ( empty($blogusers) ) {
            return;
		}
        echo __("Add one or more contributors for this post", 'rapo-multiauthors'); ?>
        <p> <br />
        <div id="rma-contributors-all" >
            <ul id="contributorchecklist" data-wp-lists="list:meta" >
            <?php
            foreach ($blogusers as $bloguser) {
                $usr_id  = $bloguser->user_login;
                $checked = is_array($postmeta) && in_array($usr_id, $postmeta) ? ' checked="checked"' : '';
         		echo '<li id="rma-contributor-', $usr_id, '"><label for="in-rma-contributor-', $usr_id, '" class="selectit"><input value="', $usr_id, '" type="checkbox" name="rma_contributor[]" id="in-contributor-', $usr_id, '"', $checked, '/> ', $usr_id, "</label></li>";
            } 
			?>
            </ul>
        </div>
        </p>
    <?php
    }

	/**
	 * Sets default settings.
	 *
	 * @return array rma default settings.
	 */
	protected function default_settings() {

		$settings = array(
			'settings' => array(
				'title' => __( 'Settings', 'rapo-multiauthors' ),
				'type'  => 'section',
				'menu'  => 'rma_settings',
			),
			'display' => array(
				'title'       => __( 'Display in', 'rapo-multiauthors' ),
				'default'     => 'posts',
				'type'        => 'select',
				'section'     => 'settings',
				'menu'        => 'rma_settings',
				'options'     => array(
					'posts'      => __( 'Only in Posts', 'rapo-multiauthors' ),
					'home_posts' => __( 'Homepage and Posts', 'rapo-multiauthors' ),
					'none'       => __( 'None', 'rapo-multiauthors' ),
				),
			),
			'design' => array(
				'title' => __( 'Colors and Design', 'rapo-multiauthors' ),
				'type'  => 'section',
				'menu'  => 'rma_settings',
			),
			'gravatar' => array(
				'title'       => __( 'Gravatar size', 'rapo-multiauthors' ),
				'default'     => 70,
				'type'        => 'text',
				'description' => sprintf( __( 'The %s size in pixels. .', 'rapo-multiauthors' ), '<a target="_blank" href="http://gravatar.com">Gravatar</a>' ),
				'section'     => 'design',
				'menu'        => 'rma_settings',
			),
			'background_color' => array(
				'title'   => __( 'Background color', 'rapo-multiauthors' ),
				'default' => '#777777',
				'type'    => 'color',
				'section' => 'design',
				'menu'    => 'rma_settings',
			),
			'text_color' => array(
				'title'   => __( 'Text color', 'rapo-multiauthors' ),
				'default' => '#1a2930',
				'type'    => 'color',
				'section' => 'design',
				'menu'    => 'rma_settings',
			),
			'title_color' => array(
				'title'   => __( 'Title color', 'rapo-multiauthors' ),
				'default' => '#0a1612',
				'type'    => 'color',
				'section' => 'design',
				'menu'    => 'rma_settings',
			),
			'border_size' => array(
				'title'       => __( 'Border size', 'rapo-multiauthors' ),
				'default'     => 2,
				'type'        => 'text',
				'section'     => 'design',
				'description' => __( 'Thickness of the border of the box (only integers).', 'rapo-multiauthors' ),
				'menu'        => 'rma_settings',
			),
			'border_style' => array(
				'title'   => __( 'Border style', 'rapo-multiauthors' ),
				'default' => 'solid',
				'type'    => 'select',
				'section' => 'design',
				'menu'    => 'rma_settings',
				'options' => array(
					'none'   => __( 'None', 'rapo-multiauthors' ),
					'solid'  => __( 'Solid', 'rapo-multiauthors' ),
					'dotted' => __( 'Dotted', 'rapo-multiauthors' ),
					'dashed' => __( 'Dashed', 'rapo-multiauthors' ),
				)
			),
			'border_color' => array(
				'title'   => __( 'Border color', 'rapo-multiauthors' ),
				'default' => '#f7ce3e',
				'type'    => 'color',
				'section' => 'design',
				'menu'    => 'rma_settings',
			),
		);

		return $settings;
	}

	/**
	 * Custom contact methods.
	 *
	 * @param  array $methods Old contact methods.
	 *
	 * @return array          New contact methods.
	 */
	public function contact_methods( $methods ) {
		// Add new methods.
		$methods['facebook']   = __( 'Facebook', 'rapo-multiauthors' );
		$methods['twitter']    = __( 'Twitter', 'rapo-multiauthors' );
		$methods['googleplus'] = __( 'Google Plus', 'rapo-multiauthors' );
		$methods['linkedin']   = __( 'LinkedIn', 'rapo-multiauthors' );
		$methods['flickr']     = __( 'Flickr', 'rapo-multiauthors' );
		$methods['tumblr']     = __( 'Tumblr', 'rapo-multiauthors' );
		$methods['vimeo']      = __( 'Vimeo', 'rapo-multiauthors' );
		$methods['youtube']    = __( 'YouTube', 'rapo-multiauthors' );
		$methods['instagram']  = __( 'Instagram', 'rapo-multiauthors' );
		$methods['pinterest']  = __( 'Pinterest', 'rapo-multiauthors' );

		return $methods;
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @return null Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->rma_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( $this->rma_screen_hook_suffix == $screen->id ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_script( 'rapo-multiauthors-admin', Rapo_MultiAuthors::get_assets_url() . 'js/admin' . $suffix . '.js', array( 'jquery', 'wp-color-picker' ), Rapo_MultiAuthors::RMA_VERSION, true );
		}
	}

	/**
	 * Register the administration menu.
	 *
	 * @return void
	 */
	public function add_rma_admin_menu() {
		$this->rma_screen_hook_suffix = add_options_page(
			__( 'Rapo MultiAuthors', 'rapo-multiauthors' ),
			__( 'Rapo MultiAuthors', 'rapo-multiauthors' ),
			'manage_options',
			'rapo-multiauthors',
			array( $this, 'display_rma_admin_page' )
		);
	}

	/**
	 * rma settings form fields.
	 *
	 * @return void
	 */
	public function rma_settings() {
		$settings = 'rma_settings';

		foreach ( $this->default_settings() as $key => $value ) {

			switch ( $value['type'] ) {
				case 'section':
					add_settings_section(
						$key,
						$value['title'],
						'__return_false',
						$value['menu']
					);
					break;
				case 'text':
					add_settings_field(
						$key,
						$value['title'],
						array( $this, 'text_element_callback' ),
						$value['menu'],
						$value['section'],
						array(
							'menu'        => $value['menu'],
							'id'          => $key,
							'class'       => 'small-text',
							'description' => isset( $value['description'] ) ? $value['description'] : '',
						)
					);
					break;
				case 'select':
					add_settings_field(
						$key,
						$value['title'],
						array( $this, 'select_element_callback' ),
						$value['menu'],
						$value['section'],
						array(
							'menu'        => $value['menu'],
							'id'          => $key,
							'description' => isset( $value['description'] ) ? $value['description'] : '',
							'options'     => $value['options'],
						)
					);
					break;
				case 'color':
					add_settings_field(
						$key,
						$value['title'],
						array( $this, 'color_element_callback' ),
						$value['menu'],
						$value['section'],
						array(
							'menu'        => $value['menu'],
							'id'          => $key,
							'description' => isset( $value['description'] ) ? $value['description'] : '',
						)
					);
					break;

				default:
					break;
			}

		}

		// Register settings.
		register_setting( $settings, $settings, array( $this, 'validate_options' ) );
	}

	/**
	 * Text element callback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string      Text field.
	 */
	public function text_element_callback( $args ) {
		$menu  = $args['menu'];
		$id    = $args['id'];
		$class = isset( $args['class'] ) ? $args['class'] : 'small-text';

		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="%4$s" />', $id, $menu, $current, $class );

		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}

		echo $html;
	}

	/**
	 * Select field fallback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string      Select field.
	 */
	public function select_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];

		$options = get_option( $menu );

		// Sets current option.
		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$html = sprintf( '<select id="%1$s" name="%2$s[%1$s]">', $id, $menu );
		foreach( $args['options'] as $key => $label ) {
			$key = sanitize_title( $key );

			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
		}
		$html .= '</select>';

		// Displays the description.
		if ( $args['description'] ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}

		echo $html;
	}

	/**
	 * Color element fallback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string      Color field.
	 */
	public function color_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];

		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '#333333';
		}

		$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="rapo-multiauthors-color-field" />', $id, $menu, $current );

		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}

		echo $html;
	}

	/**
	 * Valid options.
	 *
	 * @param  array $input options to valid.
	 *
	 * @return array        validated options.
	 */
	public function validate_options( $input ) {
		// Create our array for storing the validated options.
		$output = array();

		// Loop through each of the incoming options.
		foreach ( $input as $key => $value ) {

			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[ $key ] ) ) {

				// Strip all HTML and PHP tags and properly handle quoted strings.
				$output[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		return $output;
	}

	/**
	 * Render the settings page for rma.
	 */
	public function display_rma_admin_page() {
		include_once 'views/html-rma-settings.php';
	}

}

new Rma_Admin();
