#!/usr/bin/env bash

WORK_DIR="$(pwd)"
SW_DIR="$WORK_DIR/../../.."

export XDEBUG_MODE=off
composer dump-autoload -d "$WORK_DIR" --dev
touch "$WORK_DIR/vendor/composer/InstalledVersions.php"

php "$SW_DIR/vendor/bin/phpunit" -c "$WORK_DIR/phpunit.xml.dist" --bootstrap "$WORK_DIR/tests/TestBootstrap.php" "$@"
