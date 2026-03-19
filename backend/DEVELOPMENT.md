# Guide de Développement - MALOTY

## 🏗️ Architecture MVC

### Model (Modèles)

Logique métier et accès aux données

```
models/
├── BaseModel.php     # Classe parent avec CRUD générique
├── User.php          # Gestion des utilisateurs
├── Member.php        # Gestion des membres
├── Tithe.php         # Gestion des dîmes
├── Offering.php      # Gestion des offrandes
└── Expense.php       # Gestion des dépenses
```

**Héritage :**

```php
class User extends BaseModel {
    protected $table = 'users';

    public function authenticate($email, $password) {
        // Logique métier
    }
}
```

### View (Vues)

Présentation et interaction utilisateur

```
views/
├── layout.php        # Layout principal
├── auth/             # Pages d'authentification
├── dashboard/        # Tableau de bord
├── members/          # Gestion des membres
├── finance/          # Gestion financière
├── expenses/         # Gestion des dépenses
└── errors/           # Pages d'erreur
```

**Rendu :**

```php
$this->view('members/index', [
    'members' => $members,
    'searchTerm' => $searchTerm
]);
```

### Controller (Contrôleurs)

Orchestration entre Model et View

```
controllers/
├── BaseController.php       # Classe parent
├── AuthController.php       # Authentification
├── DashboardController.php  # Dashboard
├── MemberController.php     # Membres
├── FinanceController.php    # Finances
└── ExpenseController.php    # Dépenses
```

**Pattern :**

```php
class MonController extends BaseController {
    public function action() {
        // Logique
        $data = $this->model->getData();
        // Rendu
        $this->view('template', $data);
    }
}
```

## 🔄 Flux de Requête

```
1. index.php
   ↓
2. Routage (controller=X&action=Y)
   ↓
3. BaseController::action()
   ↓
4. Model::method()
   ↓
5. DatabaseModel (PDO)
   ↓
6. BaseController::view()
   ↓
7. layout.php + view
```

## 📝 Conventions de Codage

### Nommage

| Type             | Convention | Exemple          |
| ---------------- | ---------- | ---------------- |
| Classes          | PascalCase | `UserController` |
| Functions        | camelCase  | `getUserData()`  |
| Constants        | UPPER_CASE | `ROLE_ADMIN`     |
| Variables        | camelCase  | `$userName`      |
| Files            | PascalCase | `UserModel.php`  |
| Database tables  | lowercase  | `users`          |
| Database columns | lowercase  | `user_id`        |

### Indentation

```php
// 4 espaces (pas de tabs)
function example() {
    if ($condition) {
        $result = doSomething();
    }
    return $result;
}
```

### Commentaires

```php
/**
 * Description courte
 *
 * @param string $param Description
 * @return bool Résultat
 */
public function method($param) {
    // Commentaire for court
}
```

## Adding New Features

### 1. Ajouter un Nouveau Modèle

Créer `models/MonModele.php` :

```php
<?php
require_once __DIR__ . '/BaseModel.php';

class MonModele extends BaseModel {
    protected $table = 'ma_table';

    public function methodePersonnalisee() {
        return $this->queryAll("SELECT * FROM {$this->table}");
    }
}
?>
```

Créer la table correspondante dans `database.sql` :

```sql
CREATE TABLE ma_table (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    created_at DATETIME,
    INDEX idx_nom (nom)
);
```

### 2. Ajouter un Nouveau Contrôleur

Créer `controllers/MonController.php` :

```php
<?php
require_once PROJECT_ROOT . '/controllers/BaseController.php';
require_once PROJECT_ROOT . '/models/MonModele.php';

class MonController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new MonModele();
    }

    public function index() {
        $this->requireLogin(); // Protéger l'accès

        $data = $this->model->findAll();
        $this->view('mon_module/index', ['data' => $data]);
    }

    public function add() {
        $this->view('mon_module/form', ['action' => 'add']);
    }

    public function addProcess() {
        $this->validateRequest(); // Vérifier POST

        $data = $this->getPostData();
        $this->model->insert($data);

        $this->setFlash('Ajouté avec succès', 'success');
        $this->redirect('/index.php?controller=mon&action=index');
    }
}
?>
```

### 3. Ajouter des Vues

Créer `views/mon_module/index.php` :

```php
<?php ob_start(); ?>

<div>
    <h1>Titre</h1>
    <!-- Contenu -->
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
```

