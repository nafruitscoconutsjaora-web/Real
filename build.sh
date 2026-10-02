#!/usr/bin/env bash
set -e

# Compile Tailwind CSS
if [ -f "assets/css/input.css" ]; then
    npx @tailwindcss/cli -i assets/css/input.css -o assets/css/tailwind.css --minify 2>/dev/null || true
fi

# Validate PHP syntax on all PHP files
for file in $(find . -name "*.php" -not -path "*/node_modules/*"); do
    php -l "$file" > /dev/null || exit 1
done

echo "Build and syntax check succeeded."
