Airplane Mode
========================

## About
Control loading of external files when developing locally. WP loads certain external files (fonts, gravatar, etc) and makes external HTTP calls. This isn't usually an issue, unless you're working in an evironment without a web connection. This plugin removes / unhooks those actions to reduce load time and avoid errors due to missing files.

## Current Actions
* sets the src for Open Sans CSS font file to null due to dependency issues ( see [related Trac ticket](https://core.trac.wordpress.org/ticket/28478) )
* replaces all instances of Gravatar with a local image to remove external call
* removes all HTTP requests
* includes toggle in admin bar for quick enable / disable

## Roadmap
* fine tune HTTP request removal
* find other calls from core
* add other requests from popular plugins


### Pull Requests are very much welcome and encouraged