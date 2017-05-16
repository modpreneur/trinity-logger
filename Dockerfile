FROM modpreneur/trinity-test:0.3- sh -c 'php vendor/bin/coveralls -v'

MAINTAINER Martin Kolek <kolek@modpreneur.com>

WORKDIR /var/app

ENTRYPOINT ["fish", "entrypoint.sh"]