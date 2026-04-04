<?php
/**
 * API ExpensesController
 * Handles Expenses and Digitized Justifications
 */

require_once PROJECT_ROOT . '/backend/models/Expense.php';

class ExpensesController {
    private $expenseModel;

    public function __construct() {
        $this->expenseModel = new Expense();
    }

    /**
     * GET /api/expenses
     */
    public function index() {
        checkRole(['admin', 'Trésorier']);
        
        $category = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $data = $this->expenseModel->search($category, $status, $startDate, $endDate);
        
        json_response([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * POST /api/expenses/create
     */
    public function create() {
        $user = checkRole(['admin', 'Trésorier']);
        $input = get_input();

        $required = ['category', 'amount', 'expense_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        $filePath = null;
        if (isset($_FILES['document_path']) && $_FILES['document_path']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document_path'];
            $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 5 * 1024 * 1024;

            if (!in_array($file['type'], $allowed)) {
                json_error('Format de justificatif non supporte (PDF, JPG, PNG, WEBP)', 400);
            }

            if ($file['size'] > $maxSize) {
                json_error('Justificatif trop volumineux (max 5MB)', 400);
            }

            $uploadsDir = PROJECT_ROOT . '/uploads/expenses/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $ext = preg_replace('/[^a-z0-9]+/i', '', $ext);
            if ($ext === '') {
                $ext = 'bin';
            }
            $fileName = 'expense_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadsDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = '/uploads/expenses/' . $fileName;
            }
        }

        $data = [
            'category' => $input['category'],
            'amount' => $input['amount'],
            'expense_date' => $input['expense_date'],
            'description' => $input['description'] ?? '',
            'document_path' => $filePath,
            'status' => 'en attente',
            'recorded_by' => $user['id'] ?? null
        ];

        $id = $this->expenseModel->insert($data);

        json_response([
            'success' => true,
            'message' => 'Dépense enregistrée avec succès',
            'id' => $id
        ], 201);
    }

    /**
     * PUT /api/expenses/:id/approve
     */
    public function approve($id) {
        $user = checkRole(['admin']);
        
        $result = $this->expenseModel->update($id, [
            'status' => 'approuvee',
            'approved_by' => $user['id'],
            'approval_date' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            json_response(['success' => true, 'message' => 'Dépense approuvée']);
        } else {
            json_error('Erreur lors de l\'approbation', 500);
        }
    }

    /**
     * PUT /api/expenses/:id/reject
     */
    public function reject($id) {
        checkRole(['admin']);
        
        $result = $this->expenseModel->updateStatus($id, 'rejetee');

        if ($result) {
            json_response(['success' => true, 'message' => 'Dépense rejetée']);
        } else {
            json_error('Erreur lors du rejet', 500);
        }
    }

    /**
     * GET /api/expenses/stats
     */
    public function stats() {
        checkRole(['admin', 'Trésorier']);
        
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');

        $total = $this->expenseModel->getMonthlyTotal($year, $month);
        $byCategory = $this->expenseModel->getTotalsByCategory($year, $month);

        json_response([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_category' => $byCategory
            ]
        ]);
    }
}
