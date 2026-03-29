<?php
/**
 * Modèle pour la gestion des utilisateurs
 */

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';

    /**
     * Créer un nouvel utilisateur
     */
    public function create($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $userId = $this->insert($data);
        $this->logAction('CREATE', $userId, 'Utilisateur créé');
        
        return $userId;
    }

    /**
     * Authentifier un utilisateur
     */
    public function authenticate($email, $password) {
        $email = trim(strtolower($email));
        $user = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE LOWER(TRIM(email)) = ? AND status = 'actif'",
            [$email]
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Mettre à jour la dernière connexion
        $this->query(
            "UPDATE {$this->table} SET last_login = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $user['id']]
        );

        // Log
        $this->logAction('LOGIN', $user['id'], 'Connexion réussie', $user['id']);

        return $user;
    }

    /**
     * Récupérer par email
     */
    public function findByEmail($email) {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($id, $data) {
        // Ne pas permettre la mise à jour du mot de passe ici
        unset($data['password']);
        
        $this->update($id, $data);
        $this->logAction('UPDATE', $id, 'Utilisateur modifié');
        
        return true;
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $this->update($id, ['password' => $hashedPassword]);
        $this->logAction('UPDATE', $id, 'Mot de passe modifié');
        
        return true;
    }

    /**
     * Récupérer tous les utilisateurs actifs
     */
    public function getAllActive() {
        return $this->queryAll(
            "SELECT * FROM {$this->table} WHERE status = 'actif' ORDER BY name ASC"
        );
    }
}
?>
