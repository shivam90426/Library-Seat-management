FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions mysqli pdo_mysql

WORKDIR /app

COPY . /app

RUN php -m | grep -E "mysqli|pdo_mysql"