# =============================================
# Absensi Digital - PHP + Nginx (Single Container)
# =============================================

FROM php:8.2-fpm-alpine

# Install dependensi PHP yang dibutuhkan
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        gd \
        mbstring \
        zip \
        intl \
    && rm -rf /var/cache/apk/*

# Konfigurasi PHP untuk produksi
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' /usr/local/etc/php/php.ini \
    && sed -i 's/post_max_size = 8M/post_max_size = 50M/' /usr/local/etc/php/php.ini \
    && sed -i 's/max_execution_time = 30/max_execution_time = 300/' /usr/local/etc/php/php.ini \
    && sed -i 's/memory_limit = 128M/memory_limit = 256M/' /usr/local/etc/php/php.ini

# Copy konfigurasi Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf
RUN rm -f /etc/nginx/http.d/default.conf.bak

# Copy konfigurasi Supervisor (menjalankan Nginx + PHP-FPM bersamaan)
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy semua source code aplikasi
WORKDIR /var/www/html
COPY . .

# Set permission folder uploads agar bisa ditulis
RUN mkdir -p uploads/foto uploads/logo uploads/temp \
    && chown -R nobody:nobody /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 uploads

# Buat folder Nginx yang dibutuhkan
RUN mkdir -p /run/nginx

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
