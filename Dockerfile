# Stage 1: Install Composer dependencies
FROM composer:2 AS vendor

WORKDIR /app

# Copy composer files
COPY . .

# Install dependencies
# RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts


# Stage 2: PHP-FPM + Nginx
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libzip-dev libonig-dev nginx \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy vendor from builder
COPY --from=vendor /app/vendor ./vendor

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy Nginx config
COPY ./nginx.conf /etc/nginx/sites-available/default

# Expose port 80
EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]