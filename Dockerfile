FROM php:8.2-apache

# 更新包管理器并安装系统依赖
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install mysqli pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 启用 Apache 的 mod_rewrite
RUN a2enmod rewrite

# 拷贝项目代码到 Apache 路径
COPY . /var/www/html/

# 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 开放端口
EXPOSE 80
