#!/bin/sh sh

composer install

phpunit

sh -c 'php vendor/bin/coveralls -v'

phpstan analyse Annotation/ DependencyInjection/ Entity/ Event/ EventListener/ Interfaces/ Services/ Tests/ --level=4

#tail -f /dev/null