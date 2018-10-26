# Router
:wrench: Customizable router factory

![Packagist](https://img.shields.io/packagist/dt/nepttune/sitemap.svg)
![Packagist](https://img.shields.io/packagist/v/nepttune/sitemap.svg)
[![CommitsSinceTag](https://img.shields.io/github/commits-since/nepttune/sitemap/v1.1.1.svg?maxAge=600)]()

[![Code Climate](https://codeclimate.com/github/nepttune/sitemap/badges/gpa.svg)](https://codeclimate.com/github/nepttune/sitemap)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nepttune/sitemap/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nepttune/sitemap/?branch=master)

## Introduction

Premade router factory with some basic module configuration.

## Dependencies

- [nepttune/base-requirements](https://github.com/nepttune/base-requirements)
- [hashids/hashids](https://github.com/ivanakimov/hashids.php)

## How to use

- Register `\Nepttune\RouterFactory` as service in cofiguration file. Dont forget to inject neon parameters.
- Create router by calling  `@routerFactory::createRouter` method.
- You can configure factory to create subdomain or standard router
  - Subdomain router uses following pattern `//<module>.%domain%/[<locale>/]<presenter>/<action>[/<id>]`.
  - Standard router uses following pattern `/[<locale>/]<module>/<presenter>/<action>[/<id>]`.
  - Standard router also craetes route without `<module>` section and in this case uses configured default module.
- Locale parameter is validated against `[a-z]{2}` pattern.
- Id parameter is validated agains `\d+` pattern - or hashed to string if `hashids` is set to true.

### Example configuration

```
services:
    routerFactory:
        class: Nepttune\RouterFactory
        arguments: 
            - %router%
    router: @routerFactory::createSubdomainRouter
    
parameters:
    router:
        subdomain: false
        apimodule: true
        defaultModule: 'Www'
        hashids: true
        hashidsSalt: 'x7royUaK0g'
        modules:
            admin: Admin
```

### Configuration Options

- `hashids` (bool) - whether router should ise Hashids to hide real id parameters
- `hashidsSalt` (string) - salt passed to hashids, make your hashes unique across multiple applications
- `hashidsCharset` (string) - charset passed to hashids, set of possible characters
- `hashidsPadding` (int) - padding passed to hashids, min length of hashes
- `subdomain` (bool) - whether router should make subdomain router
  
Following options apply only for standard router - when `subdomain` option is false.

- `defaultModule` (string) - name of default module, when none is provided in url
- `apimodule` (bool) - whether router should create simple api route without parameters - `/api/<presenter>/<action>`
- `modules` (associative array) - list of routes to create - eg. `admin: Admin` to create admin route in addition to generic route
