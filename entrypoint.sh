#!/bin/sh sh

composer update

phpunit

php vendor/bin/coveralls -v

#tail -f /dev/null