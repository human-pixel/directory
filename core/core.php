<?php

/**
 * Directory_Core 
 * 
 * @package Directory
 * @copyright Incsub 2007-2011 {@link http://incsub.com}
 * @author Ivan Shaovchev (Incsub) {@link http://ivan.sh} 
 * @license GNU General Public License (Version 2 - GPLv2) {@link http://www.gnu.org/licenses/gpl-2.0.html}
 */
class Directory_Core {

    /** @var string $plugin_url Plugin URL */
    var $plugin_url = DP_PLUGIN_URL;
    /** @var string $plugin_dir Path to plugin directory */
    var $plugin_dir = DP_PLUGIN_DIR;
    /** @var string $text_domain The text domain for strings localization */
    var $text_domain = DP_TEXTDOMAIN;
    /** @var string Name of options DB entry */
    var $options_name = DP_OPTIONS_NAME;
    /** @var string User role */
    var $user_role = 'dp_member';
    /** @var string Main plugin menu slug */
    var $admin_menu_slug = 'dp_main';

    /**
     * Constructor.
     */
    function Directory_Core() {

		// TODO: Flush rewrite rules on plugin activation

        add_action( 'init', array( &$this, 'init' ), 5 );
        add_action( 'plugins_loaded', array( &$this, 'init_modules' ) );
        add_action( 'init', array( &$this, 'load_plugin_textdomain' ), 0 );
        add_action( 'init', array( &$this, 'handle_action_buttons_requests' ) );
        add_action( 'init', array( &$this, 'roles' ) );
        add_action( 'wp_loaded', array( &$this, 'scheduly_expiration_check' ) );
        add_action( 'custom_banner_header', array( &$this, 'output_banners' ) );
        add_action( 'check_expiration_dates', array( &$this, 'check_expiration_dates_callback' ) );
        add_filter( 'sort_custom_taxonomies', array( &$this, 'sort_custom_taxonomies' ) );
        register_activation_hook( $this->plugin_dir . 'loader.php', array( &$this, 'plugin_activate' ) );
        register_deactivation_hook( $this->plugin_dir . 'loader.php', array( &$this, 'plugin_deactivate' ) );
        register_theme_directory( $this->plugin_dir . 'themes' );
        $plugin = plugin_basename(__FILE__);
        add_filter( "plugin_action_links_$plugin", array( &$this, 'plugin_settings_link' ) );

		add_filter( 'single_template', array( &$this, 'handle_template' ) );
    }

