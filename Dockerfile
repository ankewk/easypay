# EasyPay PHP环境
FROM php:5.6-fpm-alpine

WORKDIR /var/www/html

# 安装PHP扩展和依赖
RUN apk add --no-cache git curl libzip-dev && \
    docker-php-ext-install zip pdo_mysql

# 安装Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 复制并安装依赖
COPY . .
RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]