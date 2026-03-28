<?php
/**
 * Modèle pour la gestion des offrandes
 */

require_once __DIR__ . '/BaseModel.php';

class Offering extends BaseModel {
    protected $table = 'offerings';

    /**
     * Enregistrer une offrande
     */
    public function recordOffering($data) {
        $data['recorded_at'] = date('Y-m-d H:i:s');
        
        $offeringId = $this->insert($data);
        $this->logAction('CREATE', $offeringId, 'Offrande enregistrée');
        
        return $offeringId;
    }

    /**
     * Récupérer les offrandes avec filtre
     */
    public function search($type = null, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($startDate) {
            $sql .= " AND offering_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND offering_date <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY offering_date DESC";

        return $this->queryAll($sql, $params);
    }

    /**
     * Total des offrandes pour un mois
     */
    public function getMonthlyTotal($year, $month) {
        $result = $this->queryOne(
            "SELECT SUM(amount) as total FROM {$this->table} 
             WHERE EXTRACT(YEAR FROM offering_date) = ? AND EXTRACT(MONTH FROM offering_date) = ?",
            [$year, $month]
        );

        return $result['total'] ?? 0;
    }

    /**
     * Total des offrandes par type
     */
    public function getTotalByType($startDate = null, $endDate = null) {
        $sql = "SELECT type, SUM(amount) as total, COUNT(*) as count FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($startDate) {
            $sql .= " AND offering_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND offering_date <= ?";
            $params[] = $endDate;
        }

        $sql .= " GROUP BY type";

        return $this->queryAll($sql, $params);
    }

    /**
     * Récupérer le total annuel
     */
    public function getYearlyTotal($year) {
        $result = $this->queryOne(
            "SELECT SUM(amount) as total FROM {$this->table} WHERE EXTRACT(YEAR FROM offering_date) = ?",
            [$year]
        );

        return $result['total'] ?? 0;
    }
}
?>
