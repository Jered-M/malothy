<?php
/**
 * Modèle pour la gestion des membres
 */

require_once __DIR__ . '/BaseModel.php';

class Member extends BaseModel {
    protected $table = 'members';

    /**
     * Créer un nouveau membre
     */
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? STATUS_ACTIVE;
        
        $memberId = $this->insert($data);
        $this->logAction('CREATE', $memberId, 'Membre créé: ' . $data['first_name'] . ' ' . $data['last_name']);
        
        return $memberId;
    }

    /**
     * Récupérer les détails d'un membre
     */
    public function getMemberDetails($id) {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupérer tous les membres avec filtrage
     */
    public function search($searchTerm = '', $status = null, $department = null, $orderBy = 'created_at DESC') {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $term = "%{$searchTerm}%";
            $params = [$term, $term, $term, $term];
        }

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if ($department) {
            $sql .= " AND department = ?";
            $params[] = $department;
        }

        $sql .= " ORDER BY {$orderBy}";

        return $this->queryAll($sql, $params);
    }

    /**
     * Mettre à jour un membre
     */
    public function updateMember($id, $data) {
        $this->update($id, $data);
        $this->logAction('UPDATE', $id, 'Membre modifié');
        
        return true;
    }

    /**
     * Supprimer un membre
     */
    public function deleteMember($id) {
        $member = $this->getMemberDetails($id);
        $this->delete($id);
        $this->logAction('DELETE', $id, 'Membre supprimé: ' . $member['first_name'] . ' ' . $member['last_name']);
        
        return true;
    }

    /**
     * Obtenir le nombre de membres actifs
     */
    public function getActiveMembersCount() {
        return $this->count("status = '" . STATUS_ACTIVE . "'");
    }

    /**
     * Récupérer les départements
     */
    public function getDepartments() {
        $results = $this->queryAll("SELECT DISTINCT department FROM {$this->table} WHERE department IS NOT NULL ORDER BY department");
        return array_column($results, 'department');
    }

    /**
     * Récupérer le nom complet du membre
     */
    public function getFullName($id) {
        $member = $this->findById($id);
        return $member ? $member['first_name'] . ' ' . $member['last_name'] : 'Inconnu';
    }
}
?>
