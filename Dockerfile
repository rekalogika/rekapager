# Use an official PHP runtime as a parent image
FROM php:8.3-apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/tests/public

# Set the working directory in the container
WORKDIR /var/www/html

# Set the Apache document root

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Install PHP extensions and other dependencies
RUN apt-get update && \
    apt-get install -y libicu-dev && \
    docker-php-ext-install intl

# Expose the port Apache listens on
EXPOSE 80

# Copy your PHP application code into the container
COPY . .

# Start Apache when the container runs
CMD ["apache2-foreground"]