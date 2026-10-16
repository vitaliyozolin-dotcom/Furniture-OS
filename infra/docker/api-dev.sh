#!/bin/sh
set -eu
cd /app/apps/api
composer install --no-interaction --prefer-dist
php bin/console doctrine:migrations:migrate --no-interaction
exec php -S 0.0.0.0:8000 -t public public/index.php
