#!/bin/bash

cd /var/www/html

# If artisan not found, assume Laravel not present
if [ ! -f artisan ]; then
    echo "Laravel not found. Creating Laravel in /tmp/laravel..."
    composer create-project --prefer-dist laravel/laravel /tmp/laravel

    echo "Copying Laravel to current directory..."
    cp -R /tmp/laravel/. .
    rm -rf /tmp/laravel

    echo "Setting correct permissions for the storage directory..."
    chown -R www-data:www-data /var/www/html/storage
    chmod -R 775 /var/www/html/storage
else
    echo "Laravel already exists. Skipping creation."
fi



exec "$@"
