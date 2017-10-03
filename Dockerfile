FROM php:7.1-alpine
ENV DEBIAN_FRONTEND noninteractive

RUN apk update && \
    apk upgrade && \
    apk add --update git unzip

# install composer
RUN cd \
  && curl -sS https://getcomposer.org/installer | php \
  && ln -s /root/composer.phar /usr/local/bin/composer

ADD . /code
WORKDIR /code

RUN composer install --no-interaction

CMD php ./src/app.php run /data
