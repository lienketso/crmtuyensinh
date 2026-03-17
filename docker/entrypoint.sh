#!/usr/bin/env sh
set -e

# Khi bind-mount source từ host (VPS), quyền thư mục có thể không cho phép PHP/Composer tạo `vendor/`
# hoặc Laravel ghi vào `storage/`, `bootstrap/cache`.
#
# Lưu ý: không chạy php-fpm bằng user thường, vì php-fpm cần khởi tạo logging đúng cách.

APP_DIR="/var/www"

mkdir -p "$APP_DIR/vendor" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chown -R www-data:www-data "$APP_DIR/vendor" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

# đảm bảo các thư mục runtime ghi được
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

exec php-fpm

