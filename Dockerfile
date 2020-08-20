FROM php:7.4-cli-alpine

RUN apk add --no-cache --update git supervisor zlib-dev acl

RUN docker-php-ext-configure sockets && docker-php-ext-install sockets pcntl

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/chatbot

COPY ./docker/php/entrypoint.sh /opt/entrypoint.sh

RUN chmod +x /opt/entrypoint.sh

COPY ./config/services.conf /etc/supervisor/conf.d/chatbot.conf

COPY ./config/supervisord.conf /etc/supervisord.conf

CMD [ "bin/console" , "app:twitch:run" ,  "-vvv"]
