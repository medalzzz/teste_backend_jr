FROM php:7.4-apache

ENV APACHE_DOCUMENT_ROOT /var/www/source

# adds zip and unzip packages to extract composer
RUN apt-get update && \
   apt-get install -y \
   libzip-dev \
   zip \
   unzip \
   && docker-php-ext-install zip

# adds php docker extensions
RUN docker-php-ext-install zip

# enables apache htaccess rewrite rules
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# installs php composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# sets work directory to /var/www/source
WORKDIR /var/www/source

# adds composer.json dependencies and install it
COPY ./source/composer.json .
RUN composer install --no-scripts --no-autoloader

COPY ./source /var/www/source

# recreates autoload file
RUN composer dump-autoload -o