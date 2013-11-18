
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

LICENSE
-------

The license can be found here: [LICENSE](LICENSE)