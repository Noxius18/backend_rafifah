# Gunakan PHP 8.3 FPM
FROM php:8.3-fpm

# Install dependensi sistem, ekstensi PHP, dan Node.js
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev default-mysql-client \
    curl gnupg \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libcurl4-openssl-dev libxml2-dev libicu-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql zip gd curl dom \
    && docker-php-ext-install -j$(nproc) \
        xmlreader xmlwriter mbstring bcmath intl


# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Install dependensi & Build frontend
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN npm install && npm run build

# Set permission agar FPM bisa menulis file
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache