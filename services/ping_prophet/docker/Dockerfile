FROM slnww/base_laravel:latest
COPY . /var/www/html
USER root
RUN composer install \
    && cp .env.example .env \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

USER www-data
