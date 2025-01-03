# Use an official PHP runtime as a parent image
FROM php:8.4-apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/tests/public
ENV TERM=xterm-256color
ENV TTYD_VERSION=1.7.7

# Set the working directory in the container
WORKDIR /var/www/html

# Set the Apache document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install PHP extensions and other dependencies
RUN apt-get update && \
    apt-get install -y supervisor libicu-dev && \
    docker-php-ext-install intl && \
    docker-php-ext-install pcntl

# Enable Apache mod_rewrite
RUN a2enmod rewrite proxy proxy_wstunnel proxy_http

# install ttyd
RUN curl -fsSL https://github.com/tsl0922/ttyd/releases/download/1.7.7/ttyd.x86_64 -o /usr/local/bin/ttyd && \
    chmod +x /usr/local/bin/ttyd

# Expose the port Apache listens on
EXPOSE 80

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copy your PHP application code into the container
COPY . .

RUN mkdir -p /var/www/html/tests/var && chmod -R 777 /var/www/html/tests/var
RUN tests/bin/console importmap:install

# Start Apache when the container runs
CMD ["/usr/bin/supervisord"]