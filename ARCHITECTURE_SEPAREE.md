# MALOTY - Architecture Backend/Frontend Séparée

## 📋 Résumé de la Séparation

Vous avez maintenant une **architecture complètement séparée** :

### ✅ Backend (`/backend`)

- PHP pure, aucun framework
- **API REST JSON** (`/backend/api/`)
- Base de données MySQL
- Modèles réutilisables
- Contrôleurs API

### ✅ Frontend (`/frontend`)

- **Single Page Application (SPA)** JavaScript
- Consomme l'API via fetch()
- HTML/CSS/TailwindCSS
- Sans framework (vanilla JavaScript)

## 🚀 Comment Utiliser

### 1. Accéder au Frontend SPA

```
http://localhost/frontend/index.html
```

### 2. Se Connecter

- **Email** : `admin@maloty.com`
- **Mot de passe** : `admin123`

### 3. Endpoints API Disponibles

```
POST   /api/auth/login
GET    /api/auth/profile
POST   /api/auth/logout
GET    /api/members?action=index
GET    /api/dashboard?action=index
```

## 📁 Structure de Fichiers

```
MALOTY/
├── backend/
│   ├── api/
│   │   ├── controllers/
│   │   │   ├── AuthController.php      ✅ New
│   │   │   ├── DashboardController.php ✅ New
│   │   │   └── MembersController.php   ✅ New
│   │   ├── index.php                   ✅ New (Router API)
│   │   └── .htaccess                   ✅ New
│   ├── config/
│   │   ├── api-config.php             ✅ New
│   │   ├── database.php
│   │   └── config.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Member.php
│   │   ├── Tithe.php
│   │   └── ...
│   └── ...
│
├── frontend/
│   ├── index.html                      ✅ New (SPA)
│   └── public/
│       └── js/
│           ├── api.js                  ✅ New (API Client)
│           ├── app.js                  ✅ New (App Router)
│           └── pages.js                ✅ New (Pages/Templates)
│
├── controllers/                        ← Garder pour l'app web classique
├── models/
├── views/
├── config/
├── database.sql
└── ...
```

## Avantages de cette Architecture

✅ **Frontend indépendant** - Peut être déployé sur un CDN, serveur Node.js, etc.
✅ **Backend API réutilisable** - Peut servir plusieurs clients (web, mobile, etc.)
✅ **CORS activé** - Permet les requêtes cross-domain
✅ **Format JSON** - Standard pour les APIs modernes
✅ **Séparation des préoccupations** - Code bien organisé et maintenable

## 🔄 Communication

**Frontend → Backend**

```
fetch('http://localhost/api/members?action=index')
  .then(r => r.json())
  .then(data => console.log(data))
```

**Backend → Frontend**

```json
{
  "success": true,
  "data": [...]
}
```

## ⚙️ Configuration CORS

Les headers CORS sont définis dans `/backend/api/index.php` :

- `Access-Control-Allow-Origin: *` (autoriser tous les domaines)
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE`
- `Access-Control-Allow-Headers: Content-Type, Authorization`

## 📝 Prochaines Étapes

1. ✅ Créer contrôleurs API pour Finance (`/api/finance`)
2. ✅ Créer contrôleur API pour Expenses (`/api/expenses`)
3. ✅ Implémenter les pages Finance et Expenses dans la SPA
4. ✅ Ajouter un système de tokens JWT pour plus de sécurité
5. ✅ Créer une documentation Swagger/OpenAPI

## 🛠️ Authentification

Sessions PHP + Cookies

```php
$_SESSION['user_id']
$_SESSION['user_role']
```

À l'avenir : JWT tokens pour la scalabilité

## 📞 Support API

Consultez : `API_DOCUMENTATION.md`

---

**Application prête !** 🎉

Frontend: http://localhost/frontend/index.html
API: http://localhost/api/
