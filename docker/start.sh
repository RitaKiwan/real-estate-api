#!/usr/bin/env bash
set -e

cd /var/www/html

# Cache the config from the environment variables the host provides.
# (route:cache is NOT used: routes/api.php and routes/web.php contain closures.)
php artisan config:cache

# Create / update the database tables (safe to repeat, does nothing when up to date).
php artisan migrate --force

# Laravel must be able to write logs, cache and compiled views.
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
