<?php
/**
 * Modèle pour la gestion des dépenses
 */

require_once __DIR__ . '/BaseModel.php';

class Expense extends BaseModel {
    protected $table = 'expenses';

    /**
     * Enregistrer une dépense
     */
    public function recordExpense($data) {
        $data['recorded_at'] = date('Y-m-d H:i:s');
        
        $expenseId = $this->insert($data);
        $this->logAction('CREATE', $expenseId, 'Dépense enregistrée: ' . $data['category']);
        
        return $expenseId;
    }

    /**
     * Récupérer les dépenses avec filtre
     */
    public function search($category = null, $startDate = null, $endDate = null, $status = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($startDate) {
            $sql .= " AND expense_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND expense_date <= ?";
            $params[] = $endDate;
        }

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY expense_date DESC";

        return $this->queryAll($sql, $params);
    }

    /**
     * Total des dépenses pour un mois
     */
    public function getMonthlyTotal($year, $month) {
        $result = $this->queryOne(
            "SELECT SUM(amount) as total FROM {$this->table} 
             WHERE EXTRACT(YEAR FROM expense_date) = ? AND EXTRACT(MONTH FROM expense_date) = ? AND status != 'rejetee'",
            [$year, $month]
        );

        return $result['total'] ?? 0;
    }

    /**
     * Total des dépenses par catégorie
     */
    public function getTotalByCategory($startDate = null, $endDate = null) {
        $sql = "SELECT category, SUM(amount) as total, COUNT(*) as count FROM {$this->table} 
                WHERE status != 'rejetee' AND 1=1";
        $params = [];

        if ($startDate) {
            $sql .= " AND expense_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND expense_date <= ?";
            $params[] = $endDate;
        }

        $sql .= " GROUP BY category ORDER BY total DESC";

        return $this->queryAll($sql, $params);
    }

    /**
     * Récupérer le total annuel
     */
    public function getYearlyTotal($year) {
        $result = $this->queryOne(
            "SELECT SUM(amount) as total FROM {$this->table} 
             WHERE EXTRACT(YEAR FROM expense_date) = ? AND status != 'rejetee'",
            [$year]
        );

        return $result['total'] ?? 0;
    }

    /**
     * Mettre à jour le statut
     */
    public function updateStatus($id, $status) {
        $this->update($id, ['status' => $status]);
        $this->logAction('UPDATE', $id, 'Statut de dépense modifié: ' . $status);
        
        return true;
    }

    /**
     * Récupérer les dépenses en attente
     */
    public function getPendingExpenses() {
        return $this->queryAll(
            "SELECT * FROM {$this->table} WHERE status = 'en attente' ORDER BY expense_date DESC"
        );
    }
}
?>
