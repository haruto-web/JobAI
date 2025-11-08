#!/bin/bash

# Application setup script - Run after uploading code to EC2

set -e

APP_DIR="/var/www/ai-job-recommendation"

echo "Setting up Laravel Backend..."
cd $APP_DIR/backend

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Copy production environment file
cp .env.production .env

# Generate application key
php artisan key:generate

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Create SQLite database
touch database/database.sqlite
sudo chown www-data:www-data database/database.sqlite
sudo chmod 664 database/database.sqlite

# Run migrations
php artisan migrate --force

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Setting up React Frontend..."
cd $APP_DIR/frontend

# Install Node dependencies
npm install

# Copy production environment file
cp .env.production .env

# Build React app
npm run build

echo "Configuring Nginx..."
sudo cp $APP_DIR/nginx-config.conf /etc/nginx/sites-available/ai-job-recommendation
sudo ln -sf /etc/nginx/sites-available/ai-job-recommendation /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

echo "Deployment complete!"
echo "Your application should now be accessible at http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)"
