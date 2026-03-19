# Guide d'Installation Détaillé - MALOTY

## 📋 Prérequis Système

### Serveur

- **PHP 7.4+** (testé sur PHP 8.0+)
- **MySQL 5.7+** ou **MariaDB 10.2+**
- **Apache 2.4+** ou **Nginx 1.18+**

### Extensions PHP requises

```bash
# Vérifier les extensions
php -m | grep -E "(pdo|mysql|json)"
```

Requis :

- `php-pdo` - PDO pour base de données
- `php-pdo-mysql` - Pilote MySQL PDO
- `php-json` - Support JSON
- `php-mbstring` - Caractères multi-octets

### Client

- Navigateur moderne (Chrome, Firefox, Edge, Safari)
- JavaScript activé
- Cookies activés

## 🔧 Installation Détaillée

### Étape 1 : Télécharger et installer

#### Windows (avec XAMPP)

```bash
# 1. Télécharger XAMPP depuis https://www.apachefriends.org
# 2. Installer dans C:\xampp
# 3. Lancer le control panel
# 4. Démarrer Apache et MySQL

# 5. Créer le dossier du projet
cd C:\xampp\htdocs
mkdir maloty-eglise
cd maloty-eglise

# 6. Copier les fichiers du projet ici
```

#### Linux (Ubuntu/Debian)

```bash
# Installer les paquets
sudo apt-get update
sudo apt-get install -y apache2 php php-mysql mysql-server

# Activer mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Créer le dossier
sudo mkdir -p /var/www/html/maloty
sudo chown -R $USER:$USER /var/www/html/maloty
cd /var/www/html/maloty

# Copier les fichiers du projet
```

#### macOS (avec Homebrew)

```bash
# Installer les dépendances
brew install php mysql

# Démarrer MySQL
brew services start mysql

# Créer le dossier
mkdir ~/Sites/maloty
cd ~/Sites/maloty

# Copier les fichiers du projet
```

### Étape 2 : Configurer PHP

**Fichier : `.env.php`**

Copier et modifier `config/.env.php` :

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'votre_mot_de_passe');
define('DB_NAME', 'eglise_m');
define('DB_PORT', 3306);

define('APP_NAME', 'MALOTY - Gestion d\'Église');
define('APP_URL', 'http://localhost/maloty');
define('APP_DEBUG', true);
define('SESSION_TIMEOUT', 3600);
```

### Étape 3 : Créer la Base de Données

#### Option A : Ligne de commande

```bash
# Se connecter à MySQL
mysql -u root -p

# Dans MySQL
CREATE DATABASE eglise_m CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eglise_m;
SOURCE /chemin/vers/database.sql;
```

#### Option B : phpMyAdmin (Windows/XAMPP)

```
1. Ouvrir http://localhost/phpmyadmin
2. Créer nouvelle base : "eglise_m"
3. Dans l'onglet SQL, copier le contenu de database.sql
4. Exécuter
```

#### Option C : Script d'installation

```bash
# Créer install.php (optionnel)
mysql -u root -p eglise_m < database.sql
```

### Étape 4 : Configurer les Permissions

```bash
# Linux/macOS
chmod 755 uploads/
chmod 755 uploads/members/
chmod 755 uploads/expenses/
chmod 644 config/.env.php

# Windows (via powershell en admin)
icacls uploads /grant Users:F /T
```

### Étape 5 : Configurer le Serveur Web

#### Apache (.htaccess)

Créer `/var/www/html/maloty/.htaccess` :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /maloty/

    # Rediriger les requêtes vers index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?$1 [QSA,L]
</IfModule>
```

#### Nginx

Configuration `/etc/nginx/sites-available/maloty` :

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/maloty;
    index index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## ✅ Vérification de l'Installation

### Test 1 : PHP

```bash
# Vérifier la version de PHP
php -v

# Sortie attendue : PHP 7.4.0+ ou supérieur
```

