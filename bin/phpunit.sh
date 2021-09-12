#!/usr/bin/env bash

export XDEBUG_MODE=off

composer dump-autoload
touch vendor/composer/InstalledVersions.php

php -n ./../../../vendor/bin/phpunit "$@"
