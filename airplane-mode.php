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
		add_action		(	'plugins_loaded',					array(  $this,  'textdomain'			)			);
		add_action		(	'wp_enqueue_scripts',				array(	$this,	'file_remove_replace'	),	9999	);
		add_action		(	'admin_enqueue_scripts',			array(	$this,	'file_remove_replace'	),	9999	);
		add_action		(	'login_enqueue_scripts',			array(	$this,	'file_remove_replace'	),	9999	);

		add_filter		(	'get_avatar',						array(	$this,	'replace_gravatar'		),	1,	5	);

		// kill all the http requests
		add_filter		(	'pre_http_request',					array(	$this,	'disable_http_req'		)			);

		// settings
		add_action		(	'init',								array(	$this,	'disable_request'		)			);
		add_action		(	'admin_init',						array(	$this,	'load_settings'			)			);

		add_action		(	'wp_head',							array(	$this,	'toggle_css'			),	9999	);
		add_action		(	'admin_head',						array(	$this,	'toggle_css'			),	9999	);
		add_action		(	'login_head',						array(	$this,	'toggle_css'			),	9999	);

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
	 * [check_status description]
	 * @return [type] [description]
	 */
	public function check_status() {

		$status	= get_option( 'airplane-mode' );

		if ( ! empty( $status ) ) {
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
		if ( $this->check_status() ) {
			return;
		}

		// deregister style first
		wp_deregister_style( 'open-sans' );

		// register a blank file to prevent dependency issues
		wp_register_style( 'open-sans', plugins_url( '/lib/blank.css', __FILE__) );

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
		if ( $this->check_status() ) {
			return $avatar;
		}

		// swap out the file
		$avatar	= plugins_url( '/lib/blank-32.png', __FILE__);
		$avatar	= "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

		// return the item
		return $avatar;
	}

	/**
	 * [disable_http_req description]
	 * @return [type] [description]
	 */
	public function disable_http_req() {

		// disable the http requests only if not disabled
		if ( ! $this->check_status() ) {
			return true;
		}

	}

	/**
	 * register our new setting
	 *
	 * @return void
	 */
	public function load_settings() {

		register_setting( 'airplane-mode', 'airplane-mode' );

	}

	/**
	 * [disable_request description]
	 * @return [type] [description]
	 */
	public function disable_request() {

		// bail if current user doesnt have cap
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// get the current status
		$status	= $this->check_status();

		// load the approprate bar
		if ( ! $status || isset( $_REQUEST['airplane-mode-toggle'] ) && $_REQUEST['airplane-mode-toggle'] == 'on' ) {
			add_action ( 'admin_bar_menu', array( $this, 'admin_bar_disable' ), 9999 );
		}

		if ( $status || isset( $_REQUEST['airplane-mode-toggle'] ) && $_REQUEST['airplane-mode-toggle'] == 'off' ) {
			add_action ( 'admin_bar_menu', array( $this, 'admin_bar_enable' ), 9999 );
		}

		// handle the setting
		if ( isset( $_REQUEST['airplane-mode-toggle'] ) && $_REQUEST['airplane-mode-toggle'] == 'on' ) {
			delete_option( 'localdev-disabled' );
		}

		if ( isset( $_REQUEST['airplane-mode-toggle'] ) && $_REQUEST['airplane-mode-toggle'] == 'off' ) {
			update_option( 'localdev-disabled', true );
		}

	}

	/**
	 * add our quick toggle to the admin bar to enable
	 * @return [type] [description]
	 */
	public function admin_bar_enable() {

		// call our global admin bar object
		global $wp_admin_bar;

		// get my text
		$text	= __( 'Airplane Mode', 'airplane-mode' );

		// get my icon
		$icon	= '<span class="airplane-toggle-icon airplane-toggle-icon-off"></span>';

		// get our link with a fancy nonce
		$link	= wp_nonce_url( add_query_arg( 'airplane-mode-toggle', 'on' ), 'airplane-mode' );

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

	/**
	 * add our quick toggle to the admin bar to disable
	 * @return [type] [description]
	 */
	public function admin_bar_disable() {

		// call our global admin bar object
		global $wp_admin_bar;

		// get my text
		$text	= __( 'Airplane Mode', 'airplane-mode' );

		// get my icon
		$icon	= '<span class="airplane-toggle-icon airplane-toggle-icon-on"></span>';

		// get our link with a fancy nonce
		$link	= wp_nonce_url( add_query_arg( 'airplane-mode-toggle', 'off' ), 'airplane-mode' );

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

	/**
	 * [toggle_css description]
	 * @return [type] [description]
	 */
	public function toggle_css() {
		?>
		<style>
		#wp-admin-bar-airplane-mode-toggle span.airplane-toggle-icon {
			height: 14px;
			width: 14px;
			border-radius: 50%;
			background: #ccc;
			display: inline-block;
			vertical-align: middle;
			margin-right: 7px;
			position: relative;
			bottom: 2px;
		}

		#wp-admin-bar-airplane-mode-toggle span.airplane-toggle-icon-on {
			background: green;
		}

		#wp-admin-bar-airplane-mode-toggle span.airplane-toggle-icon-off {
			background: red;
		}

		</style>
		<?php
	}

/// end class
}

// Instantiate our class
$Airplane_Mode_Core = Airplane_Mode_Core::getInstance();