Airplane Mode
========================

Current Version: 0.0.4

## About
Control loading of external files when developing locally. WP loads certain external files (fonts, gravatar, etc) and makes external HTTP calls. This isn't usually an issue, unless you're working in an evironment without a web connection. This plugin removes / unhooks those actions to reduce load time and avoid errors due to missing files.

## Current Actions
* sets the src for Open Sans CSS font file to null due to dependency issues ( see [related Trac ticket](https://core.trac.wordpress.org/ticket/28478) )
* replaces all instances of Gravatar with a local image to remove external call
* removes all HTTP requests
* includes toggle in admin bar for quick enable / disable

## Changelog:

### Version 0.0.4 - 2015/04/02

* added multiple checks for all the various theme and plugin update calls. props @chriscct7
* added HTTP counter on a per-page basis. props @szepeviktor

### Version 0.0.3 - 2015/01/23

* added `airplane_mode_status_change` hook for functions to fire on status change
* purge update related transients on disable
* added WordPress formatted readme file

### Version 0.0.2 - 2015/01/21

* added GitHub Updater support
* fixed update capabilities when status is disabled

### Version 0.0.1 - 2014/06/01

* lots of stuff. I wasn't keeping a changelog. I apologize.

## Roadmap
* fine tune HTTP request removal
* find other calls from core
* add other requests from popular plugins


#### pull requests are very much welcome and encouraged