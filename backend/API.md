# API Documentation - MALOTY

## Routes Disponibles

Toutes les routes suivent le pattern : `?controller=nom&action=nom`

### Authentification

| Route                        | Méthode | Description                   | Accès       |
| ---------------------------- | ------- | ----------------------------- | ----------- |
| `auth/login`                 | GET     | Afficher la page de connexion | Public      |
| `auth/loginProcess`          | POST    | Traiter la connexion          | Public      |
| `auth/logout`                | POST    | Déconnexion                   | Authentifié |
| `auth/changePassword`        | GET     | Formulaire de changement      | Authentifié |
| `auth/changePasswordProcess` | POST    | Changer le mot de passe       | Authentifié |
| `auth/forbidden`             | GET     | Page d'accès refusé           | Public      |
| `auth/notFound`              | GET     | Page 404                      | Public      |

### Tableau de Bord

| Route             | Méthode | Description               | Accès       |
| ----------------- | ------- | ------------------------- | ----------- |
| `dashboard/index` | GET     | Tableau de bord principal | Authentifié |

### Membres

| Route                | Méthode | Description               | Accès       |
| -------------------- | ------- | ------------------------- | ----------- |
| `member/index`       | GET     | Liste des membres         | Authentifié |
| `member/add`         | GET     | Formulaire d'ajout        | Authentifié |
| `member/addProcess`  | POST    | Créer un membre           | Authentifié |
| `member/view`        | GET     | Voir un membre (id)       | Authentifié |
| `member/edit`        | GET     | Formulaire d'édition (id) | Authentifié |
| `member/editProcess` | POST    | Mettre à jour un membre   | Authentifié |
| `member/delete`      | POST    | Supprimer un membre       | Admin       |

### Finances - Dîmes

| Route                     | Méthode | Description          | Accès           |
| ------------------------- | ------- | -------------------- | --------------- |
| `finance/index`           | GET     | Dashboard financier  | Trésorier/Admin |
| `finance/tithes`          | GET     | Liste des dîmes      | Trésorier/Admin |
| `finance/addTithe`        | GET     | Formulaire d'ajout   | Trésorier/Admin |
| `finance/addTitheProcess` | POST    | Enregistrer une dîme | Trésorier/Admin |

### Finances - Offrandes

| Route                        | Méthode | Description              | Accès           |
| ---------------------------- | ------- | ------------------------ | --------------- |
| `finance/offerings`          | GET     | Liste des offrandes      | Trésorier/Admin |
| `finance/addOffering`        | GET     | Formulaire d'ajout       | Trésorier/Admin |
| `finance/addOfferingProcess` | POST    | Enregistrer une offrande | Trésorier/Admin |

### Dépenses

| Route                | Méthode | Description           | Accès           |
| -------------------- | ------- | --------------------- | --------------- |
| `expense/index`      | GET     | Liste des dépenses    | Trésorier/Admin |
| `expense/add`        | GET     | Formulaire d'ajout    | Trésorier/Admin |
| `expense/addProcess` | POST    | Créer une dépense     | Trésorier/Admin |
| `expense/view`       | GET     | Voir une dépense (id) | Trésorier/Admin |
| `expense/approve`    | POST    | Approuver une dépense | Admin           |
| `expense/reject`     | POST    | Rejeter une dépense   | Admin           |

## Paramètres de Requête

### Filtrage (GET)

#### Membres

```
?controller=member&action=index&search=nom&status=actif&department=Musique
```

#### Dîmes

```
?controller=finance&action=tithes&member_id=1&start_date=2026-01-01&end_date=2026-03-31
```

#### Offrandes

```
?controller=finance&action=offerings&type=culte&start_date=2026-01-01&end_date=2026-03-31
```

#### Dépenses

```
?controller=expense&action=index&category=loyer&status=en attente&start_date=2026-01-01&end_date=2026-03-31
```

### Données POST

#### Connexion

```json
{
  "email": "admin@maloty.com",
  "password": "admin123"
}
```

#### Ajouter un Membre

```json
{
  "first_name": "Jean",
  "last_name": "Dupont",
  "email": "jean@example.com",
  "phone": "+33612345678",
  "address": "123 Rue de la Paix",
  "department": "Musique",
  "join_date": "2026-03-17",
  "photo": "[fichier]"
}
```

#### Enregistrer une Dîme

```json
{
  "member_id": "1",
  "amount": "50.00",
  "tithe_date": "2026-03-17",
  "comment": "Dîme mensuelle"
}
```

#### Enregistrer une Offrande

```json
{
  "type": "culte",
  "amount": "150.00",
  "offering_date": "2026-03-17",
  "description": "Offrande du culte"
}
```

#### Enregistrer une Dépense

```json
{
  "category": "loyer",
  "amount": "1200.00",
  "expense_date": "2026-03-17",
  "description": "Loyer du bâtiment",
  "document": "[fichier]"
}
```

## Réponses HTTP

### Codes de Statut

