#!/bin/bash
set -e

SERVER="root@178.20.41.51"
SERVER_PATH="/home/admin/web/compas.pro/public_html"

echo "=== 1. Pushing to GitHub ==="
git push

echo ""
echo "=== 2. Pulling on server ==="
ssh $SERVER "cd $SERVER_PATH && git pull"

echo ""
echo "=== 3. Composer install (если composer.lock менялся) ==="
ssh $SERVER "cd $SERVER_PATH && composer install --no-dev --optimize-autoloader --no-interaction"

echo ""
echo "=== 4. Очистка кешей ==="
ssh $SERVER "cd $SERVER_PATH && php artisan route:clear && php artisan config:clear && php artisan view:clear"

echo ""
echo "=== 5. Миграции (если есть новые) ==="
ssh $SERVER "cd $SERVER_PATH && php artisan migrate --force"

echo ""
echo "=== 6. Restart queue worker ==="
ssh $SERVER "cd $SERVER_PATH && php artisan queue:restart"

echo ""
echo "✅ Backend deploy complete!"