### 4. Mettre à Jour le Routage

Le routage se fait automatiquement via :

```
?controller=mon&action=index
```

Les noms sont automatiquement transformés en noms de classes/fichiers.

## 🔒 Sécurité

### Validation

```php
$errors = $this->validate($data, [
    'email' => 'required|email',
    'password' => 'required|min:6',
    'amount' => 'required|numeric'
]);
```

### Sanitisation

```php
$safe = sanitize($userInput);
echo $safe; // XSS protection
```

### Requêtes Préparées

```php
// ✅ BON
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);

// ❌ MAUVAIS
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

### Contrôle d'Accès

```php
// Protéger par authentification
$this->requireLogin();

// Protéger par rôle
$this->requireRole(ROLE_ADMIN);

// Vérifier plusieurs rôles
if (!hasRoles([ROLE_ADMIN, ROLE_TREASURER])) {
    http_response_code(403);
}
```

## 🧪 Testing

### Test de Connexion (Manuel)

```php
// Tester les identifiants par défaut
Email: admin@maloty.com
Password: admin123
```

### Test de Modèles

```php
// Dans un script de test
<?php
require_once 'config/config.php';
require_once 'models/User.php';

$userModel = new User();
$user = $userModel->findById(1);
var_dump($user);
?>
```

### Vérifier les Logs

```bash
# Apache
tail -f /var/log/apache2/error.log

# MySQL
tail -f /var/log/mysql/error.log

# PHP
tail -f /var/log/php-fpm.log
```

## 🐛 Debugging

### Mode Debug

```php
// Dans config/.env.php
define('APP_DEBUG', true);
```

### Fonction de Dump

```php
dd($variable); // Dump et die
```

### Utiliser var_dump

```php
echo '<pre>';
var_dump($data);
echo '</pre>';
```

### Messages de Log

```php
error_log("Message de debug: " . json_encode($data));
```

## 📚 Bonnes Pratiques

### 1. DRY (Don't Repeat Yourself)

```php
// ❌ Mauvais
$user1 = $this->model->findById(1);
$user2 = $this->model->findById(2);

// ✅ Bon
foreach ([1, 2] as $id) {
    $user = $this->model->findById($id);
}
```

### 2. SOLID Principles

- Single Responsibility
- Open/Closed
- Liskov Substitution
- Interface Segregation
- Dependency Inversion

### 3. Code Comments

```php
// ✅ Bon commentaire
// Vérifier si l'utilisateur a le rôle requis
if (!hasRole($role)) {

// ❌ Mauvais commentaire
// Increment i
$i++;
```

### 4. Error Handling

```php
// ✅ Bon
try {
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    $this->setFlash("Erreur", "error");
}

// ❌ Mauvais
$stmt = $this->db->prepare($sql);
$stmt->execute($params); // Peut échouer silencieusement
```

## 🚀 Optimisations

### Requêtes Efficaces

```php
// ✅ Utiliser LIMIT
$members = $this->model->findAll('name ASC', 10);

// ✅ Utiliser des INDEX
SELECT * FROM members WHERE status = 'actif' AND department = ?

// ❌ Éviter SELECT *
SELECT id, name, email FROM users;
```

### Caching

```php
// Simple cache en session
if (!isset($_SESSION['cached_data'])) {
    $_SESSION['cached_data'] = $this->model->expensiveQuery();
}
return $_SESSION['cached_data'];
```

### Frontend Optimization

```html
<!-- Minifier CSS et JS -->
<script src="/public/js/main.min.js"></script>

<!-- Lazy load images -->
<img src="..." loading="lazy" />

<!-- Cache headers configurés via .htaccess -->
```

## 📦 Dépendances

Aucune dépendance externes pour le backend : PHP pur !

Frontend CDN :

- Tailwind CSS : CDN
- Chart.js : CDN
- Font Awesome : CDN

## 🔄 Workflow Git (Recommandé)

```bash
# Créer une branche feature
git checkout -b feature/nom-fonction

# Développer et tester
git add .
git commit -m "Ajouter nouvelle fonction"

# Fusionner dans main
git checkout main
git merge feature/nom-fonction
git push origin main
```

## 📞 Getting Help

1. Lire la documentation du code
2. Consulter README.md et API.md
3. Vérifier les logs (error.log)
4. Tenter des changements simples
5. Consulter la communauté

---

**Guide de Développement v1.0** - MALOTY
