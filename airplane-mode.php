<?php
/**
 * Plugin Name: Airplane Mode
 * Plugin URI: http://reaktivstudios.com/
 * Description: Control loading of external files when developing locally
 * Author: Andrew Norcross
 * Author URI: http://reaktivstudios.com/
 * Version: 0.1.6
 * Text Domain: airplane-mode
 * Requires WP: 4.0
 * Domain Path: languages
 * GitHub Plugin URI: https://github.com/norcross/airplane-mode
 */

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Andrew Norcross
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

if ( ! defined( 'AIRMDE_BASE' ) ) {
	define( 'AIRMDE_BASE', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'AIRMDE_DIR' ) ) {
	define( 'AIRMDE_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'AIRMDE_VER' ) ) {
	define( 'AIRMDE_VER', '0.1.6' );
}

if ( ! class_exists( 'Airplane_Mode_Core' ) ) {

	/**
	 * Call our class.
	 */
	class Airplane_Mode_Core {

		/**
		 * Static property to hold our singleton instance
		 * @var $instance
		 */
		static $instance = false;

		/**
		 * Set a var for the number of HTTP requests.
		 * @var $http_count
		 */
		private $http_count = 0;

		/**
		 * This is our constructor. There are many like it, but this one is mine.
		 */
		private function __construct() {
			add_action( 'plugins_loaded',                       array( $this, 'textdomain'              )           );
			add_action( 'style_loader_src',                     array( $this, 'block_style_load'        ),  100     );
			add_action( 'script_loader_src',                    array( $this, 'block_script_load'       ),  100     );
			add_action( 'admin_init',                           array( $this, 'remove_update_crons'     )           );
			add_action( 'admin_init',                           array( $this, 'remove_schedule_hook'    )           );

			add_filter( 'embed_oembed_html',                    array( $this, 'block_oembed_html'       ),  1,  4   );
			add_filter( 'get_avatar',                           array( $this, 'replace_gravatar'        ),  1,  5   );
			add_filter( 'map_meta_cap',                         array( $this, 'prevent_auto_updates'    ),  10, 2   );
			add_filter( 'default_avatar_select',                array( $this, 'default_avatar'          )           );

			// Kill all the http requests.
			add_filter( 'pre_http_request',                     array( $this, 'disable_http_reqs'       ),  10, 3   );

			// Check for our query string and handle accordingly.
			add_action( 'init',                                 array( $this, 'toggle_check'            )           );

			// Check for status change and purge transients as needed.
			add_action( 'airplane_mode_status_change',          array( $this, 'purge_transients'        )           );

			// Add our counter action.
			add_action( 'airplane_mode_http_args',              array( $this, 'count_http_requests'     ),  0, 0    );

			// CSS loader and top toggle.
			add_action( 'admin_bar_menu',                       array( $this, 'admin_bar_toggle'        ),  9999    );
			add_action( 'wp_enqueue_scripts',                   array( $this, 'toggle_css'              ),  9999    );
			add_action( 'admin_enqueue_scripts',                array( $this, 'toggle_css'              ),  9999    );
			add_action( 'login_enqueue_scripts',                array( $this, 'toggle_css'              ),  9999    );

			// Body class on each location for the display.
			add_filter( 'body_class',                           array( $this, 'body_class'              )           );
			add_filter( 'login_body_class',                     array( $this, 'body_class'              )           );
			add_filter( 'admin_body_class',                     array( $this, 'admin_body_class'        )           );

			// Remove bulk action for updating themes/plugins.
			add_filter( 'bulk_actions-plugins',                 array( $this, 'remove_bulk_actions'     )           );
			add_filter( 'bulk_actions-themes',                  array( $this, 'remove_bulk_actions'     )           );
			add_filter( 'bulk_actions-plugins-network',         array( $this, 'remove_bulk_actions'     )           );
			add_filter( 'bulk_actions-themes-network',          array( $this, 'remove_bulk_actions'     )           );

			// Admin UI items.
			add_action( 'admin_menu',                           array( $this, 'admin_menu_items'        ),  9999    );
			add_action( 'network_admin_menu',                   array( $this, 'ms_admin_menu_items'     ),  9999    );
			add_filter( 'install_plugins_tabs',                 array( $this, 'plugin_add_tabs'         )           );

			// Theme update API for different calls.
			add_filter( 'themes_api_args',                      array( $this, 'bypass_theme_api'        ),  10, 2   );

			// Time based transient checks.
			add_filter( 'pre_site_transient_update_themes',     array( $this, 'last_checked_themes'     )           );
			add_filter( 'pre_site_transient_update_plugins',    array( $this, 'last_checked_plugins'    )           );
			add_filter( 'pre_site_transient_update_core',       array( $this, 'last_checked_core'       )           );
			add_filter( 'site_transient_update_themes',         array( $this, 'remove_update_array'     )           );
			add_filter( 'site_transient_update_plugins',        array( $this, 'remove_update_array'     )           );

			// Our activation / deactivation triggers.
			register_activation_hook( __FILE__,                 array( $this, 'create_setting'          )           );
			register_deactivation_hook( __FILE__,               array( $this, 'remove_setting'          )           );

			// All our various filter checks.
			if ( $this->enabled() ) {

				// Keep jetpack from attempting external requests.
				add_filter( 'jetpack_development_mode',             '__return_true', 9999 );

				// Disable automatic updater updates.
				add_filter( 'automatic_updater_disabled',           '__return_true' );

				// Hijack the themes api setup to bypass the API call.
				add_filter( 'themes_api',                           '__return_true' );

				// Tell WordPress we are on a version control system to add additional blocks.
				add_filter( 'automatic_updates_is_vcs_checkout',    '__return_true' );

				// Disable translation updates.
				add_filter( 'auto_update_translation',              '__return_false' );

				// Disable minor core updates.
				add_filter( 'allow_minor_auto_core_updates',        '__return_false' );

				// Disable major core updates.
				add_filter( 'allow_major_auto_core_updates',        '__return_false' );

				// Disable dev core updates.
				add_filter( 'allow_dev_auto_core_updates',          '__return_false' );

				// Disable overall core updates.
				add_filter( 'auto_update_core',                     '__return_false' );
				add_filter( 'wp_auto_update_core',                  '__return_false' );

				// Disable automatic plugin and theme updates (used by WP to force push security fixes).
				add_filter( 'auto_update_plugin',                   '__return_false' );
				add_filter( 'auto_update_theme',                    '__return_false' );

				// Disable debug emails (used by core for rollback alerts in automatic update deployment).
				add_filter( 'automatic_updates_send_debug_email',   '__return_false' );

				// Disable update emails (for when we push the new WordPress versions manually) as well
				// as the notification there is a new version emails.
				add_filter( 'auto_core_update_send_email',          '__return_false' );
				add_filter( 'send_core_update_notification_email',  '__return_false' );
				add_filter( 'automatic_updates_send_debug_email ',  '__return_false', 1 );

				// Get rid of the version number in the footer.
				add_filter( 'update_footer',                        '__return_empty_string', 11 );

				// Filter out the pre core option.
				add_filter( 'pre_option_update_core',               '__return_null' );

				// Remove some actions.
				remove_action( 'admin_init',            'wp_plugin_update_rows' );
				remove_action( 'admin_init',            'wp_theme_update_rows' );
				remove_action( 'admin_notices',         'maintenance_nag' );
				remove_action( 'init',                  'wp_schedule_update_checks' );

				// Add back the upload tab.
				add_action( 'install_themes_upload',    'install_themes_upload', 10, 0 );

				// Define core contants for more protection.
				if ( ! defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
					define( 'AUTOMATIC_UPDATER_DISABLED', true );
				}
				if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) ) {
					define( 'WP_AUTO_UPDATE_CORE', false );
				}
			}
		}

		/**
		 * If an instance exists, this returns it.  If not, it creates one and
		 * returns it.
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
		 * Load our textdomain for localization.
		 *
		 * @return void
		 */
		public function textdomain() {
			load_plugin_textdomain( 'airplane-mode', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Set our initial airplane mode setting to 'on' on activation.
		 */
		public function create_setting() {
			add_site_option( 'airplane-mode', 'on' );
			set_transient( 'available_translations', '', 999999999999 );
			set_transient( 'wporg_theme_feature_list', array(), 999999999999 );
		}

		/**
		 * Remove our setting on plugin deactivation.
		 */
		public function remove_setting() {
			delete_option( 'airplane-mode' );
			delete_site_option( 'airplane-mode' );
			delete_transient( 'available_translations' );
			delete_transient( 'wporg_theme_feature_list' );
		}

		/**
		 * Helper function to check the current status.
		 *
		 * @return bool True if status is 'on'; false if not.
		 */
		public function enabled() {

			// Bail if CLI.
			if ( defined( 'WP_CLI' ) and WP_CLI ) {
				return false;
			}

			// Pull our status from the options table.
			$option = get_site_option( 'airplane-mode' );

			// Backup check for regular options table.
			if ( false === $option ) {
				$option = get_option( 'airplane-mode' );
			}

			// Return the option flag.
			return 'on' === $option;
		}

		/**
		 * Check the URL of a stylesheet and remove any that are not on the local URL.
		 *
		 * @param  string $source  The source URL of the CSS sheet.
		 *
		 * @return string $source  The same URL, or null.
		 */
		public function block_style_load( $source ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $source;
			}

			// Parse the URL being passed to pull out the host.
			$parsed = parse_url( $source, PHP_URL_HOST );

			// First run the filter to allow a source host to get through.
			if ( false === apply_filters( 'airplane_mode_parse_style', true, $parsed ) ) {
				return $source;
			}

			// If we don't share the same URL as the site itself, return null. Otherwise return the URL.
			return isset( $parsed ) && false === strpos( home_url(), $parsed )
				? new Airplane_Mode_WP_Error( 'airplane_mode_enabled', __( 'Airplane Mode blocked style', 'airplane-mode' ), array(
					'return' => '',
					'src'    => $source,
				) )
				: $source;
		}

		/**
		 * Check the URL of a JS file and remove any that are not on the local URL.
		 *
		 * @param  string $source  The source URL of the JS file.
		 *
		 * @return string $source  The same URL, or null.
		 */
		public function block_script_load( $source ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $source;
			}

			// Parse the URL being passed to pull out the host.
			$parsed = parse_url( $source, PHP_URL_HOST );

			// First run the filter to allow a source host to get through.
			if ( false === apply_filters( 'airplane_mode_parse_script', true, $parsed ) ) {
				return $source;
			}

			// If we don't share the same URL as the site itself, return null. Otherwise return the URL.
			return isset( $parsed ) && false === strpos( home_url(), $parsed )
				? new Airplane_Mode_WP_Error( 'airplane_mode_enabled', __( 'Airplane Mode blocked script', 'airplane-mode' ), array(
					'return' => '',
					'src'    => $source,
				) )
				: $source;
		}

		/**
		 * Block oEmbeds from displaying.
		 *
		 * @param string $html The embed HTML.
		 * @param string $url The attempted embed URL.
		 * @param array  $attr An array of shortcode attributes.
		 * @param int    $post_ID Post ID.
		 *
		 * @return string
		 */
		public function block_oembed_html( $html, $url, $attr, $post_ID ) {
			return $this->enabled() ? sprintf( '<div class="loading-placeholder airplane-mode-placeholder"><p>%s</p></div>', sprintf( __( 'Airplane Mode is enabled. oEmbed blocked for %1$s.', 'airplane-mode' ), esc_url( $url ) ) ) : $html;
		}

		/**
		 * Add body class to front-end pages and login based on plugin status.
		 *
		 * @param  array $classes  The existing array of body classes.
		 *
		 * @return array $classes  The updated array of body classes.
		 */
		public function body_class( $classes ) {

			// Add the class based on the current status.
			$classes[]	= $this->enabled() ? 'airplane-mode-enabled' : 'airplane-mode-disabled';

			// Also add in the margin setup for Query Monitor because I'm a perfectionist.
			if ( ! class_exists( 'QueryMonitor' ) || defined( 'QM_DISABLED' ) && QM_DISABLED ) {
				$classes[]	= 'airplane-mode-no-qm';
			}

			// Return our array of classes.
			return $classes;
		}

		/**
		 * Add body class to admin pages based on plugin status.
		 *
		 * @param  string $classes  The existing space-separated list of CSS classes.
		 *
		 * @return string $classes  The updated space-separated list of CSS classes.
		 */
		public function admin_body_class( $classes ) {

			// First add the standard set of classes based on status.
			$classes .= $this->enabled() ? ' airplane-mode-enabled' : ' airplane-mode-disabled';

			// Also add in the margin setup for Query Monitor because I'm a perfectionist.
			if ( ! class_exists( 'QueryMonitor' ) || defined( 'QM_DISABLED' ) && QM_DISABLED ) {
				$classes .= ' airplane-mode-no-qm';
			}

			// Return our string of classes.
			return $classes;
		}

		/**
		 * Remove menu items for updates from a standard WP install.
		 *
		 * @return null
		 */
		public function admin_menu_items() {

			// Bail if disabled, or on a multisite.
			if ( ! $this->enabled() || is_multisite() ) {
				return;
			}

			// Remove our items.
			remove_submenu_page( 'index.php', 'update-core.php' );
			remove_submenu_page( 'index.php', 'index.php' );
		}

		/**
		 * Remove menu items for updates from a multisite instance.
		 *
		 * @return null
		 */
		public function ms_admin_menu_items() {

			// Bail if disabled or not on our network admin.
			if ( ! $this->enabled() || ! is_network_admin() ) {
				return;
			}

			// Remove the items.
			remove_submenu_page( 'index.php', 'upgrade.php' );
		}

		/**
		 * Replace all instances of gravatar with a local image file
		 * to remove the call to remote service.
		 *
		 * @param string            $avatar       Image tag for the user's avatar.
		 * @param int|object|string $id_or_email  A user ID, email address, or comment object.
		 * @param int               $size         Square avatar width and height in pixels to retrieve.
		 * @param string            $default      URL to a default image to use if no avatar is available.
		 * @param string            $alt          Alternative text to use in the avatar image tag.
		 *
		 * @return string `<img>` tag for the user's avatar.
		 */
		public function replace_gravatar( $avatar, $id_or_email, $size, $default, $alt ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $avatar;
			}

			// Swap out the file for a base64 encoded image.
			$image  = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
			$avatar = "<img alt='{$alt}' src='{$image}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' style='background:#eee;' />";

			// Return the avatar.
			return $avatar;
		}

		/**
		 * Remove avatar images from the default avatar list
		 *
		 * @param  string $avatar_list  List of default avatars.
		 *
		 * @return string               Updated list with images removed
		 */
		public function default_avatar( $avatar_list ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $avatar_list;
			}

			// Remove images.
			$avatar_list = preg_replace( '|<img([^>]+)> |i', '', $avatar_list );

			// Send back the list.
			return $avatar_list;
		}

		/**
		 * Disable all the HTTP requests being made with the action
		 * happening before the status check so others can allow certain
		 * items as desired.
		 *
		 * @param  bool|array|WP_Error $status  Whether to preempt an HTTP request return. Default false.
		 * @param  array               $args    HTTP request arguments.
		 * @param  string              $url     The request URL.
		 *
		 * @return bool|array|WP_Error          A WP_Error object if Airplane Mode is enabled. Original $status if not.
		 */
		public function disable_http_reqs( $status = false, $args = array(), $url = '' ) {

			// Pass our data to the action to allow a bypass.
			do_action( 'airplane_mode_http_args', $status, $args, $url );

			// Disable the http requests only if enabled.
			return $this->enabled() ? new WP_Error( 'airplane_mode_enabled', __( 'Airplane Mode is enabled', 'airplane-mode' ) ) : $status;
		}

		/**
		 * Load our small CSS file for the toggle switch.
		 */
		public function toggle_css() {

			// Set a suffix for loading the minified or normal.
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';

			// Load the CSS file itself.
			wp_enqueue_style( 'airplane-mode', plugins_url( '/lib/css/airplane-mode' . $suffix, __FILE__ ), array(), AIRMDE_VER, 'all' );
		}

		/**
		 * Check the user action from the toggle switch to set the option
		 * to 'on' or 'off'.
		 *
		 * @return void if any of the sanity checks fail and we bail early.
		 */
		public function toggle_check() {

			// Bail if current user doesn't have cap.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Set a sanitized variable of our potential nonce and request.
			$nonce  = isset( $_GET['airmde_nonce'] ) ? sanitize_key( $_GET['airmde_nonce'] ) : '';
			$switch = isset( $_REQUEST['airplane-mode'] ) ? sanitize_key( $_REQUEST['airplane-mode'] ) : '';

			// Check for our nonce.
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'airmde_nonce' ) ) {
				return;
			}

			// Now check for our query string.
			if ( empty( $switch ) || ! in_array( $switch, array( 'on', 'off' ) ) ) {
				return;
			}

			// Delete old per-site option.
			delete_option( 'airplane-mode' );

			// Update the setting.
			update_site_option( 'airplane-mode', sanitize_key( $_REQUEST['airplane-mode'] ) );

			// Fire action to allow for functions to run on status change.
			do_action( 'airplane_mode_status_change', $switch );

			// And go about our business.
			wp_redirect( self::get_redirect() );
			exit;
		}

		/**
		 * Fetch the URL to redirect to after toggling Airplane Mode.
		 *
		 * @return string The URL to redirect to.
		 */
		protected static function get_redirect() {

			// Return the args for the actual redirect.
			$redirect = remove_query_arg( array(
				'airplane-mode',
				'airmde_nonce',
				'user_switched',
				'switched_off',
				'switched_back',
				'message',
				'update',
				'updated',
				'settings-updated',
				'saved',
				'activated',
				'activate',
				'deactivate',
				'enabled',
				'disabled',
				'locked',
				'skipped',
				'deleted',
				'trashed',
				'untrashed',
			) );

			// Redirect away from the update core page.
			$redirect = str_replace( 'update-core.php', '', $redirect );

			// And return the redirect.
			return apply_filters( 'airplane_mode_redirect_url', $redirect );
		}

		/**
		 * Add our quick toggle to the admin bar to enable / disable
		 *
		 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
		 *
		 * @return void if current user can't manage options and we bail early.
		 */
		public function admin_bar_toggle( WP_Admin_Bar $wp_admin_bar ) {

			// Bail if current user doesn't have cap.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Get the current status.
			$status = $this->enabled();

			// Set a title message (translatable).
			$title  = $status ? __( 'Airplane Mode is enabled.', 'airplane-mode' ) : __( 'Airplane Mode is disabled.', 'airplane-mode' );

			// Set our toggle variable parameter (in reverse since we want the opposite action).
			$toggle = $status ? 'off' : 'on';

			// Set my HTTP request count label to a blank string for now.
			$label = '';

			// Get and display the HTTP count when Query Monitor isn't active.
			if ( ! class_exists( 'QueryMonitor' ) || defined( 'QM_DISABLED' ) && QM_DISABLED ) {

				// Pull my HTTP count.
				$count  = ! empty( $this->http_count ) ? number_format_i18n( $this->http_count ) : 0;

				$count_label = sprintf( _n( 'There was %s HTTP request.', 'There were %s HTTP requests.', $count, 'airplane-mode' ), $count );

				// Build the markup for my label.
				$label = '<span class="ab-label" aria-hidden="true">' . absint( $count ) . '</span>';
				$label .= '<span class="screen-reader-text">' . esc_html( $count_label ) . '</span>';

				// Amend the tooltip title with the count.
				$title .= '&nbsp;' . $count_label;
			}

			// Get our link with the status parameter.
			$link = wp_nonce_url( add_query_arg( 'airplane-mode', $toggle ), 'airmde_nonce', 'airmde_nonce' );

			// Now add the admin bar link.
			$wp_admin_bar->add_menu(
				array(
					'id'        => 'airplane-mode-toggle',
					'title'     => '<span class="ab-icon"></span>' . $label,
					'href'      => esc_url( $link ),
					'position'  => 0,
					'meta'      => array(
						'title' => $title,
					),
				)
			);
		}

		/**
		 * Filter a user's meta capabilities to prevent auto-updates from being attempted.
		 *
		 * @param array  $caps    Returns the user's actual capabilities.
		 * @param string $cap     Capability name.
		 *
		 * @return array The user's filtered capabilities.
		 */
		public function prevent_auto_updates( $caps, $cap ) {

			// Check for being enabled and look for specific cap requirements.
			if ( $this->enabled() && in_array( $cap, array( 'update_plugins', 'update_themes', 'update_core' ) ) ) {
				$caps[] = 'do_not_allow';
			}

			// Send back the data array.
			return $caps;
		}

		/**
		 * Check the new status after airplane mode has been enabled or
		 * disabled and purge related transients
		 *
		 * @return void
		 */
		public function purge_transients() {

			// First check for the filter to avoid this action overall.
			if ( false === $clear = apply_filters( 'airplane_mode_purge_transients', true ) ) {
				return;
			}

			// Purge the transients related to updates when disabled.
			if ( ! $this->enabled() ) {
				delete_site_transient( 'update_core' );
				delete_site_transient( 'update_plugins' );
				delete_site_transient( 'update_themes' );
			}
		}

		/**
		 * Remove all the various places WP does the update checks. As you can see there are a lot of them.
		 *
		 * @return null
		 */
		public function remove_update_crons() {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return;
			}

			// Do a quick check to make sure we can remove things.
			if ( ! function_exists( 'remove_action' ) ) {
				return;
			}

			// Disable Theme Updates.
			remove_action( 'load-update-core.php', 'wp_update_themes' );
			remove_action( 'load-themes.php', 'wp_update_themes' );
			remove_action( 'load-update.php', 'wp_update_themes' );
			remove_action( 'wp_update_themes', 'wp_update_themes' );
			remove_action( 'admin_init', '_maybe_update_themes' );

			// Disable Plugin Updates.
			remove_action( 'load-update-core.php', 'wp_update_plugins' );
			remove_action( 'load-plugins.php', 'wp_update_plugins' );
			remove_action( 'load-update.php', 'wp_update_plugins' );
			remove_action( 'wp_update_plugins', 'wp_update_plugins' );
			remove_action( 'admin_init', '_maybe_update_plugins' );

			// Disable Core updates
			// @@ TODO figure out how to do this without a create_function.
			add_action( 'init', create_function( '', 'remove_action( \'init\', \'wp_version_check\' );' ), 2 );

			// Don't look for WordPress updates. Seriously!
			remove_action( 'wp_version_check', 'wp_version_check' );
			remove_action( 'admin_init', '_maybe_update_core' );

			// Not even maybe.
			remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );
			remove_action( 'admin_init', 'wp_maybe_auto_update' );
			remove_action( 'admin_init', 'wp_auto_update_core' );
		}

		/**
		 * Remove all the various schedule hooks for themes, plugins, etc.
		 *
		 * @return null
		 */
		public function remove_schedule_hook() {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return;
			}

			wp_clear_scheduled_hook( 'wp_update_themes' );
			wp_clear_scheduled_hook( 'wp_update_plugins' );
			wp_clear_scheduled_hook( 'wp_version_check' );
			wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
		}

		/**
		 * Hijack the themes api setup to bypass the API call.
		 *
		 * @param object $args    Arguments used to query for installer pages from the Themes API.
		 * @param string $action  Requested action. Likely values are 'theme_information',
		 *                        'feature_list', or 'query_themes'.
		 *
		 * @return bool           true or false depending on the type of query
		 */
		public function bypass_theme_api( $args, $action ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $args;
			}

			// Return false on feature list to avoid the API call.
			return ! empty( $action ) && 'feature_list' === $action ? false : $args;
		}

		/**
		 * Always send back that the latest version of WordPress is the one we're running
		 *
		 * @return object     the modified output with our information
		 */
		public function last_checked_core() {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return false;
			}

			// Call the global WP version.
			global $wp_version;

			// Return our object.
			return (object) array(
				'last_checked'		=> time(),
				'updates'			=> array(),
				'version_checked'	=> $wp_version,
			);
		}

		/**
		 * Always send back that the latest version of our theme is the one we're running
		 *
		 * @return object     the modified output with our information
		 */
		public function last_checked_themes() {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return false;
			}

			// Call the global WP version.
			global $wp_version;

			// Set a blank data array.
			$data = array();

			// Build my theme data array.
			foreach ( wp_get_themes() as $theme ) {
				$data[ $theme->get_stylesheet() ] = $theme->get( 'Version' );
			}

			// Return our object.
			return (object) array(
				'last_checked'		=> time(),
				'updates'			=> array(),
				'version_checked'	=> $wp_version,
				'checked'			=> $data,
			);
		}

		/**
		 * Always send back that the latest version of our plugins are the one we're running
		 *
		 * @return object     the modified output with our information
		 */
		public function last_checked_plugins() {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return false;
			}

			// Call the global WP version.
			global $wp_version;

			// Set a blank data array.
			$data = array();

			// Add our plugin file if we don't have it.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Build my plugin data array.
			foreach ( get_plugins() as $file => $pl ) {
				$data[ $file ] = $pl['Version'];
			}

			// Return our object.
			return (object) array(
				'last_checked'		=> time(),
				'updates'			=> array(),
				'version_checked'	=> $wp_version,
				'checked'			=> $data,
			);
		}

		/**
		 * Return an empty array of items requiring update for both themes and plugins
		 *
		 * @param  array $items  All the items being passed for update.
		 *
		 * @return array         An empty array, or the original items if not enabled.
		 */
		public function remove_update_array( $items ) {
			return ! $this->enabled() ? $items : array();
		}

		/**
		 * Remove the ability to update plugins/themes from single
		 * site and multisite bulk actions
		 *
		 * @param  array $actions  All the bulk actions.
		 *
		 * @return array $actions  The remaining actions
		 */
		public function remove_bulk_actions( $actions ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $actions;
			}

			// Set an array of items to be removed with optional filter.
			if ( false === $remove = apply_filters( 'airplane_mode_bulk_items', array( 'update-selected', 'update', 'upgrade' ) ) ) {
				return $actions;
			}

			// Loop the item array and unset each.
			foreach ( $remove as $key ) {
				unset( $actions[ $key ] );
			}

			// Return the remaining.
			return $actions;
		}

		/**
		 * Remove the tabs on the plugin page to add new items
		 * since they require the WP connection and will fail.
		 *
		 * @param  array $nonmenu_tabs  All the tabs displayed.
		 * @return array $nonmenu_tabs  The remaining tabs.
		 */
		public function plugin_add_tabs( $nonmenu_tabs ) {

			// Bail if disabled.
			if ( ! $this->enabled() ) {
				return $nonmenu_tabs;
			}

			// Set an array of tabs to be removed with optional filter.
			if ( false === $remove = apply_filters( 'airplane_mode_bulk_items', array( 'featured', 'popular', 'recommended', 'favorites', 'beta' ) ) ) {
				return $nonmenu_tabs;
			}

			// Loop the item array and unset each.
			foreach ( $remove as $key ) {
				unset( $nonmenu_tabs[ $key ] );
			}

			// Return the tabs.
			return $nonmenu_tabs;
		}

		/**
		 * Increase HTTP request counter by one.
		 *
		 * @return void
		 */
		public function count_http_requests() {
			$this->http_count++;
		}

	} // end class

} //end class_exists

if ( ! class_exists( 'Airplane_Mode_WP_Error' ) ) {

	class Airplane_Mode_WP_Error extends WP_Error {

		public function __tostring() {
			$data = $this->get_error_data();
			return $data['return'];
		}

	}

}

// Instantiate our class.
$Airplane_Mode_Core = Airplane_Mode_Core::getInstance();
