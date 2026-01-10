#!/bin/bash

echo "=== Clearing all Laravel caches ==="

php artisan route:clear
echo "✓ Routes cleared"

php artisan view:clear
echo "✓ Views cleared"

php artisan config:clear
echo "✓ Config cleared"

php artisan cache:clear
echo "✓ Application cache cleared"

php artisan optimize:clear
echo "✓ All caches cleared"

echo ""
echo "=== Caching for production ==="

php artisan config:cache
echo "✓ Config cached"

php artisan route:cache
echo "✓ Routes cached"

php artisan view:cache
echo "✓ Views cached"

echo ""
echo "=== Route List ==="
php artisan route:list --name=customers.notes

echo ""
echo "✓ Done! All caches cleared and rebuilt."
