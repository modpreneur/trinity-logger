#!/bin/sh sh

composer update

phpunit

phpstan analyse Annotation/ DependencyInjection/ Entity/ Event/ EventListener/ Interfaces/ Services/ Tests/ --level=4

#tail -f /dev/null