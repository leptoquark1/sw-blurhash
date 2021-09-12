#!/usr/bin/env bash

export XDEBUG_MODE=coverage

composer dump-autoload
touch vendor/composer/InstalledVersions.php

./../../../vendor/bin/phpunit --configuration ./phpunit.xml.dist --coverage-html ./build/coverage "$@"
