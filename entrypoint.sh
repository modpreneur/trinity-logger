#!/bin/sh sh

composer update

phpunit

sh -c 'php vendor/bin/coveralls -v'
#tail -f /dev/null