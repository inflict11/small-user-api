FROM php:8-fpm-alpine3.12

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN set -ex && apk --update --no-cache add \
    $PHPIZE_DEPS \
    git \
    postgresql-dev \
    busybox-suid \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-source extract \
    # Clean-up
    && apk del ${PHPIZE_DEPS} \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/pear \
    && rm -rf /tmp/* /var/lib/apt/lists/* \
    && rm -rf /var/cache/apk/*

WORKDIR /app
EXPOSE 9000
