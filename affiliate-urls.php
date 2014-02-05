<?php
/*
Plugin Name: Affiliate URLs
Plugin URI: http://www.skyrockinc.com/aff-urls/
Description: 
Version: 0.0.1
Author: hypedtext
Author URI: http://www.skyrockinc.com/
*/
class AffURLs {

	// Constructor
	function __construct() {
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'columns_data' ) );
		add_filter( 'manage_edit-affurls_columns', array( &$this, 'columns_filter' ) );
		add_action( 'admin_menu', array( &$this, 'add_meta_box' ) );
		add_action( 'save_post', array( &$this, 'meta_box_save' ), 1, 2 );
		add_action( 'template_redirect', array( &$this, 'count_and_redirect' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'add_js' ) );
		add_action( 'wp_ajax_cookie_logic', array( &$this, 'cookie_logic' ) );
		add_action( 'wp_ajax_nopriv_cookie_logic', array( &$this, 'cookie_logic' ) ); 

		// New stuff
        add_action( 'admin_menu', array(&$this, 'admin_menu') );
        require_once( plugin_dir_path( __FILE__) . 'php/wp-settings-framework.php' );
        $this-> affurlssettings = new WordPressSettingsFramework( plugin_dir_path( __FILE__ ) . 'php/settings.php' );
        // Add an optional settings validation filter (recommended)
        add_filter( $this->affurlssettings->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );
	}
	
	// PHP4 Constructor
	function AffURLs () {
		$this->__construct();
	}
	
	function register_post_type() {
		
		register_post_type( 'affurls',
			array(
				'labels' => array(
					'name' => __( 'Affiliate URLs' ),
					'singular_name' => __( 'Affiliate URL' ),
					'add_new' => __( 'Add New' ),
					'add_new_item' => __( 'Add New Affiliate URL' ),
					'edit' => __( 'Edit' ),
					'edit_item' => __( 'Edit Affiliate URL' ),
					'new_item' => __( 'New Affiliate URL' ),
					'view' => __( 'View Affiliate URL' ),
					'view_item' => __( 'View Affiliate URL' ),
					'search_items' => __( 'Search Affiliate URL' ),
					'not_found' => __( 'No Affiliate URLs found' ),
					'not_found_in_trash' => __( 'No URLs found in Trash' )
				),
				'public' => true,
				'query_var' => true,
				'menu_position' => 20,
				'menu_icon' => 'dashicons-star-filled',
				'supports' => array( 'title' ),
				'rewrite' => array( 'slug' => 'go', 'with_front' => false )
			)
		);
		
	}

    function admin_menu() {
        add_submenu_page( 'edit.php?post_type=affurls', __( 'Settings', 'wp-settings-framework' ), __( 'Settings', 'wp-settings-framework' ), 'manage_options', 'affurlsssettings', array(&$this, 'settings_page') );
    }

    function settings_page() {
	    ?>
		<div class="wrap">
			<h2>Settings</h2>
			<p>Use this page to override the redirect you have set on your affiliate URLs. This is useful if you're running paid traffic and tracking with software like iMobiTrax. Here you can spefify a new redirect domain to handle your offer landing pages.</p>
			<?php 
			// Output your settings form
			$this->affurlssettings->settings(); 
			?>
		</div>
		<?php
	}
	
	function validate_settings( $input ) {
    	return $input;
	}
	
	function columns_filter( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title'),
			'url' => __('Redirect to'),
			'permalink' => __('Permalink'),
			'clicks' => __('Clicks')
		);
		return $columns;
	}
	
	function columns_data( $column ) {
		global $post;
		
		$url = get_post_meta($post->ID, '_affurls_redirect', true);
		$count = get_post_meta($post->ID, '_affurls_count', true);
		
		if ( $column == 'url' ) {
			echo make_clickable( esc_url( $url ? $url : '' ) );
		}
		elseif ( $column == 'permalink' ) {
			echo make_clickable( get_permalink() );
		}
		elseif ( $column == 'clicks' ) {
			echo esc_html( $count ? $count : 0 );
		}
	}
	
	function add_meta_box() {
		add_meta_box('affurls', __('URL Information', 'affurls'), array( &$this, 'meta_box' ), 'affurls', 'normal', 'high');
	}
	
	function meta_box() {
		global $post;
		
		printf( '<input type="hidden" name="_affurls_nonce" value="%s" />', wp_create_nonce( plugin_basename(__FILE__) ) );
		
		printf( '<p><label for="%s">%s</label></p>', '_affurls_redirect', __('Redirect URI', 'affurls') );
		printf( '<p><input style="%s" type="text" name="%s" id="%s" value="%s" /></p>', 'width: 99%;', '_affurls_redirect', '_affurls_redirect', esc_attr( get_post_meta( $post->ID, '_affurls_redirect', true ) ) );
		
		$count = isset( $post->ID ) ? get_post_meta($post->ID, '_affurls_count', true) : 0;
		printf( '<p>This URL has been accessed <b>%d</b> times.', esc_attr( $count ) );
		
	}
	
	function meta_box_save( $post_id, $post ) {
		$key = '_affurls_redirect';
		
		//	verify the nonce
		if ( !isset($_POST['_affurls_nonce']) || !wp_verify_nonce( $_POST['_affurls_nonce'], plugin_basename(__FILE__) ) )
			return;
			
		//	don't try to save the data under autosave, ajax, or future post.
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
		if ( defined('DOING_AJAX') && DOING_AJAX ) return;
		if ( defined('DOING_CRON') && DOING_CRON ) return;

		//	is the user allowed to edit the URL?
		if ( ! current_user_can( 'edit_posts' ) || $post->post_type != 'affurls' )
			return;
			
		$value = isset( $_POST[$key] ) ? $_POST[$key] : '';
		
		if ( $value ) {
			//	save/update
			update_post_meta($post->ID, $key, $value);
		} else {
			//	delete if blank
			delete_post_meta($post->ID, $key);
		}
	}

	
	function count_and_redirect() {
		if ( !is_singular('affurls') )
			return;

		global $wp_query;
		
		// Update the count
		$count = isset( $wp_query->post->ID ) ? get_post_meta($wp_query->post->ID, '_affurls_count', true) : 0;
		update_post_meta( $wp_query->post->ID, '_affurls_count', $count + 1 );

		// Handle the redirect
		$redirect = isset( $wp_query->post->ID ) ? get_post_meta($wp_query->post->ID, '_affurls_redirect', true) : '';

		if ( !empty( $redirect ) ) {
			if ( isset( $_COOKIE[ 'ppc' ] ) && $_COOKIE[ 'ppc' ] == 'true' ) {
				//echo $_COOKIE[ 'ppc' ];
				wp_redirect( esc_url_raw( wpsf_get_setting( wpsf_get_option_group( plugin_dir_path( __FILE__ ) . 'php/settings.php' ), 'ppc', 'offerlink' ) ), 301 );
				exit;
			} else {
				//echo $_COOKIE[ 'ppc' ];
				wp_redirect( esc_url_raw( $redirect ), 301);
				exit;
			}
		} else {
			wp_redirect( home_url(), 302 );
			exit;
		}
	}

	function add_js() {
		if( !wp_script_is( 'cookiejs', 'queue' ) ) {
			wp_enqueue_script( 'cookiejs', plugins_url( basename( __DIR__ ) . '/js/cookie.min.js' ), array( 'jquery' ) );
		}
		wp_enqueue_script( 'check', plugins_url( basename( __DIR__ ) . '/js/check.js' ), array( 'jquery' ) );
		wp_localize_script( 'check', 'check', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		$param = wpsf_get_setting( wpsf_get_option_group( plugin_dir_path( __FILE__ ) . 'php/settings.php' ), 'ppc', 'param' );
		wp_localize_script( 'check', 'get', $param );
	}

	function cookie_logic() {
		print_r( $_COOKIE );
	}

}

$AffURLs = new AffURLs;
