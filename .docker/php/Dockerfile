ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install zip \
    && pecl install pcov apcu \
    && docker-php-ext-enable pcov apcu \
    && echo 'apc.enable_cli=1' > /usr/local/etc/php/conf.d/apcu-cli.ini \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} appgroup && \
    useradd -u ${UID} -g appgroup -m appuser && \
    chown -R appuser:appgroup /app
USER appuser

CMD ["php", "-v"]