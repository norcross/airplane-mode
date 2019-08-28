#### Version 0.2.4 - 2019/08/28
* Fix the `__return_false` callback for the `themes_api` hook.

#### Version 0.2.3 - 2016/08/26
* Disable core from downloading the full language list in options-general.php page.

#### Version 0.2.2 - 2016/08/25
* Make removing gravatar an additional option trigger by `define('WP_CORE_BLOCKER_DISABLE_GRAVATAR',false)`

#### Version 0.2.1 - 2016/08/25
* Remove WordPress news dashboard widget and disabled installation of plugins

#### Version 0.2.0 - 2016/08/25
* Rewrite plugin to only block core connections and disabled installation of themes

#### Version 0.1.9 - 2016/07/25
* Prevent BuddyPress from falling back to Gravatar. props @johnbillion

#### Version 0.1.8 - 2016/07/12
* allow `JETPACK_DEV_DEBUG` constant to take priority over filter. props @kopepasah
* added additional CSS for upcoming 4.6. change to upload tab.

#### Version 0.1.7 - 2016/05/18
* allow local HTTP calls with optional filter. props @johnbillion
* add back index.php link to main dashboard menu item
* bumped minimum WP version requirement to 4.4

#### Version 0.1.6 - 2016/04/25
* minor tweak to include CSS for new icon font

#### Version 0.1.5 - 2016/04/24
* adding custom icon font for display and removing label. props @barryceelen

#### Version 0.1.4 - 2016/02/26
* better setup for blocked external assets. props @johnbillion

#### Version 0.1.3 - 2016/02/22
* modified CSS rules to fix media bulk actions bar from disappearing
* moved `airplane_mode_status_change` action to run before redirect, and now includes the status being run.

#### Version 0.1.2 - 2016/01/09
* added back HTTP count when inactive
* removed HTTP count completely when Query Monitor is active

#### Version 0.1.1 - 2016/01/06
* fixed incorrect nonce check that was breaking toggle
* changed CSS and JS checks to include all themes and plugins as well as core

#### Version 0.1.0 - 2015/12/30
* added `airplane_mode_purge_transients` filter to bypass transient purge

#### Version 0.0.9 - 2015/12/07
* changed from colored circle to actual airplane icon for usability
* fixed dashboard link icon for multisite
* changed to exclude all external stylesheets, not just Open Sans
* added language files for translateable goodness
* general cleanup for WP coding standards

#### Version 0.0.8 - 2015/05/18
* added `class_exists` as now included in DesktopServer and collisions could result
* fixed `if ( ! defined ...` for `AIRMDE_BASE` constant
* add `.gitattributes` to remove certain files from updates

#### Version 0.0.7 - 2015/04/21
* fixed some CSS from hiding plugins page bar
* moved changelog to it's own file
* added `composer.json`
* added contributors to readme
* clarified license (MIT)

#### Version 0.0.6 - 2015/04/02
* version bump for GitHub updater

#### Version 0.0.5 - 2015/04/02
* fixed bug in update logic that was preventing checks when disabled (but activated). props @johnbillion

#### Version 0.0.3 - 2015/01/23
* added `airplane_mode_status_change` hook for functions to fire on status change
* purge update related transients on disable
* added WordPress formatted readme file

#### Version 0.0.2 - 2015/01/21
* added GitHub Updater support
* fixed update capabilities when status is disabled

#### Version 0.0.1 - 2014/06/01
* lots of stuff. I wasn't keeping a changelog. I apologize.
