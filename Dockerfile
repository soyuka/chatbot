FROM php:7.4-cli-alpine

RUN apk add --no-cache --update git supervisor

RUN docker-php-ext-configure sockets && docker-php-ext-install sockets

COPY ./config/supervisord.conf /etc/supervisor/conf.d/chatbot.conf

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/chatbot

COPY ./docker/php/entrypoint.sh /opt/entrypoint.sh

RUN chmod +x /opt/entrypoint.sh

ENTRYPOINT ["/opt/entrypoint.sh"]
