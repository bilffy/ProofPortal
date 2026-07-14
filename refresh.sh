#!/bin/bash
echo "Clearing Laravel Caches..."
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear

echo "Done! Laravel caches cleared."
