FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy backend files
COPY backend/ /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure Apache
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Start script
COPY <<EOF /start.sh
#!/bin/bash
sed -i "s/Listen 80/Listen \${PORT:-80}/g" /etc/apache2/ports.conf
sed -i "s/:80/:\${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf
php artisan config:cache
php artisan route:cache
php artisan migrate --force
apache2-foreground
EOF

RUN chmod +x /start.sh

CMD ["/start.sh"]
