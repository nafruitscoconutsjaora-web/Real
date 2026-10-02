#!/usr/bin/env bash
set -e

# Ensure MariaDB is running
/etc/init.d/mariadb status >/dev/null 2>&1 || /etc/init.d/mariadb start

# Ensure database and user exist
mariadb -e "CREATE DATABASE IF NOT EXISTS game_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON game_platform.* TO 'game_user'@'localhost' IDENTIFIED BY 'GamePass123!#'; FLUSH PRIVILEGES;" >/dev/null 2>&1 || true

# Initialize database tables and seeds
php -f db_init.php

# Compile Tailwind CSS
npx @tailwindcss/cli -i assets/css/input.css -o assets/css/tailwind.css --minify || true

echo "Starting NexusGaming PHP Server on port 3000..."
exec php -S 0.0.0.0:3000 router.php
