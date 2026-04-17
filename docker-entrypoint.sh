#!/bin/sh
set -e

echo "🚀 Starting Laravel application..."

echo "📁 Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 storage bootstrap/cache

# DB wait
if [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    timeout=60
    elapsed=0
    until nc -z "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null || [ $elapsed -eq $timeout ]; do
        echo "Waiting for database... ($elapsed/$timeout)"
        sleep 2
        elapsed=$((elapsed + 2))
    done
fi

echo "🔄 Running migrations..."
php artisan migrate --force --no-interaction

# echo "🌱 Running Super Admin Seeder..."
# php artisan db:seed --class=SuperAdminSeeder --force --no-interaction

echo "⚡ Clearing caches..."
php artisan optimize:clear

echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan storage:link || true
echo "🌐 Starting server..."
exec php artisan serve --host=0.0.0.0 --port=10000
php artisan queue:work --sleep=3 --tries=3