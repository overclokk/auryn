ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

# Old PHP images (7.1) are based on Debian releases moved to archive.debian.org.
RUN if grep -qE 'stretch|buster' /etc/os-release; then \
        sed -i -e 's|deb.debian.org|archive.debian.org|g' \
            -e 's|security.debian.org|archive.debian.org|g' \
            -e '/-updates/d' /etc/apt/sources.list; \
    fi

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /app

# Composer 2.2 LTS is the last line that still runs on PHP 7.1.
COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer

ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} appgroup && \
    useradd -u ${UID} -g appgroup -m appuser && \
    chown -R appuser:appgroup /app
USER appuser

CMD ["php", "-v"]