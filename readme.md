# FeatherBB Readme

[![Join the chat at https://gitter.im/featherbb/featherbb](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/featherbb/featherbb?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Total Downloads](https://poser.pugx.org/featherbb/featherbb/downloads)](https://packagist.org/packages/featherbb/featherbb)
[![License](https://poser.pugx.org/featherbb/featherbb/license)](https://packagist.org/packages/featherbb/featherbb)
[![Latest Stable Version](https://poser.pugx.org/featherbb/featherbb/v/stable)](https://packagist.org/packages/featherbb/featherbb)

## About

FeatherBB is an open source forum application released under the GNU General Public
Licence. It is free to download and use and will remain so. FeatherBB is a fork of
FluxBB 1.5 based on Slim Framework and designed to be simple and very lightweight,
with modern features: MVC architecture, PDO, OOP and a plugin system. Maybe more?
You are more than welcome to join the development :-)

## Changelog

This is __FeatherBB v1.0.0 Beta__. It is intended for testing purposes only and not
for use in a production environment. Please report all the bugs you may encounter to
the forums or in the GitHub bug tracker.

### Beta 5 (2019-05-05)
* API [[adaur](https://github.com/adaur)]
* New permission systen [[beaver](https://github.com/beaver-dev)]
* Switch parser to TextFormatter [[adaur](https://github.com/adaur)]
* Migrate templates to Twig [[adaur](https://github.com/adaur)]
* Use .env for configuration [[adaur](https://github.com/adaur)]

### Beta 4 (2016-02-21)
* Upgrade to Slim version 3 [[beaver](https://github.com/beaver-dev)] [[adaur](https://github.com/adaur)]
* More OOP goodness thanks to static classes (User::, Input::, Post::, ...) [[beaver](https://github.com/beaver-dev)]
* Download and install plugins right from the admin panel [[adaur](https://github.com/adaur)]
* Switch to Json Web Token for authentication [[beaver](https://github.com/beaver-dev)]
* Better installer [[beaver](https://github.com/beaver-dev)]
* Cleaner gettext code [[adaur](https://github.com/adaur)]

### Beta 3 (2015-09-09)
* Plugin system and some hooks [[beaver](https://github.com/beaver-dev)] [[adaur](https://github.com/adaur)]
* New architecture ready for Composer [[beaver](https://github.com/beaver-dev)]
* New template system [[capkokoon](https://github.com/capkokoon)] [[adaur](https://github.com/adaur)]
* Gettext implemented [[adaur](https://github.com/adaur)]
* Dynamic URLs within code [[beaver](https://github.com/beaver-dev)]
* Better permission handling [[adaur](https://github.com/adaur)]
* OOP parser [[adaur](https://github.com/adaur)]
* Major namespaces cleanup [[adaur](https://github.com/adaur)]
* New caching system [[capkokoon](https://github.com/capkokoon)]
* New error handler [[capkokoon](https://github.com/capkokoon)]
* New installer [[capkokoon](https://github.com/capkokoon)]
* Static functions files converted to OOP [[capkokoon](https://github.com/capkokoon)] [[beaver](https://github.com/beaver-dev)] [[adaur](https://github.com/adaur)]

### Beta 2 (2015-08-11)

* New DB Layer [[adaur](https://github.com/adaur)]
* Flash messages [[beaver](https://github.com/beaver-dev)]
* BBCode editor [[beaver](https://github.com/beaver-dev)]
* CSRF tokens [[capkokoon](https://github.com/capkokoon)]
* Cookie encryption improved [[capkokoon](https://github.com/capkokoon)]
* htaccess management improved [[adaur](https://github.com/adaur)]

### Beta 1  (2015-07-09)

* Integration with Slim Framework v2.6.2 [[adaur](https://github.com/adaur)]
* New parser [[ridgerunner](https://github.com/ridgerunner)]
* MVC architecture [[adaur](https://github.com/adaur)]
* URL rewriting [[adaur](https://github.com/adaur)]
* Routing system [[adaur](https://github.com/adaur)]
* Responsive default style [[Magicalex](https://github.com/Magicalex)]
* Database schema compatible with FluxBB [[adaur](https://github.com/adaur)]
* Antispam protection [[adaur](https://github.com/adaur)]
* Themes fully customizables [[adaur](https://github.com/adaur)]
* PHP 4 support dropped [[adaur](https://github.com/adaur)]
* PSR-2 compliant [[Magicalex](https://github.com/magicalex)]

## Requirements

* A webserver
* PHP 5.5.0 or later
* A database such as MySQL 4.1.2 or later, PostgreSQL 7.0 or later, SQLite 2 or later

## Recommendations

* Make use of a PHP accelerator such as OPCache
* Make sure PHP has the zlib module installed to allow FeatherBB to gzip output
* If you download directly from the repository, make sure to run a composer install
to setup the dependencies (Slim Framework and some more)

## Links

* Homepage: https://featherbb.org
* Documentation: https://featherbb.org/docs/
* Community: https://forums.featherbb.org/
* Chat: https://gitter.im/featherbb/featherbb
* Development: https://github.com/featherbb/featherbb

## Contributors

* [[adaur](https://github.com/adaur)] Project leader
* [[capkokoon](https://github.com/capkokoon)] contributor
* [[beaver](https://github.com/beaver-dev)] contributor
* [[Magicalex](https://github.com/magicalex)] contributor
