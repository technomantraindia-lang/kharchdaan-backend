FROM php:8.4-cli-alpine

# Set working directory
WORKDIR /app

# Install system dependencies and PHP extensions required by Laravel
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
    pdo_mysql \
    pdo_sqlite \
    pdo_pgsql \
    pgsql \
    bcmath \
    gd \
    intl \
    zip \
    opcache

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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
    && chmod -R 777 storage bootstrap/cache

# Install composer production dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Copy entrypoint script and make executable
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh

# Expose Render dynamic port (default 10000)
EXPOSE 10000

CMD ["/usr/local/bin/docker-entrypoint.sh"]
