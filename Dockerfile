FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev default-mysql-client \
    curl gnupg \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libcurl4-openssl-dev libxml2-dev libicu-dev libonig-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # Extract source dulu sebelum apapun di-install
    && docker-php-source extract \
    # Salin header dom SEBELUM install (source masih ada)
    && cp -r /usr/src/php/ext/dom /usr/local/include/php/ext/ \
    # Install dom, xmlreader, xmlwriter sekaligus
    && docker-php-ext-install dom xmlreader xmlwriter \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql zip gd curl \
        mbstring bcmath intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN git config --global --add safe.directory '*'

RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN npm install && npm run build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache