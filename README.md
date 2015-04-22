Airplane Mode
========================

## Contributors
* [Andrew Norcross](https://github.com/norcross)
* [John Blackbourn](https://github.com/johnbillion)
* [Andy Fragen](https://github.com/afragen)
* [Viktor Szépe](https://github.com/szepeviktor)
* [Chris Christoff](https://github.com/chriscct7)
* [Mark Jaquith](https://github.com/markjaquith)

## About
Control loading of external files when developing locally. WP loads certain external files (fonts, Gravatar, etc.) and makes external HTTP calls. This isn't usually an issue, unless you're working in an evironment without a web connection. This plugin removes/unhooks those actions to reduce load time and avoid errors due to missing files.

## Current Actions
* sets the source for the Open Sans CSS font file to null due to dependency issues (see [related Trac ticket](https://core.trac.wordpress.org/ticket/28478))
* replaces all instances of Gravatar with a local image to remove the external call
* removes all HTTP requests
* includes a toggle in the admin bar for quick enable/disable

## Changelog

See [CHANGES.md](CHANGES.md).

## Roadmap
* fine tune HTTP request removal
* find other calls from core
* add other requests from popular plugins

#### [Pull requests](https://github.com/norcross/airplane-mode/pulls) are very much welcome and encouraged.
