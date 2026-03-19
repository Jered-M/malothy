# Documentation API MALOTY

## Structure Backend/Frontend

```
backend/
├── api/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   └── MembersController.php
│   ├── index.php (API Router)
│   └── .htaccess

frontend/
├── index.html
└── public/
    └── js/
        ├── api.js (API Client)
        ├── app.js (Application SPA)
        └── pages.js (Page Templates)
```

## Endpoints API

### Authentification

```bash
# Login
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@maloty.com",
  "password": "admin123"
}

# Logout
POST /api/auth/logout

# Profile
GET /api/auth/profile
```

### Tableau de Bord

```bash
# Récupérer les statistiques
GET /api/dashboard?action=index
```

### Membres

```bash
# Lister tous les membres
GET /api/members?action=index

# Récupérer un membre
GET /api/members?action=show&id=1

# Créer un membre
POST /api/members?action=create
{
  "first_name": "Jean",
  "last_name": "Dupont",
  "email": "jean@email.com",
  "phone": "06 12 34 56 78",
  "address": "15 rue de la Paix",
  "department": "Ministère",
  "join_date": "2026-01-01"
}

# Mettre à jour un membre
PUT /api/members?action=update&id=1

# Supprimer un membre
DELETE /api/members?action=delete&id=1
```

## Frontend (SPA)

Accédez à : **`http://localhost/frontend/index.html`**

- **API Client** : `/frontend/public/js/api.js` - Communique avec le backend
- **Application** : `/frontend/public/js/app.js` - Gère la navigation
- **Pages** : `/frontend/public/js/pages.js` - Templates HTML

## Installation & Utilisation

### 1. Backend API

```bash
# L'API est accessible à :
http://localhost/api/auth/login
http://localhost/api/members?action=index
http://localhost/api/dashboard?action=index

# Nécessite CORS activé dans `/backend/api/index.php`
```

### 2. Frontend SPA

```bash
# Ouvrir dans le navigateur :
http://localhost/frontend/index.html

# Le frontend consomme l'API et affiche les données
```

## Notes

- La session PHP est utilisée pour l'authentification
- CORS est activé pour les requêtes cross-domain
- Les réponses sont au format JSON
- Les erreurs retournent des codes HTTP appropriés (401, 404, 500, etc.)

## Prochaines Étapes

- [ ] Créer les contrôleurs API pour Finance, Expenses
- [ ] Implémenter les pages Finance et Expenses dans la SPA
- [ ] Ajouter un système de tokens JWT
- [ ] Créer un système de logs d'API
- [ ] Documentation Swagger/OpenAPI
