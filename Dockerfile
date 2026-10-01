FROM dunglas/frankenphp:php8.4

# Set working directory
WORKDIR /app

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP extensions required by Laravel & MySQL
RUN install-php-extensions \
    pdo_mysql \
    pdo_sqlite \
    bcmath \
    gd \
    intl \
    zip \
    opcache

# Copy project files
COPY . .

# Ensure storage and bootstrap cache directories exist with correct permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    storage/app/private \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Install production dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Bind to Render's dynamic PORT or default 80
ENV SERVER_NAME=:${PORT:-80}
EXPOSE 80 10000

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
