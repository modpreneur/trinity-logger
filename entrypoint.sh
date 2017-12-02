#!/bin/sh sh

composer update

phpunit

phpstan analyse Annotation/ DependencyInjection/ Entity/ Event/ EventListener/ Interfaces/ Services/ --level=4

tail -f /dev/null