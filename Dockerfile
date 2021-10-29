FROM php:8.0-cli-alpine

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk --no-cache update && apk --no-cache add bash git

COPY . /var/www/html
WORKDIR /var/www/html

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
	install-php-extensions @composer && \
	composer update --no-scripts && \
	composer install && \
	composer update

RUN wget https://get.symfony.com/cli/installer -O - | bash && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

CMD [ "php", "bin/console", "play", "--gameServer=http://volga-it-2021.ml", "--gameId=617c01b4e9df051bb57d2ef4",  "--playerId=1" ]
