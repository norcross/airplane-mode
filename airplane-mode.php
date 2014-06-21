<?php
/*
Plugin Name: Airplane Mode
Plugin URI: http://reaktivstudios.com/
Description: Control loading of external files when developing locally
Author: Andrew Norcross
Version: 0.0.1
Requires at least: 3.7
Author URI: http://reaktivstudios.com/
*/
/*  Copyright 2014 Andrew Norcross

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


if( ! defined( 'AIRMDE_BASE ' ) ) {
	define( 'AIRMDE_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'AIRMDE_DIR' ) ) {
	define( 'AIRMDE_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'AIRMDE_VER' ) ) {
	define( 'AIRMDE_VER', '0.0.1' );
}


class Airplane_Mode_Core
{

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
		add_action		(	'plugins_loaded',						array(  $this,  'textdomain'			)			);
		add_action		(	'wp_enqueue_scripts',					array(	$this,	'file_remove_replace'	),	9999	);
		add_action		(	'admin_enqueue_scripts',				array(	$this,	'file_remove_replace'	),	9999	);
		add_action		(	'login_enqueue_scripts',				array(	$this,	'file_remove_replace'	),	9999	);

		add_filter		(	'get_avatar',							array(	$this,	'replace_gravatar'		),	1,	5	);

		// kill all the http requests
		add_filter		(	'pre_http_request',						array(	$this,	'disable_http_reqs'		),	10, 3	);

		// check for our query string and handle accordingly
		add_action		(	'init',									array(	$this,	'toggle_check'			)			);

		// settings
		add_action		(	'admin_bar_menu',						array(	$this,	'admin_bar_toggle'		),	9999	);
		add_action		(	'wp_enqueue_scripts',					array(	$this,	'toggle_css'			),	9999	);
		add_action		(	'admin_enqueue_scripts',				array(	$this,	'toggle_css'			),	9999	);
		add_action		(	'login_enqueue_scripts',				array(	$this,	'toggle_css'			),	9999	);

		register_activation_hook	(	__FILE__,				array(	$this,	'create_setting'			)			);
		register_deactivation_hook	(	__FILE__,				array(	$this,	'remove_setting'			)			);

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

		update_option( 'airplane-mode', 'on' );

	}

	/**
	 * remove our setting field on plugin deactivation
	 *
	 * @return void
	 */
	public function remove_setting() {

		delete_option( 'airplane-mode' );

	}

	/**
	 * [check_status description]
	 * @return [type] [description]
	 */
	public function check_status() {

		// pull our status from the options table
		$status	= get_option( 'airplane-mode' );

		if ( ! empty( $status ) && $status == 'on' ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * replace Open Sans file with blank CSS to
	 * avoid breaking dependencies. issue addressed
	 * in trac ticket #28478
	 *
	 * @return string	url of blank CSS file
	 */
	public function file_remove_replace() {

		// bail if disabled
		if ( ! $this->check_status() ) {
			return;
		}

		// deregister style first
		wp_deregister_style( 'open-sans' );

		// register a blank file to prevent dependency issues
		wp_register_style( 'open-sans', plugins_url( '/lib/blanks/blank.css', __FILE__) );

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
		if ( ! $this->check_status() ) {
			return $avatar;
		}

		// swap out the file
		$image	= plugins_url( '/lib/blanks/blank-32.png', __FILE__);
		$avatar	= "<img alt='{$alt}' src='{$image}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

		// return the item
		return $avatar;
	}

	/**
	 * [disable_http_req description]
	 * @param  [type] $false [description]
	 * @param  [type] $args  [description]
	 * @param  [type] $url   [description]
	 * @return [type]        [description]
	 */
	public function disable_http_reqs( $false, $args, $url ) {

		// pass our data to the action to allow a bypass
		do_action( 'airplane_mode_http_args', $false, $args, $url );

		// disable the http requests only if not disabled
		if ( $this->check_status() ) {
			return true;
		}

	}

	/**
	 * [toggle_css description]
	 * @return [type] [description]
	 */
	public function toggle_css() {

		wp_enqueue_style( 'airplane-mode', plugins_url( '/lib/css/airplane-mode.css', __FILE__), array(), AIRMDE_VER, 'all' );

	}

	/**
	 * [toggle_check description]
	 * @return [type] [description]
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

		// get the status paramater (in reverse since we want the opposize action)
		$status	= $this->check_status() ? 'off' : 'on';

		// determine our class based on the status
		$class	= $this->check_status() ? 'airplane-toggle-icon-on' : 'airplane-toggle-icon-off';

		// get my text
		$text	= __( 'Airplane Mode', 'airplane-mode' );

		// get my icon
		$icon	= '<span class="airplane-toggle-icon ' . esc_attr( $class ) . '"></span>';

		// get our link with the status paramater
		$link	= wp_nonce_url( add_query_arg( 'airplane-mode', $status ), 'airmde_nonce', 'airmde_nonce' );

		// now add the admin bar link
		$wp_admin_bar->add_menu(
			array(
				'id'		=> 'airplane-mode-toggle',
				'title'		=> $icon . $text,
				'href'		=> $link,
				'position'	=> 0
			)
		);

	}


/// end class
}

// Instantiate our class
$Airplane_Mode_Core = Airplane_Mode_Core::getInstance();