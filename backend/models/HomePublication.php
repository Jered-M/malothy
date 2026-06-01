<?php
require_once PROJECT_ROOT . '/backend/models/BaseModel.php';

class HomePublication extends BaseModel {
    protected $table = 'home_publications';

    public function findLatest($limit = null) {
        $sql = "SELECT title, period, description, image_url FROM {$this->table} ORDER BY sort_order ASC, published_at DESC";
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$limit]);
        } else {
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearAll() {
        return $this->db->exec("DELETE FROM {$this->table}") !== false;
    }
}
