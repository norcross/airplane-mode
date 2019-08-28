<?php
/**
 * Plugin Name: WP Core Blocker
 * Plugin URI: https://github.com/devgeniem/wp-core-blocker
 * Description: Disables WP from contacting wp.org servers and disables users from installing anything in wp-admin.
 * Author: Onni Hakala / Geniem Oy
 * Author URI: http://github.com/onnimonni
 * Version: 0.2.3
 * Requires WP: 4.4
 * GitHub Plugin URI: https://github.com/devgeniem/wp-core-blocker
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

namespace geniem\helper;

use WP_Error;
use stdClass;

// Ensure the class has not already been loaded.
if ( ! class_exists( __NAMESPACE__ . '\Core_Blocker' ) ) {

    /**
     * Call our class.
     */
    class Core_Blocker {
        /**
         * Activate hooks
         */
        static function init() {
            // Stop wp-cron from looking out for new plugin versions
            add_action( 'admin_init',                           array( __CLASS__, 'remove_update_crons'     )           );
            add_action( 'admin_init',                           array( __CLASS__, 'remove_schedule_hook'    )           );

            // Disable gravatars
            if ( defined( 'WP_CORE_BLOCKER_DISABLE_GRAVATAR' ) and WP_CORE_BLOCKER_DISABLE_GRAVATAR ) {
                add_filter( 'get_avatar',                           array( __CLASS__, 'replace_gravatar'        ),  1,  5   );
                add_filter( 'default_avatar_select',                array( __CLASS__, 'default_avatar'          )           );
            }

            // Prevent users from even trying to update plugins and themes
            add_filter( 'map_meta_cap',                         array( __CLASS__, 'prevent_auto_updates'    ),  10, 2   );

            // Remove bulk action for updating themes/plugins.
            add_filter( 'bulk_actions-plugins',                 array( __CLASS__, 'remove_bulk_actions'     )           );
            add_filter( 'bulk_actions-themes',                  array( __CLASS__, 'remove_bulk_actions'     )           );
            add_filter( 'bulk_actions-plugins-network',         array( __CLASS__, 'remove_bulk_actions'     )           );
            add_filter( 'bulk_actions-themes-network',          array( __CLASS__, 'remove_bulk_actions'     )           );


            // Admin UI items.
            add_action( 'admin_menu',                           array( __CLASS__, 'admin_menu_items'        ),  9999    );
            add_action( 'network_admin_menu',                   array( __CLASS__, 'ms_admin_menu_items'     ),  9999    );
            add_filter( 'install_plugins_tabs',                 array( __CLASS__, 'plugin_add_tabs'         )           );

            // Theme update API for different calls.
            add_filter( 'themes_api_args',                      array( __CLASS__, 'bypass_theme_api'        ),  10, 2   );
            add_filter( 'themes_api',                           '__return_false'                             ,  10, 2   );

            // Time based transient checks.
            add_filter( 'pre_site_transient_update_themes',     array( __CLASS__, 'last_checked_themes'     )           );
            add_filter( 'pre_site_transient_update_plugins',    array( __CLASS__, 'last_checked_plugins'    )           );
            add_filter( 'pre_site_transient_update_core',       array( __CLASS__, 'last_checked_core'       )           );
            add_filter( 'site_transient_update_themes',         array( __CLASS__, 'remove_update_array'     )           );
            add_filter( 'site_transient_update_plugins',        array( __CLASS__, 'remove_plugin_updates'   )           );

            // Remove admin news dashboard widget
            add_action( 'admin_init',                           array( __CLASS__, 'remove_dashboards'       )           );

            // Removes update check wp-cron
            remove_action( 'init',                  'wp_schedule_update_checks' );

            // Disable overall core updates.
            add_filter( 'auto_update_core',                     '__return_false' );
            add_filter( 'wp_auto_update_core',                  '__return_false' );

            // Disable automatic plugin and theme updates (used by WP to force push security fixes).
            add_filter( 'auto_update_plugin',                   '__return_false' );
            add_filter( 'auto_update_theme',                    '__return_false' );

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

            // Disable automatic updater updates.
            add_filter( 'automatic_updater_disabled',           '__return_true' );

            // Run various hooks if the plugin should be enabled
            if ( self::enabled() ) {

                // Disable WordPress from fetching available languages
                add_filter( 'pre_site_transient_available_translations', array( __CLASS__, 'available_translations' ) );

                // Prevent BuddyPress from falling back to Gravatar avatars.
                add_filter( 'bp_core_fetch_avatar_no_grav',         '__return_true' );

                // Hijack the themes api setup to bypass the API call.
                add_filter( 'themes_api',                           '__return_true' );

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
         * Checks when plugin should be enabled This offers nice compatibilty with wp-cli
         */
        static public function enabled() {
            // Bail if CLI.
            if ( defined( 'WP_CLI' ) and WP_CLI ) {
                return false;
            }

            return true;
        }

        /**
         * Remove menu items for updates from a standard WP install.
         *
         * @return null
         */
        static public function admin_menu_items() {

            // Bail if disabled, or on a multisite.
            if ( ! self::enabled() || is_multisite() ) {
                return;
            }

            // Remove our items.
            remove_submenu_page( 'index.php', 'update-core.php' );
        }

        /**
         * Remove WordPress news dashboard widget
         */
        static function remove_dashboards() {
            remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
        }

        /**
         * Remove menu items for updates from a multisite instance.
         *
         * @return null
         */
        static public function ms_admin_menu_items() {

            // Bail if disabled or not on our network admin.
            if ( ! self::enabled() || ! is_network_admin() ) {
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
        static public function replace_gravatar( $avatar, $id_or_email, $size, $default, $alt ) {

            // Bail if disabled.
            if ( ! self::enabled() ) {
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
        static public function default_avatar( $avatar_list ) {

            // Bail if disabled.
            if ( ! self::enabled() ) {
                return $avatar_list;
            }

            // Remove images.
            $avatar_list = preg_replace( '|<img([^>]+)> |i', '', $avatar_list );

            // Send back the list.
            return $avatar_list;
        }

        /**
         * Fetch the URL to redirect to after toggling Airplane Mode.
         *
         * @return string The URL to redirect to.
         */
        protected static function get_redirect() {

            // Return the args for the actual redirect.
            $redirect = remove_query_arg( array(
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
            return apply_filters( 'core_blocker_redirect_url', $redirect );
        }

        /**
         * Filter a user's meta capabilities to prevent auto-updates from being attempted.
         *
         * @param array  $caps    Returns the user's actual capabilities.
         * @param string $cap     Capability name.
         *
         * @return array The user's filtered capabilities.
         */
        static public function prevent_auto_updates( $caps, $cap ) {

            // Check for being enabled and look for specific cap requirements.
            if ( self::enabled() && in_array( $cap, array( 'install_plugins', 'install_themes', 'update_plugins', 'update_themes', 'update_core' ) ) ) {
                $caps[] = 'do_not_allow';
            }

            // Send back the data array.
            return $caps;
        }

        /**
         * Remove all the various places WP does the update checks. As you can see there are a lot of them.
         *
         * @return null
         */
        static public function remove_update_crons() {

            // Bail if disabled.
            if ( ! self::enabled() ) {
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
            add_action( 'init', function(){ remove_action('init','wp_version_check'); }, 2 );

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
        static public function remove_schedule_hook() {

            // Bail if disabled.
            if ( ! self::enabled() ) {
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
        static public function bypass_theme_api( $args, $action ) {

            // Bail if disabled.
            if ( ! self::enabled() ) {
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
        static public function last_checked_core() {

            // Bail if disabled.
            if ( ! self::enabled() ) {
                return false;
            }

            // Call the global WP version.
            global $wp_version;

            // Return our object.
            return (object) array(
                'last_checked'      => time(),
                'updates'           => array(),
                'version_checked'   => $wp_version,
            );
        }

        /**
         * Always send back that the latest version of our theme is the one we're running
         *
         * @return object     the modified output with our information
         */
        static public function last_checked_themes() {

            // Bail if disabled.
            if ( ! self::enabled() ) {
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
                'last_checked'      => time(),
                'updates'           => array(),
                'version_checked'   => $wp_version,
                'checked'           => $data,
            );
        }

        /**
         * Always send back that the latest version of our plugins are the one we're running
         *
         * @return object     the modified output with our information
         */
        static public function last_checked_plugins() {

            // Bail if disabled.
            if ( ! self::enabled() ) {
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
                'last_checked'      => time(),
                'updates'           => array(),
                'version_checked'   => $wp_version,
                'checked'           => $data,
            );
        }

        /**
         * Returns installed languages instead of all possibly available languages
         */
        static public function available_translations() {

            // include long predefined list of all available languages
            // It includes a function: core_blocker_get_languages()
            include( __DIR__ . '/lib/language-list.php' );
            $core_languges = core_blocker_get_languages();
            $installed = get_available_languages();

            // Call the global WP version.
            global $wp_version;

            // shared settings
            $date = date_i18n( 'Y-m-d H:is' , time() ); // eg. 2016-06-26 10:08:23

            $available = array();

            foreach ($installed as $lang) {

                // Try to mimick the data that wordpress puts into 'available_translations' transient
                $settings = array(
                    'language' => $lang,
                    'iso' => array( $lang ),
                    'version' => $wp_version,
                    'updated' => $date,
                    'strings' => array(
                        'continue' => __('Continue'),
                    ),
                    'package' => "https://downloads.wordpress.org/translation/core/{$wp_version}/{$lang}.zip"
                );

                $available[$lang] = array_merge( $settings, $core_languges[$lang] );
            }

            return $available;
        }

        /**
         * Return an empty array of items requiring update for both themes and plugins
         *
         * @param  array $items  All the items being passed for update.
         *
         * @return array         An empty array, or the original items if not enabled.
         */
        static public function remove_update_array( $items ) {
            return ! self::enabled() ? $items : array();
        }

        /**
         * Returns list of plugins which tells that there's no updates
         *
         * @param array $current    Empty array
         *
         * @return array            Lookalike data which is stored in site transient 'update_plugins'
         */
        static public function remove_plugin_updates( $current ) {
            if ( ! $current ) {
                $current = new stdClass;
                $current->last_checked = time();
                $current->translations = array();

                $plugins = get_plugins();
                foreach ( $plugins as $file => $p ) {
                    $current->checked[ $file ] = strval($p['Version']);
                }
                $current->response = array();
            }
            return $current;
        }

        /**
         * Remove the ability to update plugins/themes from single
         * site and multisite bulk actions
         *
         * @param  array $actions  All the bulk actions.
         *
         * @return array $actions  The remaining actions
         */
        static public function remove_bulk_actions( $actions ) {

            // Bail if disabled.
            if ( ! self::enabled() ) {
                return $actions;
            }

            // Set an array of items to be removed with optional filter.
            if ( false === $remove = apply_filters( 'core_blocker_bulk_items', array( 'update-selected', 'update', 'upgrade' ) ) ) {
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
        static public function plugin_add_tabs( $nonmenu_tabs ) {

            // Bail if disabled.
            if ( ! self::enabled() ) {
                return $nonmenu_tabs;
            }

            // Set an array of tabs to be removed with optional filter.
            if ( false === $remove = apply_filters( 'core_blocker_bulk_items', array( 'featured', 'popular', 'recommended', 'favorites', 'beta' ) ) ) {
                return $nonmenu_tabs;
            }

            // Loop the item array and unset each.
            foreach ( $remove as $key ) {
                unset( $nonmenu_tabs[ $key ] );
            }

            // Return the tabs.
            return $nonmenu_tabs;
        }

        // End class.
    }

} //end class_exists

if ( ! class_exists( __NAMESPACE__ . '\CoreBlocker_WP_Error' ) ) {

    class Core_Blocker_WP_Error extends WP_Error {

        public function __tostring() {
            $data = $this->get_error_data();
            return $data['return'];
        }

    }

}

// Instantiate our plugin.
Core_Blocker::init();
