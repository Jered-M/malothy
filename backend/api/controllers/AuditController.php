<?php
/**
 * API AuditController
 * Traceability and Operations History (Logs)
 */

class AuditController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/audit/logs
     */
    public function logs() {
        checkRole(['admin']);
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $sql = "SELECT l.*, u.name as user_name, u.role as user_role FROM audit_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $logs = $stmt->fetchAll();

        // Count total for pagination if needed
        $total = $this->db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

        json_response([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }
}
