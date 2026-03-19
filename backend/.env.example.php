<?php
/**
 * Configuration d'environnement
 * Copier ce fichier en .env et configurer vos paramètres
 */

// Base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'eglise_m');
define('DB_PORT', 3306);

// Application
define('APP_NAME', 'MALOTY - Gestion d\'Église');
define('APP_URL', 'http://localhost');
define('APP_DEBUG', true);

// Sessions
define('SESSION_TIMEOUT', 3600);

// Uploads
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Email (optionnel) 
define('MAIL_FROM', 'noreply@maloty.local');
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 25);

// Sécurité
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SECURE', false); // À true en HTTPS

// Chemins
define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOADS_PATH', PROJECT_ROOT . '/uploads/');
define('UPLOADS_URL', '/uploads/');
