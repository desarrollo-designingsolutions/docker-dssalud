FROM php:8.3-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    mariadb-client \
    msmtp \
    perl \
    wget \
    procps \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev \
    netcat-openbsd \
    nano \
    libxslt1-dev \
    zip \
    unzip

# Instalar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) gd mysqli pdo_mysql intl bcmath opcache exif zip xsl pcntl

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Habilitar mod_rewrite para Laravel
RUN a2enmod rewrite

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar el código de la aplicación
COPY ./backend /var/www/html

# Configurar Apache para apuntar a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permisos para Laravel (usando el usuario www-data por defecto de la imagen)
RUN chown -R www-data:www-data /var/www/html

# Punto de entrada
COPY ./docker/entrypoint/php.entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/php.entrypoint.sh

ENTRYPOINT ["/usr/local/bin/php.entrypoint.sh"]
CMD ["apache2-foreground"]
