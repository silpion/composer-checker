[![Build Status](https://travis-ci.org/silpion/composer-checker.svg?branch=master)](https://travis-ci.org/silpion/composer-checker)

Composer Checker
======================

A simple tool for various composer related checks and validations.

Usage
-----

    $ php bin/composer-checker

    Available commands:
      help         Displays help for a command
      list         Lists commands
    check
      check:dist   Matching the dist urls in a composer.lock file against some patterns.
      check:src    Matching the src urls in a composer.lock file against some patterns.
    remove
      remove:dist   Removing dist urls from a composer.lock file.
      remove:src    Removing src urls from a composer.lock file.


Check: Dist-Urls
-------------------

This check is intended to validate the dist-urls in a composer.lock file.
When using a Satis Mirror for your packages, it might break your ci/deployment when external dist-urls are used in your composer.lock file.

Simply run this command to check against the url "satis.example.com":

    $ php bin/composer-checker check:dist -p "satis.example.com" composer.lock
     --- Invalid urls found ---
    +-----------------+-----------------------------------------------------------------------------------------------+
    | Package         | Dist-URL                                                                                      |
    +-----------------+-----------------------------------------------------------------------------------------------+
    | symfony/console | https://api.github.com/repos/symfony/Console/zipball/00848d3e13cf512e77c7498c2b3b0192f61f4b18 |
    +-----------------+-----------------------------------------------------------------------------------------------+

The output gives a hint, which packages do not comply with the given url pattern, which is basically just a regex.
A positive example with a more complex regex:

    $ php bin/composer-checker check:dist -p "^https://api.github.com/repos/(.+)/(.+)/zipball/([a-f0-9]+)$" composer.lock
    All urls valid.

It is also possible to enforce to use only "https" dist-urls with a pattern like this:

    $ php bin/composer-checker check:dist -p "^https://" composer.lock

Allowing empty or missing dist urls can be done with the `--allow-empty` switch.


Check: Source-Urls
---------------------

Parallel to the dist urls, the source urls can be checked too.

    $ php bin/composer-checker check:src -p "git@git.example.com/foo.git" composer.lock


Allowing empty or missing source urls can be done with the `--allow-empty` switch.


Remove: Dist-Urls
-------------------

This command will remove distribution urls from a given `composer.lock` file.
Forcing composer to install all packages from "source".

It is possible to `--except` specific patterns like "jquery.com". These urls will _not_ be removed.

    php bin/composer-checker remove:dist -e jquery.com composer.lock


Remove: Source-Urls
-------------------

Working the same as the `remove:dist` counterpart. Removing the "source" entries from a given `composer.lock` file.

    php bin/composer-checker remove:src -e jquery.com composer.lock

This command can be very useful for automated deploying.
Because if a package mirror like Satis, holding "dist" copies, is not available, composer will silently fail back to using "source" packages creating a unnoticed dependency between production and the VCS.
Removing all the "source" entries from a composer.lock file, will force composer to only use the "dist" urls or stop with a failure.


LICENSE
-------

The license can be found here: [LICENSE](LICENSE)
