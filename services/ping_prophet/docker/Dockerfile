# Needs base_laravel with PHP >= 8.3.36 (CVE-2026-109618 / CVE-2026-109619).
FROM slnww/base_laravel:latest
COPY . /var/www/html
USER root
RUN composer install \
    && cp .env.example .env \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

WORKDIR /var/www/html
RUN npm install && npm run build

USER www-data
