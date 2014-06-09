<?php
/*
Plugin Name: WP Local Dev Environment
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


if( ! defined( 'LCLDEV_BASE ' ) ) {
	define( 'LCLDEV_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'LCLDEV_DIR' ) ) {
	define( 'LCLDEV_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'LCLDEV_VER' ) ) {
	define( 'LCLDEV_VER', '0.0.1' );
}


class LCL_Dev_Core
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
		add_action		(	'wp_enqueue_scripts',			array(	$this,	'file_remove_replace'	), 9999		);
		add_action		(	'admin_enqueue_scripts',		array(	$this,	'file_remove_replace'	), 9999		);
		add_action		(	'login_enqueue_scripts',		array(	$this,	'file_remove_replace'	), 9999		);

		add_filter		(	'get_avatar',					array(	$this,	'replace_gravatar'		),	1,	5	);

		// kill all the http requests
		add_filter		(	'pre_http_request',	'__return_true' );
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
	 * replace Open Sans file with blank CSS to
	 * avoid breaking dependencies. issue addressed
	 * in trac ticket #28478
	 *
	 * @return string	url of blank CSS file
	 */
	public function file_remove_replace() {

		// remove the files
		wp_deregister_style( 'open-sans' );

		// add a blank file to prevent dependency issues
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

		// swap out the file
		$avatar	= plugins_url( '/lib/blank-32.png', __FILE__);
		$avatar	= "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

		// return the item
		return $avatar;
	}


/// end class
}

// Instantiate our class
$LCL_Dev_Core = LCL_Dev_Core::getInstance();