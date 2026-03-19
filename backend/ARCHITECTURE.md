# Architecture MALOTY

## 📐 Architecture Générale

```
┌────────────────────────────────────────────────────────────────┐
│                          Client (Navigateur)                    │
│  HTML5 + CSS3 + JavaScript + Tailwind CSS + Chart.js            │
└─────────────────────────┬──────────────────────────────────────┘
                          │
                  HTTP Request/Response
                          │
┌─────────────────────────▼──────────────────────────────────────┐
│                   Couche Présentation (Web Server)               │
│                     Apache 2.4 + mod_rewrite                    │
└─────────────────────────┬──────────────────────────────────────┘
                          │
┌─────────────────────────▼──────────────────────────────────────┐
│                   Couche Application (PHP 7.4+)                 │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ index.php (Front Controller)                            │   │
│  └─────────────────┬───────────────────────────────────────┘   │
│                    │                                            │
│  ┌─────────────────▼───────────────────────────────────────┐   │
│  │ Routeur (Controller + Action)                           │   │
│  └─────────────────┬───────────────────────────────────────┘   │
│                    │                                            │
│  ┌─────────────────▼───────────────────────────────────────┐   │
│  │ Controllers/Contrôleurs                                 │   │
│  │ ├── AuthController    (Entrée/Authentification)         │   │
│  │ ├── DashboardController (Accueil/Statistiques)          │   │
│  │ ├── MemberController   (Gestion Membres)               │   │
│  │ ├── FinanceController  (Dîmes/Offrandes)               │   │
│  │ ├── ExpenseController  (Dépenses)                      │   │
│  │ └── BaseController     (Classe parente)                │   │
│  └─────────────────┬───────────────────────────────────────┘   │
│                    │                                            │
│  ┌─────────────────▼───────────────────────────────────────┐   │
│  │ Models/Modèles (Logique métier)                         │   │
│  │ ├── User        (Authentification & Autorisation)       │   │
│  │ ├── Member      (Gestion des Membres)                  │   │
│  │ ├── Tithe       (Enregistrement Dîmes)                 │   │
│  │ ├── Offering    (Enregistrement Offrandes)             │   │
│  │ ├── Expense     (Gestion Dépenses)                     │   │
│  │ └── BaseModel   (CRUD générique)                       │   │
│  └─────────────────┬───────────────────────────────────────┘   │
│                    │                                            │
│  ┌─────────────────▼───────────────────────────────────────┐   │
│  │ Views/Vues (Présentation)                               │   │
│  │ ├── layout.php      (Layout principal)                  │   │
│  │ ├── auth/           (Pages authentification)            │   │
│  │ ├── dashboard/      (Tableau de bord)                  │   │
│  │ ├── members/        (Gestion membres)                   │   │
│  │ ├── finance/        (Gestion finances)                  │   │
│  │ ├── expenses/       (Gestion dépenses)                  │   │
│  │ └── errors/         (Pages erreur)                      │   │
│  └─────────────────┬───────────────────────────────────────┘   │
│                    │                                            │
│  ┌─────────────────▼───────────────────────────────────────┐   │
│  │ Config & Services                                       │   │
│  │ ├── .env.php        (Configuration environnement)       │   │
│  │ ├── config.php      (Configuration générale)            │   │
│  │ └── database.php    (Singleton PDO)                    │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────┬──────────────────────────────────────┘
                          │
┌─────────────────────────▼──────────────────────────────────────┐
│              Couche Données (MySQL 5.7+/MariaDB 10.2+)          │
│                                                                  │
│  Database: eglise_m                                              │
│  Tables:                                                         │
│  ├── users         (Utilisateurs + Authentification)            │
│  ├── members       (Membres de l'église)                        │
│  ├── tithes        (Dîmes)                                      │
│  ├── offerings     (Offrandes)                                  │
│  ├── expenses      (Dépenses)                                   │
│  └── audit_logs    (Journalisation/Audit)                       │
└────────────────────────────────────────────────────────────────┘

                    Fichiers Statiques
                         │
        ┌────────────────┼────────────────┐
        │                │                │
    public/css/      public/js/       public/images/
    (Tailwind)       (JavaScript)      (Images app)
        │                │                │
    Tailwind CSS      Chart.js          Icônes FA
    (via CDN)      Font Awesome
                     (via CDN)
```

## 🔐 Couches de Sécurité

```
┌─────────────────────────────────────────┐
│      Validation Côté Serveur             │
│  (Type checking, Range checking)         │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│    Sanitisation des Entrées              │
│  (htmlspecialchars, trim, filter)        │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│   Requêtes Préparées (PDO)              │
│  (Protection SQL Injection)              │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│   Hachage des Mots de Passe             │
│  (password_hash, password_verify)        │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│   Contrôle d'Accès (RBAC)               │
│  (Vérification des rôles)                │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│   Journalisation (Audit Logs)           │
│  (Tracer chaque action)                  │
└─────────────────────────────────────────┘
```

## 📊 Flux de Données

### Exemple : Ajout d'un Membre

