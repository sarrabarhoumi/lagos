# Utiliser une image officielle PHP avec Apache
FROM php:8.2-apache

# Copier le contenu du projet dans le dossier web du conteneur
COPY . /var/www/html


# Installer les extensions PHP nécessaires (ex : pdo_mysql)
RUN docker-php-ext-install pdo pdo_mysql

# Donner les bons droits
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80 (port web par défaut)
EXPOSE 80