| Code | Signification          |
| ---- | ---------------------- |
| 200  | OK - Requête réussie   |
| 302  | Redirection (POST→GET) |
| 404  | Non trouvé             |
| 405  | Méthode non autorisée  |
| 500  | Erreur serveur         |

### Messages Flash

Les messages flash sont stockés en session et affichés une seule fois :

```php
// Success
$_SESSION['flash'] = [
    'message' => 'Opération réussie',
    'type' => 'success'
];

// Error
$_SESSION['flash'] = [
    'message' => 'Erreur lors de l\'opération',
    'type' => 'error'
];
```

## Modèles de Données

### User

```php
{
    id: int,
    name: string,
    email: string,
    password: string (bcrypt),
    role: enum('admin', 'trésorier', 'secrétaire'),
    status: enum('actif', 'inactif', 'suspendu'),
    last_login: datetime,
    created_at: datetime,
    updated_at: datetime
}
```

### Member

```php
{
    id: int,
    first_name: string,
    last_name: string,
    email: string,
    phone: string,
    address: string,
    department: string,
    join_date: date,
    photo: string (chemin),
    status: enum('actif', 'inactif', 'suspendu'),
    created_at: datetime,
    updated_at: datetime
}
```

### Tithe

```php
{
    id: int,
    member_id: int (FK),
    amount: decimal(10,2),
    tithe_date: date,
    comment: text,
    recorded_at: datetime,
    created_at: datetime
}
```

### Offering

```php
{
    id: int,
    type: enum('culte', 'evenement', 'mission', 'autre'),
    amount: decimal(10,2),
    offering_date: date,
    description: text,
    recorded_at: datetime,
    created_at: datetime
}
```

### Expense

```php
{
    id: int,
    category: enum('loyer', 'salaire', 'mission', 'entretien', 'communion', 'autre'),
    amount: decimal(10,2),
    expense_date: date,
    description: text,
    document_path: string,
    status: enum('en attente', 'approuvée', 'rejetée'),
    recorded_at: datetime,
    created_at: datetime,
    updated_at: datetime
}
```

### AuditLog

```php
{
    id: int,
    user_id: int (FK),
    action: string,
    table_name: string,
    record_id: int,
    details: text,
    ip_address: string,
    created_at: datetime
}
```

## Constantes Disponibles

### Rôles

```php
ROLE_ADMIN = 'admin'
ROLE_TREASURER = 'trésorier'
ROLE_SECRETARY = 'secrétaire'
```

### Statuts Membres

```php
STATUS_ACTIVE = 'actif'
STATUS_INACTIVE = 'inactif'
STATUS_SUSPENDED = 'suspendu'
```

### Catégories Dépenses

```php
EXPENSE_CATEGORIES = [
    'loyer' => 'Loyer',
    'salaire' => 'Salaires',
    'mission' => 'Missions',
    'entretien' => 'Entretien',
    'communion' => 'Article de communion',
    'autre' => 'Autre'
]
```

### Types Offrandes

```php
OFFERING_TYPES = [
    'culte' => 'Culte',
    'evenement' => 'Événement',
    'mission' => 'Mission',
    'autre' => 'Autre'
]
```

## Fonctions Utilitaires

### Configuration

- `isLoggedIn()` - Vérifier si l'utilisateur est connecté
- `getCurrentUser()` - Récupérer l'utilisateur courant
- `hasRole($role)` - Vérifier un rôle spécifique
- `hasRoles($roles)` - Vérifier plusieurs rôles
- `requireLogin()` - Protéger l'accès (redirection si non connecté)
- `requireRole($role)` - Protéger par rôle

### Sécurité

- `sanitize($string)` - Échapper pour XSS
- `password_hash($pwd)` - Hasher un mot de passe
- `password_verify($pwd, $hash)` - Vérifier un mot de passe

### Formatage

- `formatDate($date, $format)` - Formater une date
- `formatMoney($amount)` - Formater un montant en devise

### Navigation

- `redirect($url)` - Redirection HTTP
- `setFlash($message, $type)` - Définir un message flash
- `getFlash()` - Récupérer et nettoyer le message flash

## Exemples d'Utilisation

### Ajouter un Membre via Formulaire

```html
<form
  action="/index.php?controller=member&action=addProcess"
  method="POST"
  enctype="multipart/form-data"
>
  <input type="text" name="first_name" required />
  <input type="text" name="last_name" required />
  <input type="email" name="email" required />
  <input type="tel" name="phone" required />
  <input type="date" name="join_date" required />
  <input type="file" name="photo" accept="image/*" />
  <button type="submit">Ajouter</button>
</form>
```

### Récupérer les Statistiques Financières

```php
// Dans le contrôleur
$titheModel = new Tithe();
$offeringModel = new Offering();

$tithes = $titheModel->getMonthlyTotal(2026, 3); // Mars 2026
$offerings = $offeringModel->getMonthlyTotal(2026, 3);
$balance = $tithes + $offerings; // Revenus total
```

---

**Documentation API v1.0** - MALOTY
