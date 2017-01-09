FROM php:7-cli

ENV DEBIAN_FRONTEND noninteractive

# Update system.
RUN apt-get update -q && \
    apt-get upgrade -qqy && \
    apt-get install -qqy \
            libevent-dev \
            libssl-dev \
            libmcrypt-dev \
            libbz2-dev \
            zlib1g-dev \
            locales \
            supervisor \
            git \
            wget

# Setup locale.
RUN dpkg-reconfigure locales && \
    locale-gen C.UTF-8 && \
    /usr/sbin/update-locale LANG=C.UTF-8

RUN echo 'en_US.UTF-8 UTF-8' >> /etc/locale.gen && \
    locale-gen

ENV LC_ALL C.UTF-8
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US.UTF-8

# Setup PHP extensions.
RUN docker-php-ext-install sockets mcrypt zip bz2
RUN pecl install event --with-event-libevent-dir=/usr
RUN pecl install mailparse

# Clean.
RUN apt-get clean
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /var/cache/*

# Install composer.
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '55d6ead61b29c7bdee5cccfb50076874187bd9f21f65d8991d46ec5cc90518f447387fb9f76ebae1fbbacf329e583e30') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/bin/composer

# Build project.
RUN mkdir /app /data
WORKDIR /app
COPY composer.json  /app/composer.json
COPY config/php.ini /usr/local/etc/php/php.ini
RUN composer update

COPY server.php /app/server.php
COPY src        /app/src
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

VOLUME /data

EXPOSE 25 9001

CMD ["/usr/bin/supervisord"]
