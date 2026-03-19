# MALOTY - Gestion Administrative et Financière d'Église

Application web complète de gestion administrative et financière pour une église, développée en PHP pur, sans framework, avec architecture MVC.

## 🎯 Fonctionnalités Principales

### 🔐 Authentification et Autorisation

- Connexion sécurisée avec email/mot de passe
- Mots de passe hashés avec `password_hash()`
- 3 rôles d'utilisateurs : Administrateur, Trésorier, Secrétaire
- Contrôle d'accès basé sur les rôles

### 📊 Tableau de Bord

- Statistiques clés : nombre de membres, revenus/dépenses du mois
- Graphiques interactifs (Chart.js) : recettes vs dépenses
- Vue d'ensemble des transactions récentes
- Bilan annuel

### 👥 Gestion des Membres

- Ajout/modification/suppression de membres
- Champs : nom, prénom, email, téléphone, adresse, département, date d'adhésion
- Upload de photo (JPEG, PNG)
- Recherche et filtrage avancés
- Gestion des statuts (actif, inactif, suspendu)

### 💰 Gestion Financière

#### Dîmes

- Enregistrement des dîmes : montant, date, membre, commentaire
- Historique des transactions
- Filtrage par membre et période
- Statistiques mensuelles et annuelles

#### Offrandes

- Enregistrement : type (culte, événement, mission), montant, date
- Historique complet
- Filtrage par type et période
- Analyse des revenus par type

### 💸 Gestion des Dépenses

- Enregistrement avec catégories (loyer, salaire, mission, entretien...)
- Upload de justificatifs (PDF, Images, Documents)
- Workflow d'approbation : en attente → approuvée/rejetée
- Filtrage par catégorie, période et statut
- Statistiques par catégorie

### 🧾 Journalisation et Audit

- Enregistrement automatique de chaque action
- Utilisateur, action, date, adresse IP
- Traçabilité complète des modifications

## 🛠️ Stack Technique

- **Backend** : PHP 7.4+
- **Base de données** : MySQL 5.7+
- **Frontend** : HTML5, CSS3, JavaScript
- **Framework CSS** : Tailwind CSS
- **Graphiques** : Chart.js
- **Icônes** : Font Awesome 6

## 📁 Structure du Projet

```
MALOTY/
├── config/
│   ├── config.php          # Configuration générale et constantes
│   ├── database.php        # Connexion PDO à MySQL
│   └── .env.php            # Variables d'environnement
├── controllers/
│   ├── AuthController.php       # Authentification
│   ├── DashboardController.php  # Tableau de bord
│   ├── MemberController.php     # Gestion des membres
│   ├── FinanceController.php    # Gestion financière
│   ├── ExpenseController.php    # Gestion des dépenses
│   └── BaseController.php       # Classe de base
├── models/
│   ├── BaseModel.php       # Classe de base CRUD
│   ├── User.php            # Modèle utilisateur
│   ├── Member.php          # Modèle membre
│   ├── Tithe.php           # Modèle dîme
│   ├── Offering.php        # Modèle offrande
│   └── Expense.php         # Modèle dépense
├── views/
│   ├── layout.php          # Layout principal
│   ├── auth/               # Pages d'authentification
│   ├── dashboard/          # Pages du tableau de bord
│   ├── members/            # Pages de gestion des membres
│   ├── finance/            # Pages de gestion financière
│   ├── expenses/           # Pages de gestion des dépenses
│   └── errors/             # Pages d'erreur
├── public/
│   ├── css/                # Fichiers CSS
│   ├── js/
│   │   └── main.js         # JavaScript utilitaires
│   └── images/             # Images de l'application
├── uploads/                # Dossier pour les uploads
│   ├── members/            # Photos des membres
│   └── expenses/           # Justificatifs de dépenses
├── index.php               # Point d'entrée principal
├── database.sql            # Schéma de base de données
└── .env.example            # Exemple de configuration
```

## 🚀 Installation

### 1. Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web Apache (avec mod_rewrite si vous utilisez les URLs propres)

### 2. Configuration

**Étape 1 : Cloner ou télécharger le projet**

```bash
# Créer le dossier
mkdir maloty-eglise
cd maloty-eglise
```

**Étape 2 : Copier le fichier d'environnement**

```bash
cp .env.example .env
```

**Étape 3 : Configurer .env.php**
Modifier `config/.env.php` avec vos paramètres MySQL :

