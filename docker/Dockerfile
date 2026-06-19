# Default linux/amd64 — typický VPS. Na Macu build: docker build --platform linux/amd64 ...
ARG TARGETPLATFORM=linux/amd64

# Stage 1: build assets + composer
FROM --platform=$TARGETPLATFORM gazimazi/php:85-apache-node AS build
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci && npm run build && rm -f public/hot

# Stage 2: production runtime
FROM --platform=$TARGETPLATFORM gazimazi/php:85-apache
WORKDIR /var/www/html
COPY --from=build /app .
COPY docker/apache/laravel.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache database
EXPOSE 80
ENTRYPOINT ["/bin/bash", "/entrypoint.sh"]
