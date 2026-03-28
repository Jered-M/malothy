# Utiliser l'image officielle PHP avec Apache
FROM php:8.2-apache

# Installer les dépendances du système et les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql

# Activer le module de réécriture d'Apache (mod_rewrite)
RUN a2enmod rewrite

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Configurer les permissions pour Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port 80
EXPOSE 80

# Utiliser le fichier de config Apache par défaut ou personnalisé
CMD ["apache2-foreground"]
