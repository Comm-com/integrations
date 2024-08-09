#!/bin/bash

#not used in production.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd $SCRIPT_DIR && cd ../

cp .env.example .env
docker compose up -d ping-prophet-app
docker compose exec -T ping-prophet-app composer install --no-interaction
docker compose exec -T ping-prophet-app npm install

docker compose up -d
sudo find . -type d -exec chmod 775 {} \;
docker compose exec ping-prophet-app chmod -R 775 ./storage/logs
docker compose exec ping-prophet-app php artisan db:seed
docker compose exec ping-prophet-app php artisan route:clear
docker compose exec ping-prophet-app php artisan config:clear
docker compose exec ping-prophet-app php artisan cache:clear
docker compose logs
