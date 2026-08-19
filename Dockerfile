FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions mysqli pdo_mysql

WORKDIR /app

COPY . /app

RUN php -i | grep -i mysqli || true