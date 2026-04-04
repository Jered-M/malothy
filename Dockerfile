# Utiliser l'image officielle PHP avec Apache
FROM php:8.2-apache

# Installer les dépendances du système et les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql mbstring xml bcmath opcache

# Activer le module de réécriture d'Apache (mod_rewrite)
RUN a2enmod rewrite

# Configuration PHP pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configurer OpCache pour de meilleures performances
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Configurer les permissions pour Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Ajuster le port Apache dynamiquement pour Render (qui utilise $PORT)
# Par défaut 80 si non spécifié, mais Render injecte $PORT
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Exposer le port par défaut (Render le détecte via $PORT)
EXPOSE 80

# Démarrer Apache en arrière-plan
CMD ["apache2-foreground"]
