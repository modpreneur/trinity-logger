#!/bin/sh sh

composer update

phpunit

#tail -f /dev/null