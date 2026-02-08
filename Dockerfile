# Stage 1: Build everything (PHP + Node)
FROM php:8.4-cli AS build

# Force rebuild - PostgreSQL support added
ARG BUILD_VERSION=2

# Install system dependencies and PostgreSQL client library
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (PostgreSQL for production)
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy application code
COPY . .

# Dump autoload
RUN composer dump-autoload --optimize

# Install Node dependencies and build frontend
ENV VITE_APP_NAME="Smart Documents"
RUN npm ci && npm run build


# Stage 2: Production image
FROM serversideup/php:8.4-fpm-nginx AS app

# Install intl extension
USER root
RUN install-php-extensions intl
USER www-data

WORKDIR /var/www/html

# Force cache invalidation for COPY
ARG CACHEBUST=1
RUN echo "Build: $CACHEBUST"

# Copy application code (bootstrap/cache excluded via .dockerignore)
COPY --chown=www-data:www-data . .

# Copy vendor from build stage
COPY --chown=www-data:www-data --from=build /app/vendor vendor

# Copy built frontend assets
COPY --chown=www-data:www-data --from=build /app/public/build public/build

# Copy generated wayfinder routes
COPY --chown=www-data:www-data --from=build /app/resources/js/actions resources/js/actions
COPY --chown=www-data:www-data --from=build /app/resources/js/routes resources/js/routes

# Create necessary directories
RUN mkdir -p \
    storage/app/documents \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
