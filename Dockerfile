FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm \
    supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN NODE_ENV=development npm install --include=dev && NODE_ENV=development npm run build

RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY nginx.conf /etc/nginx/sites-enabled/default

RUN echo "[supervisord]" > /etc/supervisor/conf.d/supervisord.conf \
    && echo "nodaemon=true" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "[program:php-fpm]" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "command=php-fpm" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "autostart=true" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "autorestart=true" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "[program:nginx]" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "command=nginx -g 'daemon off;'" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "autostart=true" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "autorestart=true" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "[program:scheduler]" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "command=php /var/www/html/artisan schedule:work" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "user=www-data" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "autostart=%(ENV_RUN_SCHEDULER)s" >> /etc/supervisor/conf.d/supervisord.conf \
    && echo "autorestart=true" >> /etc/supervisor/conf.d/supervisord.conf

# Recurring invoices (06:00) and overdue reminders (09:00) run inside the
# container; set RUN_SCHEDULER=false when a separate scheduler runs them.
ENV RUN_SCHEDULER=true

EXPOSE 10000

COPY start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
