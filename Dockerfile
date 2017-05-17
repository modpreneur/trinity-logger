FROM modpreneur/trinity-test:0.3

MAINTAINER Martin Kolek <kolek@modpreneur.com>

WORKDIR /var/app

ENTRYPOINT ["fish", "entrypoint.sh"]