    /**
     * Intiate plugin.
     *
     * @return void
     */
    function init() {
		register_taxonomy( 'listing_tag', 'listing', array(
			'rewrite' => array( 'slug' => 'listings/tag', 'with_front' => false ),
			'labels' => array(
				'name'			=> __( 'Listing Tags', $this->text_domain ),
				'singular_name'	=> __( 'Listing Tag', $this->text_domain ),
				'search_items'	=> __( 'Search Listing Tags', $this->text_domain ),
				'popular_items'	=> __( 'Popular Listing Tags', $this->text_domain ),
				'all_items'		=> __( 'All Listing Tags', $this->text_domain ),
				'edit_item'		=> __( 'Edit Listing Tag', $this->text_domain ),
				'update_item'	=> __( 'Update Listing Tag', $this->text_domain ),
				'add_new_item'	=> __( 'Add New Listing Tag', $this->text_domain ),
				'new_item_name'	=> __( 'New Listing Tag Name', $this->text_domain ),
				'separate_items_with_commas'	=> __( 'Separate listing tags with commas', $this->text_domain ),
				'add_or_remove_items'			=> __( 'Add or remove listing tags', $this->text_domain ),
				'choose_from_most_used'			=> __( 'Choose from the most used listing tags', $this->text_domain ),
			)
		) );

		register_taxonomy( 'listing_category', 'listing', array(
			'rewrite' => array( 'slug' => 'listings/category', 'with_front' => false ),
			'hierarchical' => true,
			'labels' => array(
				'name'			=> __( 'Listing Categories', $this->text_domain ),
				'singular_name'	=> __( 'Listing Category', $this->text_domain ),
				'search_items'	=> __( 'Search Listing Categories', $this->text_domain ),
				'popular_items'	=> __( 'Popular Listing Categories', $this->text_domain ),
				'all_items'		=> __( 'All Listing Categories', $this->text_domain ),
				'parent_item'	=> __( 'Parent Category', $this->text_domain ),
				'edit_item'		=> __( 'Edit Listing Category', $this->text_domain ),
				'update_item'	=> __( 'Update Listing Category', $this->text_domain ),
				'add_new_item'	=> __( 'Add New Listing Category', $this->text_domain ),
				'new_item_name'	=> __( 'New Listing Category', $this->text_domain ),
				'parent_item_colon'		=> __( 'Parent Category:', $this->text_domain ),
				'add_or_remove_items'	=> __( 'Add or remove listing categories', $this->text_domain ),
			)
		) );

		register_post_type( 'listing', array(
			'public' => true,
			'rewrite' => array( 'slug' => 'listings', 'with_front' => false ),
			'has_archive' => true,

			'capability_type' => 'listing',
			'capabilities' => array( 'read' => 'read_listings' ),
			'map_meta_cap' => true,

			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'revisions' ),

			'labels' => array(
				'name'			=> __('Listings', $this->text_domain ),
				'singular_name'	=> __('Listing', $this->text_domain ),
				'add_new'		=> __('Add New', $this->text_domain ),
				'add_new_item'	=> __('Add New Listing', $this->text_domain ),
				'edit_item'		=> __('Edit Listing', $this->text_domain ),
				'new_item'		=> __('New Listing', $this->text_domain ),
				'view_item'		=> __('View Listing', $this->text_domain ),
				'search_items'	=> __('Search Listings', $this->text_domain ),
				'not_found'		=> __('No listings found', $this->text_domain ),
				'not_found_in_trash'	=> __('No listings found in trash', $this->text_domain ),
			)
		) );
    }

    /**
     * Initiate plugin modules.
     *
     * @return void
      **/
    function init_modules() {
        /* Initiate Data Imports */
        new Directory_Core_Data();
        /* Initiate Content Types Module */
        // new Content_Types_Core( $this->admin_menu_slug );
        /* Initiate Payments Module */
        new Payments_Core( 'settings', $this->user_role );
        /* Initiate Ratings Module */
        new Ratings_Core();
    }

    /**
     * Loads "directory-[xx_XX].mo" language file from the "languages" directory
     * @todo To do something! 
     * @return void
     **/
    function load_plugin_textdomain() {
        $plugin_dir = $this->plugin_dir . 'languages';
        load_plugin_textdomain( 'directory', null, $plugin_dir );
    }

    /**
     * Update plugin versions
     *
     * @return void
     **/
    function plugin_activate() {
		$this->init();
		flush_rewrite_rules();
    }

    /**
     * Deactivate plugin. If $this->flush_plugin_data is set to "true"
     * all plugin data will be deleted
     *
     * @return void
     */
    function plugin_deactivate() {
        /* if true all plugin data will be deleted */
        if ( false ) {
            delete_option( $this->options_name );
            delete_option( 'ct_custom_post_types' );
            delete_option( 'ct_custom_taxonomies' );
            delete_option( 'ct_custom_fields' );
            delete_option( 'ct_flush_rewrite_rules' );
            delete_option( 'module_payments' );
            delete_site_option( $this->options_name );
            delete_site_option( 'ct_custom_post_types' );
            delete_site_option( 'ct_custom_taxonomies' );
            delete_site_option( 'ct_custom_fields' );
            delete_site_option( 'ct_flush_rewrite_rules' );
            delete_site_option( 'allow_per_site_content_types' );
        }
    }

    /**
     *
     * @param <type> $links
     * @return <type>
     */
    function plugin_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=dp_main&dp_settings=main&dp_gen">Settings</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }


	/**
	 * Loads default templates if the current theme doesn't have them.
	 */
	function handle_template( $path ) {
		global $wp_query;

		// $this->_load_default_style();

		// if ( is_qa_page( 'archive' ) && is_search() )
			// $this->load_template( 'archive-question.php' );

		$type = reset( explode( '_', current_filter() ) );

		$file = basename( $path );

		if ( 'listing' == get_query_var( 'post_type' ) && "$type.php" == $file ) {
			// A more specific template was not found, so load the default one
			$path = $this->plugin_dir . "templates/$type-listing.php";
		}

		return $path;
	}

	/**
	 * Load a template, with fallback to default-templates.
	 */
	function load_template( $name ) {
		$path = locate_template( $name );

		if ( !$path ) {
			$path = $this->plugin_dir . "templates/$name";
		}

		load_template( $path );
		die;
	}

	/**
	 * Enqueue default CSS.
	 */
	function _load_default_style() {
		if ( is_qa_page() && !current_theme_supports( 'qa_section' ) ) {
			wp_enqueue_style( 'qa-section', $this->plugin_dir . "templates/css/general.css" );
		}
	}

    /**
	 * TODO: Remove roles entirely, use capabilities instead
	 *
     * Add custom role for members. Add new capabilities for admin.
     *
     * @global $wp_roles
     * @return void
     */
    function roles() {
        global $wp_roles;

        if ( $wp_roles ) {
            $wp_roles->add_role( $this->user_role, 'Directory Member', array(
                'publish_listings'       => true,
                'edit_listings'          => true,
                'edit_others_listings'   => false,
                'delete_listings'        => false,
                'delete_others_listings' => false,
                'read_private_listings'  => false,
                'edit_listing'           => true,
                'delete_listing'         => true,
                'read_listing'           => true,
                'upload_files'           => true,
                'assign_terms'           => true,
                'read'                   => true
            ) );

            /* Set administrator roles */
            $wp_roles->add_cap( 'administrator', 'publish_listings' );
            $wp_roles->add_cap( 'administrator', 'edit_listings' );
            $wp_roles->add_cap( 'administrator', 'edit_others_listings' );
            $wp_roles->add_cap( 'administrator', 'delete_listings' );
            $wp_roles->add_cap( 'administrator', 'delete_others_listings' );
            $wp_roles->add_cap( 'administrator', 'read_private_listings' );
            $wp_roles->add_cap( 'administrator', 'edit_listing' );
            $wp_roles->add_cap( 'administrator', 'delete_listing' );
            $wp_roles->add_cap( 'administrator', 'read_listing' );
        }
    }

    /**
     * Sets sort type for taxonomies 
     * 
     * @param string $sort 
     * @access public
     * @return string Sort type
     */
    function sort_custom_taxonomies( $sort ) {
        $options = $this->get_options('general_settings');

		if ( isset( $options['order_taxonomies'] ) )
			$sort = $options['order_taxonomies'];

        return $sort; 
    }

    /**
     *  
     */
    function output_banners() { 
        $options = $this->get_options( 'ads_settings' );
        if ( !empty( $options['header_ad_code'] ) ) {
            echo stripslashes( $options['header_ad_code'] );
        } else {
            echo '<span>' .  __( 'Advartise Here', $this->text_domain ) . '</span>';
        }
    }    

    /**
     * handle_action_buttons_requests 
     * 
     * @access public
     * @return void
     */
    function handle_action_buttons_requests(  ) {
        /* If your want to go to admin profile */
        if ( isset( $_POST['redirect_profile'] ) ) {
            wp_redirect( admin_url() . 'profile.php' );
            exit();
        }
        elseif ( isset( $_POST['redirect_listing'] ) ) {
            wp_redirect( admin_url() . 'post-new.php?post_type=directory_listing' );
            exit();
        }
    }

    /**
     * Schedule expiration check for twice daily.
     *
     * @return void
     **/
    function scheduly_expiration_check() {
        if ( !wp_next_scheduled( 'check_expiration_dates' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'check_expiration_dates' );
        }
    }

    /**
     * Check each post from the used post type and compare the expiration date/time
     * with the current date/time. If the post is expired update it's status.
     *
     * @return void
     **/
    function check_expiration_dates_callback() {}

    /**
     * Save plugin options.
     *
     * @param  array $params The $_POST array
     * @return die() if _wpnonce is not verified
     **/
    function save_options( $params ) {
        if ( wp_verify_nonce( $params['_wpnonce'], 'verify' ) ) {
            /* Remove unwanted parameters */
            unset( $params['_wpnonce'], $params['_wp_http_referer'], $params['save'] );
            /* Update options by merging the old ones */
            $options = $this->get_options();
            $options = array_merge( $options, array( $params['key'] => $params ) );
            update_option( $this->options_name, $options );
        } else {
            die( __( 'Security check failed!', $this->text_domain ) );
        }
    }

    /**
     * Get plugin options.
     *
     * @param  string|NULL $key The key for that plugin option.
     * @return array $options Plugin options or empty array if no options are found
     **/
    function get_options( $key = null ) {
        $options = get_option( $this->options_name );
        $options = is_array( $options ) ? $options : array();
        /* Check if specific plugin option is requested and return it */
        if ( isset( $key ) && array_key_exists( $key, $options ) )
            return $options[$key];
        else
            return $options;
    }

    /**
	 * Renders an admin section of display code.
	 *
	 * @param  string $name Name of the admin file(without extension)
	 * @param  string $vars Array of variable name=>value that is available to the display code(optional)
	 * @return void
	 **/
    function render_admin( $name, $vars = array() ) {
		foreach ( $vars as $key => $val )
			$$key = $val;
		if ( file_exists( "{$this->plugin_dir}ui-admin/{$name}.php" ) )
			include "{$this->plugin_dir}ui-admin/{$name}.php";
		else
			echo "<p>Rendering of admin template {$this->plugin_dir}ui-admin/{$name}.php failed</p>";
	}

}

/* Initiate Class */
if ( class_exists('Directory_Core') )
	$__directory_core = new Directory_Core();

/* Update Notifications Notice */
// if ( !function_exists( 'wdp_un_check' ) ):
// function wdp_un_check() {
    // if ( !class_exists('WPMUDEV_Update_Notifications') && current_user_can('edit_users') )
        // echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
// }
// add_action( 'admin_notices', 'wdp_un_check', 5 );
// add_action( 'network_admin_notices', 'wdp_un_check', 5 );
// endif;

?>
