#!/bin/bash
set -xe
composer update
composer install

npm install
# Copy over testing configuration.
cp .env.gitlab .env
npm run dev
php artisan key:generate
php artisan config:cache
php artisan migrate
php artisan db:seed
