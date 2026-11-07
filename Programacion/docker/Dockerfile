# Servidor web de SiGeRU: Apache con PHP 8 (como XAMPP, pero en un contenedor)
FROM php:8.2-apache

# Extension mysqli, para que PHP se pueda conectar a MySQL (la usa conexion.php)
RUN docker-php-ext-install mysqli

# Permite que funcionen los archivos .htaccess (bloquean el listado de las carpetas)
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Hora de Uruguay para PHP
RUN echo "date.timezone = America/Montevideo" > /usr/local/etc/php/conf.d/hora.ini

# Modulo headers: cabeceras de seguridad de los .htaccess (X-Frame-Options, nosniff...)
RUN a2enmod headers

# Carpeta para la clave de cifrado de los datos personales (sigeru_clave.php).
# Es un volumen aparte (ver docker-compose.yml), asi la clave no se pierde al recrear el contenedor.
RUN mkdir -p /var/www/claves && chown www-data:www-data /var/www/claves
