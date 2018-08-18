FROM php:7.2.0-fpm-alpine
RUN apk update \
    && apk upgrade \
    && apk add libxml2-dev curl libmemcached-dev openssl-dev libmcrypt-dev \
    && pecl channel-update pecl.php.net \
    && docker-php-ext-install zip \
    && docker-php-ext-install tokenizer \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install bcmath

#-------------PHPUNIT----------------
ARG INSTALL_PHPUNIT=false
RUN if [ ${INSTALL_PHPUNIT} = true ]; then \
php -r "copy('https://phar.phpunit.de/phpunit.phar', 'phpunit.phar');" && \
    chmod +x phpunit.phar && \
    mv phpunit.phar /usr/local/bin/phpunit \
	;fi


#-------------XDEBUG----------------
ARG INSTALL_XDEBUG=false
RUN if [ ${INSTALL_XDEBUG} = true ]; then \
    apk add cmake autoconf build-base \
    && pecl install xdebug \
#    && pecl install xdebug-2.6.0alpha1 \ # if not work prev line
    && docker-php-ext-enable xdebug \
    ;fi

#-------------COMPOSER----------------
ARG INSTALL_COMPOSER=false
RUN if [ ${INSTALL_COMPOSER} = true ]; then \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
     && php composer-setup.php \
     && php -r "unlink('composer-setup.php');" \
     && mv composer.phar /usr/local/bin/composer \
    ;fi



ADD xdebug.ini /usr/local/etc/php/conf.d/
