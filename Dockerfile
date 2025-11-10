FROM php:8.2-apache

# Installer mysqli et pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copier le code dans le conteneur
COPY ./ /var/www/html/

# Activer mod_rewrite pour Apache
RUN a2enmod rewrite

# Exposer le port 80
EXPOSE 80
