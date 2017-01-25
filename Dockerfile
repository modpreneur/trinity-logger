FROM modpreneur/trinity-test:alpine

MAINTAINER Barbora Čápová <capova@modpreneur.com>

# Install app
ADD . /var/app

WORKDIR /var/app

RUN apk add --update \
    fish

RUN mkdir -p /root/.config/fish/functions

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.profiler_enable=0" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.profiler_output_dir=/var/app/var/xdebug/" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.profiler_enable_trigger=1" >> /usr/local/etc/php/php.ini \
    && echo "alias composer=\"php -n -d memory_limit=2048M -d extension=bcmath.so -d extension=zip.so /usr/bin/composer\"" >> /root/.config/fish/functions/composer.fish

RUN chmod +x entrypoint.sh

ENTRYPOINT ["sh", "entrypoint.sh"]