#!/usr/bin/env bash

# Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved

# Be sure to log in as the Magento files owner and browse to Magento root directory
# Make this file executable with chmod +x install-facebook-business-extension.sh

echo "Starting Facebook Business Extension installation"

echo "Installing Facebook Business SDK..."
composer require facebook/php-business-sdk

echo "Enabling extension..."
php bin/magento module:enable Facebook_BusinessExtension

echo "Deploying static files..."
php bin/magento setup:static-content:deploy

echo "Installing component..."
php bin/magento setup:upgrade

echo "Compiling app..."
php bin/magento setup:di:compile

echo "Cleaning the cache..."
php bin/magento cache:clean

echo "Running cron job..."
php bin/magento cron:run
php bin/magento cron:run
php bin/magento cron:run

echo "Installation finished"
