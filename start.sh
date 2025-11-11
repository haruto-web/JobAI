#!/bin/bash
set -e

echo "Starting application..."

# Clear caches
php artisan config:clear
php artisan cache:clear

# Start server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
