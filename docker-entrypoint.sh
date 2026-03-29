#!/bin/sh
set -e

echo "🚀 Starting Laravel application..."

# Ensure proper permissions
echo "📁 Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Wait for database to be ready (optional, but helpful)
if [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    timeout=60
    elapsed=0
    until nc -z "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null || [ $elapsed -eq $timeout ]; do
        echo "Waiting for database... ($elapsed/$timeout)"
        sleep 2
        elapsed=$((elapsed + 2))
    done

    if [ $elapsed -eq $timeout ]; then
        echo "⚠️  Database connection timeout - continuing anyway..."
    else
        echo "✅ Database is ready!"
    fi
fi

# Run migrations (only in production with caution)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🔄 Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Cache configuration for better performance
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate JWT secret if not exists (for jwt-auth)
if ! php artisan jwt:secret --show 2>/dev/null; then
    echo "🔑 Generating JWT secret..."
    php artisan jwt:secret --force
fi

# Clear any previous caches
php artisan optimize:clear

# Final optimization
php artisan optimize

echo "✅ Application ready!"

# Execute the main command
exec "$@"
