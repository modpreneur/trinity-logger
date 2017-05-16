FROM modpreneur/trinity-test:0.2.1

RUN echo "xdebug.remote_host=192.168.121.56" >> /usr/local/etc/php/php.ini

MAINTAINER Martin Kolek <kolek@modpreneur.com>

WORKDIR /var/app

ENTRYPOINT ["fish", "entrypoint.sh"]