```php
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=maloty_eglise
```

**Étape 4 : Créer la base de données**

```bash
# Via MySQL CLI
mysql -u root -p < database.sql

# Ou via phpMyAdmin :
# 1. Créer une nouvelle base de données "maloty_eglise"
# 2. Importer le fichier database.sql
```

**Étape 5 : Configurer les permissions**

```bash
# Donner les permissions au dossier uploads
chmod 755 uploads/
chmod 755 uploads/members/
chmod 755 uploads/expenses/
```

### 3. Accès à l'application

Accédez à : `http://localhost/index.php?controller=auth&action=login`

### 4. Identifiants de démonstration

| Rôle           | Email                | Mot de passe |
| -------------- | -------------------- | ------------ |
| Administrateur | admin@maloty.com     | admin123     |
| Trésorier      | treasure@maloty.com  | treas123     |
| Secrétaire     | secretary@maloty.com | sec123       |

## 🔒 Sécurité

- ✅ Mots de passe hashés avec `password_hash()` et vérification avec `password_verify()`
- ✅ Protection XSS avec `htmlspecialchars()`
- ✅ Protection contre les injections SQL avec requêtes préparées (PDO)
- ✅ Sessions PHP sécurisées
- ✅ Validation et sanitisation des entrées utilisateur
- ✅ Vérification des fichiers uploadés (type, taille)
- ✅ Contrôle d'accès basé sur les rôles (RBAC)

## 📱 Responsive Design

L'application utilise Tailwind CSS pour assurer une interface responsive sur tous les appareils :

- Desktop : interface complète
- Tablet : mise en page adaptée
- Mobile : interface optimisée

## 📊 Graphiques et Statistiques

- Charts.js pour les graphiques interactifs
- Vue mensuelle des recettes vs dépenses
- Distribution des dépenses par catégorie
- Statistiques par type d'offrande

## 🔄 Flux de Travail des Dépenses

1. **Enregistrement** : Le trésorier enregistre une dépense avec justificatif
2. **Attente** : La dépense est "en attente" d'approbation
3. **Approbation** : L'administrateur approuve ou rejette
4. **Enregistrement** : Dépense approuvée est comptabilisée

## 💾 Export et Rapports

- Export CSV des listes
- Impression des rapports (fonction print du navigateur)
- Génération de PDF via impression navigateur

## 🛠️ Maintenance

### Sauvegarde

```bash
# Exporter la base de données
mysqldump -u root -p maloty_eglise > backup.sql
```

### Restauration

```bash
# Restaurer la base de données
mysql -u root -p maloty_eglise < backup.sql
```

## 🔧 Développement

### Architecture MVC

- **Model** : Logique métier et accès aux données
- **View** : Présentation (templates HTML)
- **Controller** : Orchestration entre Model et View

### Conventions de codage

- Noms de classes en PascalCase
- Noms de fonctions en camelCase
- Noms de constantes en UPPERCASE
- Indentation : 4 espaces

### Extension du projet

Pour ajouter une nouvelle fonctionnalité :

1. **Créer le modèle** : `models/MonModele.php`
2. **Créer le contrôleur** : `controllers/MonController.php`
3. **Créer les vues** : `views/mon_module/*.php`
4. **Ajouter les routes** : Utiliser le pattern GET dans index.php

## 📝 Logs et Audit

Chaque action est enregistrée dans `audit_logs` :

- Création, modification, suppression
- Identifiant utilisateur
- Adresse IP
- Timestamp

## 🎓 Utilisation Pédagogique

Ce projet peut servir de base pour :

- Apprentissage de PHP
- Étude d'architecture MVC
- Pratique de la sécurité web
- Démonstration d'une application complète
- Portfolio pour candidatures

## 📄 Licence

Ce projet est fourni à titre d'exemple éducatif.

## 🤝 Support

Pour des questions ou améliorations, consultez la documentation du code source bien commentée dans chaque fichier.

## 🎯 Améliorations Futures

- [ ] API REST pour mobile (Flutter/React Native)
- [ ] Système de rapports avancés (TCPDF)
- [ ] Intégration avec système de paiement
- [ ] Multi-langue (i18n)
- [ ] Cache Redis
- [ ] Tests unitaires (PHPUnit)
- [ ] Docker pour déploiement
- [ ] Panel admin avancé
- [ ] Notification par email
- [ ] Integration 2FA

---

**Développé avec ❤️ pour les églises**
