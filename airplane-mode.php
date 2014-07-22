<?php
/*
Plugin Name: Airplane Mode
Plugin URI: http://reaktivstudios.com/
Description: Control loading of external files when developing locally
Author: Andrew Norcross
Version: 0.0.1
Requires at least: 3.7
Author URI: http://reaktivstudios.com/

Copyright 2014 Andrew Norcross

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License (GPL v2) only.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( ! defined( 'AIRMDE_BASE ' ) ) {
	define( 'AIRMDE_BASE', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'AIRMDE_DIR' ) ) {
	define( 'AIRMDE_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'AIRMDE_VER' ) ) {
	define( 'AIRMDE_VER', '0.0.1' );
}


class Airplane_Mode_Core {
	/**
	 * Static property to hold our singleton instance
	 * @var $instance
	 */
	static $instance = false;

	/**
	 * this is our constructor.
	 * there are many like it, but this one is mine
	 */
	private function __construct() {
		add_action( 'plugins_loaded',     array( $this, 'textdomain'        )        );
		add_action( 'wp_default_styles',  array( $this, 'block_style_load'  ), 100   );
		add_action( 'wp_default_scripts', array( $this, 'block_script_load' ), 100   );
		add_filter( 'get_avatar',         array( $this, 'replace_gravatar'  ), 1,  5 );

		// kill all the http requests
		add_filter( 'pre_http_request', array( $this, 'disable_http_reqs' ), 10, 3 );

		// check for our query string and handle accordingly
		add_action( 'init', array( $this, 'toggle_check' ) );

		// settings
		add_action( 'admin_bar_menu',        array( $this, 'admin_bar_toggle' ), 9999 );
		add_action( 'wp_enqueue_scripts',    array( $this, 'toggle_css'       ), 9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'toggle_css'       ), 9999 );
		add_action( 'login_enqueue_scripts', array( $this, 'toggle_css'       ), 9999 );

		// keep jetpack from attempting external requests
		if ( $this->enabled() ) {
			add_filter( 'jetpack_development_mode', '__return_true', 9999 );
		}

		register_activation_hook( __FILE__,   array( $this, 'create_setting' ) );
		register_deactivation_hook( __FILE__, array( $this, 'remove_setting' ) );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * load our textdomain for localization
	 *
	 * @return void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'airplane-mode', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	 /**
	  * set our initial airplane mode setting to 'on'
	  *
	  * @return void
	  */
	public function create_setting() {
		add_option( 'airplane-mode', 'on' );
	}

	/**
	 * remove our setting on plugin deactivation
	 *
	 * @return void
	 */
	public function remove_setting() {
		delete_option( 'airplane-mode' );
	}

	/**
	 * helper function to check the current status
	 *
	 * @return bool
	 */
	public function enabled() {
		// pull our status from the options table
		return 'on' === get_option( 'airplane-mode' );
	}

	/**
	 * hop into the set of default CSS files to allow for
	 * disabling Open Sans and filter to allow other mods
	 *
	 * @param  object $styles all the registered CSS items
	 * @return object $styles the same object with Open Sans src set to null
	 */
	public function block_style_load( $styles ) {
		// bail if disabled
		if ( ! $this->enabled() ) {
			return $styles;
		}

		// make sure we have something registered first
		if ( ! isset( $styles->registered ) ) {
			return $styles;
		}

		// fetch our registered styles
		$registered = $styles->registered;

		// pass the entire set of registered data to the action to allow a bypass
		do_action( 'airplane_mode_style_load', $registered );

		// fetch our open sans if present and set the src inside the object to null
		if ( ! empty( $registered['open-sans'] ) ) {
			$open_sans = $registered['open-sans'];
			$open_sans->src = null;
		}

		// send it back
		return $styles;
	}

	/**
	 * hop into the set of default JS files to allow for
	 * disabling as needed filter to allow other mods
	 *
	 * @param  object $scripts all the registered JS items
	 * @return object $scripts the same object, possibly filtered
	 */
	public function block_script_load( $scripts ) {
		// bail if disabled
		if ( ! $this->enabled() ) {
			return $scripts;
		}

		// make sure we have something registered first
		if ( ! isset( $scripts->registered ) ) {
			return $scripts;
		}

		// fetch our registered scripts
		$registered = $scripts->registered;

		// pass the entire set of registered data to the action to allow a bypass
		do_action( 'airplane_mode_script_load', $registered );

		/*
		 * nothing actually being done here at the present time. this is a
		 * placeholder for being able to modify the script loading in the same
		 * manner that we do the CSS files
		 */

		// send it back
		return $scripts;
	}

	/**
	 * replace all instances of gravatar with a local image file
	 * to remove the call to remote service
	 *
	 * @param  [type] $avatar      [description]
	 * @param  [type] $id_or_email [description]
	 * @param  [type] $size        [description]
	 * @param  [type] $default     [description]
	 * @param  [type] $alt         [description]
	 * @return [type]              [description]
	 */
	public function replace_gravatar( $avatar, $id_or_email, $size, $default, $alt ) {
		// bail if disabled
		if ( ! $this->enabled() ) {
			return $avatar;
		}

		// swap out the file
		$image = plugins_url( '/lib/img/blank-32.png', __FILE__ );
		$avatar = "<img alt='{$alt}' src='{$image}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

		// return the item
		return $avatar;
	}

	/**
	 * disable all the HTTP requests being made with the action
	 * happening before the status check so others can allow certain
	 * items as desired
	 *
	 * @param  boolean $status [description]
	 * @param  array   $args   [description]
	 * @param  string  $url    [description]
	 * @return [type]          [description]
	 */
	public function disable_http_reqs( $status = false, $args = array(), $url = '' ) {
		// pass our data to the action to allow a bypass
		do_action( 'airplane_mode_http_args', $status, $args, $url );

		// disable the http requests only if enabled
		return $this->enabled();
	}

	/**
	 * load our small CSS file for the toggle switch
	 * @return [type] [description]
	 */
	public function toggle_css() {
		wp_enqueue_style( 'airplane-mode', plugins_url( '/lib/css/airplane-mode.css', __FILE__), array(), AIRMDE_VER, 'all' );
	}

	/**
	 * check the user action from the toggle switch to set the option
	 * to 'on' or 'off'
	 *
	 * @return void
	 */
	public function toggle_check() {
		// bail if current user doesnt have cap
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// check for our nonce
		if ( ! isset( $_GET['airmde_nonce'] ) || ! wp_verify_nonce( $_GET['airmde_nonce'], 'airmde_nonce' ) ) {
			return;
		}

		// now check for our query string
		if ( ! isset( $_REQUEST['airplane-mode'] ) || ! in_array( $_REQUEST['airplane-mode'], array( 'on', 'off' ) ) ) {
			return;
		}

		// update the setting
		update_option( 'airplane-mode', sanitize_key( $_REQUEST['airplane-mode'] ) );

		// and go about our business
		return;
	}

	/**
	 * add our quick toggle to the admin bar to enable / disable
	 * @return [type] [description]
	 */
	public function admin_bar_toggle() {
		// bail if current user doesnt have cap
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// call our global admin bar object
		global $wp_admin_bar;

		// get the current status
		$status = $this->enabled();

		// set our toggle variable paramater (in reverse since we want the opposite action)
		$toggle = $status ? 'off' : 'on';

		// determine our class based on the status
		$class  = 'airplane-toggle-icon-';
		$class .= $status ? 'on' : 'off';

		// get my text
		$text = __( 'Airplane Mode', 'airplane-mode' );

		// get my icon
		$icon = '<span class="airplane-toggle-icon ' . esc_attr( $class ) . '"></span>';

		// get our link with the status paramater
		$link = wp_nonce_url( add_query_arg( 'airplane-mode', $toggle ), 'airmde_nonce', 'airmde_nonce' );

		// now add the admin bar link
		$wp_admin_bar->add_menu(
			array(
				'id'       => 'airplane-mode-toggle',
				'title'    => $icon . $text,
				'href'     => $link,
				'position' => 0,
			)
		);
	}

/// end class
}

// Instantiate our class
$Airplane_Mode_Core = Airplane_Mode_Core::getInstance();
