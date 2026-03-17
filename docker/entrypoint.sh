#!/usr/bin/env sh
set -e

# Khi bind-mount source từ host (VPS), quyền thư mục có thể không cho phép user trong container tạo `vendor/`.
# Chạy entrypoint với quyền root để tự sửa quyền rồi hạ xuống user `www`.

APP_DIR="/var/www"

mkdir -p "$APP_DIR/vendor" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chown -R www:www "$APP_DIR/vendor" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

# đảm bảo các thư mục runtime ghi được
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

exec su -s /bin/sh www -c "php-fpm"