### Test 2 : MySQL

```bash
# Se connecter à MySQL
mysql -u root -p

# Vérifier la base de données
SHOW DATABASES;
USE eglise_m;
SHOW TABLES;
```

Sortie attendue :

```
+---
---+
| Tables_in_eglise_m |
+------
-----+
| audit_logs     |
| expenses       |
| members        |
| offerings      |
| tithes         |
| users          |
+------
-----+
```

### Test 3 : Application Web

```
1. Ouvrir http://localhost/maloty
   (ou http://localhost/index.php)

2. Vous devriez voir la page de connexion

3. Essayer de vous connecter avec :
   Email: admin@maloty.com
   Mot de passe: admin123

4. Cliquer sur "Connexion"
```

## 🚀 Déploiement en Production

### 1. Sécurité

```bash
# Désactiver le mode debug
define('APP_DEBUG', false);

# Modifier les mots de passe par défaut
UPDATE users SET password = PASSWORD('nouveau_mot_de_passe') WHERE id = 1;

# Configurer HTTPS
define('SESSION_COOKIE_SECURE', true);
define('SESSION_COOKIE_HTTPONLY', true);
```

### 2. Sauvegardes

```bash
# Créer une sauvegarde quotidienne
0 2 * * * mysqldump -u root -p eglise_m > /backup/maloty_$(date +\%Y\%m\%d).sql

# Sauvegarder les fichiers uploadés
rsync -av /var/www/html/maloty/uploads/ /backup/uploads/
```

### 3. Optimisation

```php
// config/.env.php
define('APP_DEBUG', false);
define('SESSION_TIMEOUT', 7200); // 2 heures

// Ajouter caching headers
header('Cache-Control: public, max-age=3600');
```

## 🐛 Dépannage

### Erreur : "Erreur de connexion à la base de données"

**Causes possibles :**

1. MySQL n'est pas démarré
2. Identifiants incorrects dans .env.php
3. Base de données non créée

**Solutions :**

```bash
# Vérifier MySQL
sudo service mysql status

# Redémarrer MySQL
sudo service mysql restart

# Vérifier les identifiants
mysql -u root -p -e "SELECT VERSION();"
```

### Erreur : "Permission denied" sur uploads/

**Solution :**

```bash
# Linux/macOS
sudo chown -R www-data:www-data /var/www/html/maloty/uploads
chmod 755 /var/www/html/maloty/uploads

# Windows : utiliser les permissions du dossier en GUI
```

### Erreur : "404 Not Found"

**Causes :**

1. .htaccess non activé
2. mod_rewrite non activé
3. Mauvais DocumentRoot

**Solutions :**

```bash
# Activer mod_rewrite (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2

# Vérifier DocumentRoot
apache2ctl -S

# Tester sans URL propres
http://localhost/maloty/index.php?controller=auth&action=login
```

### Erreur : "Classe non trouvée"

**Solution :**
Vérifier que PROJECT_ROOT est bien défini dans config/.env.php et que les fichiers existent.

## 📚 Ressources

- [Documentation PHP](https://www.php.net)
- [Documentation MySQL](https://dev.mysql.com/doc)
- [Tailwind CSS](https://tailwindcss.com)
- [Chart.js](https://www.chartjs.org)

## 📞 Support

Pour des problèmes :

1. Vérifier les logs PHP : `/var/log/php-fpm.log`
2. Vérifier les logs Apache : `/var/log/apache2/error.log`
3. Vérifier les logs MySQL : `/var/log/mysql/error.log`

## ✨ Prochaines Étapes

1. ✅ Créer la base de données
2. ✅ Configurer les fichiers d'environnement
3. ✅ Tester la connexion
4. ✅ Ajouter les premiers utilisateurs et membres
5. 📝 Configurer les sauvegardes automatiques
6. 🔐 Sécuriser l'application en production

---

**Installation réussie !** 🎉 Vous pouvez maintenant utiliser MALOTY.
