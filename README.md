WP Local Dev Environment
========================

## About
Control loading of external files when developing locally. WP loads certain external files (fonts, gravatar, etc) and makes external HTTP calls. This isn't usually an issue, unless you're working in an evironment without a web connection. This plugin removes / unhooks those actions to reduce load time and avoid errors due to missing files.

## Current Actions
* replaces Open Sans CSS font file with a blank CSS file due to dependency issues [related Trac ticket](https://core.trac.wordpress.org/ticket/28478)
* replaces all instances of Gravatar with a local image to remove external call
* removes all HTTP requests

## Roadmap
* fine tune HTTP request removal
* find other calls from core
* add other requests from popular plugins
