
=== Airplane Mode ===
Contributors: norcross, johnbillion, afragen, szepeviktor, chriscct7, markjaquith
Website Link: https://github.com/norcross/airplane-mode
Donate link: https://andrewnorcross.com/donate
Tags: external calls, HTTP
Requires at least: 3.7
Tested up to: 4.2
Stable tag: 0.0.8
License: MIT
License URI: http://opensource.org/licenses/mit-license.php

Control loading of external files when developing locally

== Description ==

Control loading of external files when developing locally. WP loads certain external files (fonts, gravatar, etc) and makes external HTTP calls. This isn't usually an issue, unless you're working in an evironment without a web connection. This plugin removes / unhooks those actions to reduce load time and avoid errors due to missing files.

Features

* sets the src for Open Sans CSS font file to null due to dependency issues ( see [related Trac ticket](https://core.trac.wordpress.org/ticket/28478) )
* replaces all instances of Gravatar with a local image to remove external call
* removes all HTTP requests
* includes toggle in admin bar for quick enable / disable

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `airplane-mode` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Toggle users as needed

== Frequently Asked Questions ==

= Why do I need this? =

Because you are a jet set developer who needs to work without internet.


== Screenshots ==


== Changelog ==

= 0.0.8 - 2015/05/18
* added `class_exists` as now included in DesktopServer and collisions could result
* fixed `if ( ! defined ...` for `AIRMDE_BASE` constant

= 0.0.7 - 2015/04/21 =
* fixed some CSS from hiding plugins page bar
* moved changelog to it's own file
* added `composer.json`
* added contributors to readme
* clarified license (MIT)

= 0.0.6 - 2015/04/02 =
* version bump for GitHub updater

= 0.0.5 - 2015/04/02 =
* fixed bug in update logic that was preventing checks when disabled (but activated). props @johnbillion

= 0.0.4 - 2015/04/02 =
* added multiple checks for all the various theme and plugin update calls. props @chriscct7
* added HTTP counter on a per-page basis. props @szepeviktor

= 0.0.3 - 2015/01/23 =
* added `airplane_mode_status_change` hook for functions to fire on status change
* purge update related transients on disable
* added WordPress formatted readme file

= 0.0.2 - 2015/01/21 =
* added GitHub Updater support
* fixed update capabilities when status is disabled

= 0.0.1 - 2014/06/01 =
* lots of stuff. I wasn't keeping a changelog. I apologize.


== Upgrade Notice ==

= 0.0.1 =
Initial release
