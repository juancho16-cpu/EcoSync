FROM php:8.2-apache
RUN docker-php-ext-install mysqli
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
RUN echo "date.timezone = America/Montevideo" > /usr/local/etc/php/conf.d/hora.ini
RUN a2enmod headers
RUN mkdir -p /var/www/claves && chown www-data:www-data /var/www/claves
