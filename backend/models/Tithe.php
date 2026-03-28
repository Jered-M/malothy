<?php
/**
 * Modèle pour la gestion des dîmes
 */

require_once __DIR__ . '/BaseModel.php';

class Tithe extends BaseModel {
    protected $table = 'tithes';

    /**
     * Enregistrer une dîme
     */
    public function recordTithe($data) {
        $data['recorded_at'] = date('Y-m-d H:i:s');
        
        $titheId = $this->insert($data);
        $this->logAction('CREATE', $titheId, 'Dîme enregistrée');
        
        return $titheId;
    }

    /**
     * Récupérer les dîmes avec filtre
     */
    public function search($memberId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT t.*, m.first_name, m.last_name FROM {$this->table} t
                LEFT JOIN members m ON t.member_id = m.id
                WHERE 1=1";
        $params = [];

        if (!is_null($memberId)) {
            $sql .= " AND t.member_id = ?";
            $params[] = $memberId;
        }

        if ($startDate) {
            $sql .= " AND t.tithe_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND t.tithe_date <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY t.tithe_date DESC";

        return $this->queryAll($sql, $params);
    }

    /**
     * Total des dîmes pour un mois
     */
    public function getMonthlyTotal($year, $month) {
        $result = $this->queryOne(
            "SELECT SUM(amount) as total FROM {$this->table} 
             WHERE EXTRACT(YEAR FROM tithe_date) = ? AND EXTRACT(MONTH FROM tithe_date) = ?",
            [$year, $month]
        );

        return $result['total'] ?? 0;
    }

    /**
     * Total des dîmes par membre
     */
    public function getTithesByMember($memberId, $year = null) {
        $sql = "SELECT SUM(amount) as total, COUNT(*) as count FROM {$this->table} WHERE member_id = ?";
        $params = [$memberId];

        if ($year) {
            $sql .= " AND EXTRACT(YEAR FROM tithe_date) = ?";
            $params[] = $year;
        }

        $result = $this->queryOne($sql, $params);
        
        return [
            'total' => $result['total'] ?? 0,
            'count' => $result['count'] ?? 0
        ];
    }

    /**
     * Récupérer le total annuel
     */
    public function getYearlyTotal($year) {
        $result = $this->queryOne(
            "SELECT SUM(amount) as total FROM {$this->table} WHERE EXTRACT(YEAR FROM tithe_date) = ?",
            [$year]
        );

        return $result['total'] ?? 0;
    }
}
?>
