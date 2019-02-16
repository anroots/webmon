#!/usr/bin/env bash

cd src
touch .env

echo "Installing Composer dependencies..."
composer install --no-interaction


echo "Running unit tests..."
exec php vendor/bin/phpunit
