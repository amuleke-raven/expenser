#!/bin/sh
set -e

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Discover packages (skipped during build to avoid missing .env errors)
php artisan package:discover --ansi

# Ensure SQLite database file exists
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    if [ ! -f "$DB_PATH" ]; then
        touch "$DB_PATH"
    fi
    chown www-data:www-data "$DB_PATH"
fi

# Run migrations
php artisan migrate --force

# Database seeding logic
echo "Seeding database..."
php artisan db:seed --force
# Ensure the public storage target exists (volume mount may shadow the image's directory)
mkdir -p /var/www/html/storage/app/public

# Create public storage symlink for file uploads
php artisan storage:link --force

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "Production caches built"
fi
#fix filament assets
php artisan livewire:publish --assets
# Fix permissions for storage and cache directories
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;

echo "Entrypoint setup complete - starting application..."

exec "$@"