```
┌──────────────────────────────────────┐
│  Formulaire HTML                      │
│  (form#member-form)                   │
└─────────────┬────────────────────────┘
              │
              │ POST (form-data)
              │
┌─────────────▼────────────────────────┐
│  index.php                            │
│  Routage: controller=member&action=   │
│           addProcess                  │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  MemberController                     │
│  └─> addProcess()                     │
│      │ Validation                     │
│      │ Sanitisation                   │
│      │ Upload photo                   │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  Member Model (BaseModel)             │
│  └─> create($data)                    │
│      │ INSERT into database            │
│      │ Log action (audit_logs)         │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  PDO / MySQL                          │
│  └─> Execute query                    │
│      Return lastInsertId()             │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  Redirection & Flash Message          │
│  → /index.php?controller=member&      │
│    action=index&flash=success         │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  MemberController::index()            │
│  Récupérer liste des membres          │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  View: members/index.php              │
│  Afficher message flash               │
│  Afficher la liste mise à jour        │
└─────────────┬────────────────────────┘
              │
┌─────────────▼────────────────────────┐
│  HTML Response                        │
│  (avec CSS et JavaScript)              │
└──────────────────────────────────────┘
```

## 🗂️ Hiérarchie des Fichiers

```
maloty/
│
├── index.php                   # Point d'entrée principal
├── database.sql                # Schéma de base de données
├── .env.example.php            # Configuration exemple
├── .env.php                    # Configuration (ignorée par git)
├── .htaccess                   # Réécriture d'URL Apache
│
├── config/
│   ├── config.php              # Config générale + helpers
│   ├── database.php            # Connexion PDO (Singleton)
│   └── .env.php                # Variables d'environnement
│
├── controllers/
│   ├── BaseController.php      # Classe parente
│   ├── AuthController.php      # Authentification
│   ├── DashboardController.php # Dashboard
│   ├── MemberController.php    # Membres
│   ├── FinanceController.php   # Finances
│   └── ExpenseController.php   # Dépenses
│
├── models/
│   ├── BaseModel.php           # CRUD générique
│   ├── User.php                # Utilisateurs
│   ├── Member.php              # Membres
│   ├── Tithe.php               # Dîmes
│   ├── Offering.php            # Offrandes
│   └── Expense.php             # Dépenses
│
├── views/
│   ├── layout.php              # Layout principal
│   ├── auth/
│   │   ├── login.php           # Page login
│   │   └── change-password.php # Change password
│   ├── dashboard/
│   │   └── index.php           # Dashboard
│   ├── members/
│   │   ├── index.php           # Liste
│   │   ├── form.php            # Formulaire
│   │   └── view.php            # Détails
│   ├── finance/
│   │   ├── index.php           # Dashboard finance
│   │   ├── tithes.php          # Liste dîmes
│   │   ├── add-tithe.php       # Ajouter dîme
│   │   ├── offerings.php       # Liste offrandes
│   │   └── add-offering.php    # Ajouter offrande
│   ├── expenses/
│   │   ├── index.php           # Liste
│   │   ├── form.php            # Formulaire
│   │   └── view.php            # Détails
│   └── errors/
│       ├── 404.php             # Page 404
│       └── forbidden.php        # Page 403
│
├── public/
│   ├── css/
│   │   └── (custom.css)        # Styles personnalisés
│   ├── js/
│   │   └── main.js             # Scripts utilitaires
│   └── images/
│       └── (logo, icônes)      # Images de l'app
│
├── uploads/
│   ├── members/                # Photos des membres
│   └── expenses/               # Justificatifs
│
├── README.md                   # Documentation
├── INSTALL.md                  # Guide d'installation
├── DEVELOPMENT.md              # Guide de développement
├── API.md                       # Documentation API
└── CHANGELOG.md                # Historique des versions
```

## 🔗 Dépendances Internes

```
index.php
  ├─> config/config.php
  │    ├─> config/database.php
  │    └─> config/.env.php
  │
  └─> controllers/*/Controller.php
       ├─> BaseController
       │    └─> views/*
       │         └─> layout.php
       │
       └─> models/*/Model.php
            ├─> BaseModel
            │    └─> Database::getInstance()
            │
            └─> Database (Singleton PDO)
                 └─> MySQL Connection
```

## 🌐 Sessions et Authentification

```
┌─────────────────────────────────────┐
│  Session PHP                         │
│  $_SESSION = [                       │
│    'user_id' => 1,                   │
│    'user' => [                       │
│      'id' => 1,                      │
│      'name' => 'Admin',              │
│      'email' => 'admin@...',         │
│      'role' => 'admin'               │
│    ]                                 │
│  ]                                   │
└────────────────────────────────────┘

Vérification d'accès :
  ├─> isLoggedIn()      // true/false
  ├─> hasRole($role)    // Vérif rôle
  ├─> requireLogin()    // Redirection
  └─> requireRole($r)   // Accès contrôlé
```

## 📈 Scaling et Performances

### Optimisations Actuelles

- ✅ Requêtes préparées (PDO)
- ✅ Indexes MySQL optimisés
- ✅ Cache navigateur (.htaccess)
- ✅ Minification frontend (CDN)

### Améliorations Futures

- [ ] Cache Redis/Memcached
- [ ] CDN pour images
- [ ] Lazy loading
- [ ] Compression gzip
- [ ] API JSON (REST)

---

**Architecture MALOTY v1.0**
