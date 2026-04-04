<?php
/**
 * Classe de base pour tous les modèles
 */

abstract class BaseModel {
    protected $db;
    protected $table = '';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupérer tous les enregistrements
     */
    public function findAll($orderBy = 'id DESC', $limit = null) {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupérer par ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Compter les enregistrements
     */
    public function count($where = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Exécuter une requête personnalisée
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Récupérer tous les résultats d'une requête
     */
    public function queryAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Récupérer le premier résultat d'une requête
     */
    public function queryOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Insérer un enregistrement
     */
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute(array_values($data))) {
            $lastId = $this->db->lastInsertId();
            // Pour PostgreSQL, lastInsertId peut nécessiter une séquence, mais si on utilise SERIAL c'est souvent automatique.
            // On s'assure juste que si ça échoue, on continue quand même pour le log si recordId est nul.
            $this->logAction('CREATE', $lastId ?: null, 'Nouvel enregistrement créé');
            return $lastId;
        }
        return false;
    }

    /**
     * Mettre à jour un enregistrement
     */
    public function update($id, $data) {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET {$set} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute($values);
        if ($result) {
            $this->logAction('UPDATE', $id, 'Enregistrement mis à jour');
        }
        return $result;
    }

    /**
     * Supprimer un enregistrement
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id]);
        if ($result) {
            $this->logAction('DELETE', $id, 'Enregistrement supprimé');
        }
        return $result;
    }

    /**
     * Log une action
     */
    protected function logAction($action, $recordId = null, $details = '', $userId = null) {
        if (!$userId) {
            $sessionUser = getCurrentUser();
            if ($sessionUser && isset($sessionUser['id'])) {
                $userId = $sessionUser['id'];
            }
        }

        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $this->table,
            'record_id' => $recordId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute(array_values($logData));
        } catch (Exception $e) {
            // Log uniquement s'il existe une table audit_logs
        }
    }
}
?>
