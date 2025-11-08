#!/bin/bash

# Deployment script for AWS EC2
# Run this script on your EC2 instance

set -e

echo "Starting deployment..."

# Update system
sudo apt update
sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx php8.1-fpm php8.1-cli php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-sqlite3 php8.1-mysql composer nodejs npm git unzip

# Install Node.js 18 (LTS)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Create application directory
sudo mkdir -p /var/www/ai-job-recommendation
sudo chown -R $USER:$USER /var/www/ai-job-recommendation

echo "System setup complete!"
