# Stage 1: build assets + composer
FROM gazimazi/php:85-apache-node AS build
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci && npm run build

# Stage 2: production runtime
FROM gazimazi/php:85-apache
WORKDIR /var/www/html
COPY --from=build /app .
COPY docker/apache/laravel.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache database
EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
