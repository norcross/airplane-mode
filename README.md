![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)
# WP Plugin: WP Core Blocker
[![Build Status](https://travis-ci.org/devgeniem/wp-core-blocker.svg?branch=master)](https://travis-ci.org/devgeniem/wp-core-blocker) [![Latest Stable Version](https://poser.pugx.org/devgeniem/wp-core-blocker/v/stable)](https://packagist.org/packages/devgeniem/wp-core-blocker) [![Total Downloads](https://poser.pugx.org/devgeniem/wp-core-blocker/downloads)](https://packagist.org/packages/devgeniem/wp-core-blocker) [![Latest Unstable Version](https://poser.pugx.org/devgeniem/wp-core-blocker/v/unstable)](https://packagist.org/packages/devgeniem/wp-core-blocker) [![License](https://poser.pugx.org/devgeniem/wp-core-blocker/license)](https://packagist.org/packages/devgeniem/wp-core-blocker)

## History
This is fork from excellent work from [norcross/airplane-mode](https://github.com/norcross/airplane-mode/). We needed something to help local development and to disable all core update checks from production servers.

## Installation
Preferred installation way is with composer:
```
$ composer require devgeniem/wp-core-blocker
```

## About
This plugin is meant for composer driven sites. It helps you to force installing/updating stuff only through composer or cli. It also blocks WordPress core from connecting to wp.org servers to make site faster and not to fail under local development with poor internet connection.

## Current Actions
* disables installing plugins and themes
* disables admin dashboard WP news widget
* disables all WP update checks for core, translations, themes, and plugins
* `define('WP_CORE_BLOCKER_DISABLE_GRAVATAR',true)` replaces all instances of Gravatar with a local image to remove external call

## Changelog

See [CHANGES.md](CHANGES.md).

## Roadmap
* Disable all connections which cause errors without internet connections so that we can use whoops in local development without annoying errors.

## Credits
* [Andrew Norcross](https://github.com/norcross)
* [John Blackbourn](https://github.com/johnbillion)
* [Andy Fragen](https://github.com/afragen)
* [Viktor Szépe](https://github.com/szepeviktor)
* [Chris Christoff](https://github.com/chriscct7)
* [Mark Jaquith](https://github.com/markjaquith)

## License

[MIT](https://github.com/devgeniem/wp-core-blocker/blob/master/LICENSE)

## Contributing

[Pull requests](https://github.com/devgeniem/wp-core-blocker/pulls) are very much welcome and encouraged.

## Maintainers
[@onnimonni](https://github.com/onnimonni)

[@villepietarinen](https://github.com/villepietarinen)

