FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app
COPY . .

ENV APP_ENV=production

CMD php -S 0.0.0.0:$PORT public/index.php
