<?php
/**
 * Contrôleur pour la gestion des dépenses
 */

require_once PROJECT_ROOT . '/controllers/BaseController.php';
require_once PROJECT_ROOT . '/models/Expense.php';

class ExpenseController extends BaseController {
    private $expenseModel;

    public function __construct() {
        $this->expenseModel = new Expense();
    }

    /**
     * Afficher la liste des dépenses
     */
    public function index() {
        $this->requireRole(ROLE_TREASURER);

        $category = $this->getQuery('category', null);
        $startDate = $this->getQuery('start_date', null);
        $endDate = $this->getQuery('end_date', null);
        $status = $this->getQuery('status', null);

        $expenses = $this->expenseModel->search($category, $startDate, $endDate, $status);
        $flash = $this->getFlash();

        $this->view('expenses/index', [
            'expenses' => $expenses,
            'categories' => EXPENSE_CATEGORIES,
            'category' => $category,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'flash' => $flash
        ]);
    }

    /**
     * Afficher le formulaire d'ajout
     */
    public function add() {
        $this->requireRole(ROLE_TREASURER);

        $flash = $this->getFlash();

        $this->view('expenses/form', [
            'expense' => null,
            'categories' => EXPENSE_CATEGORIES,
            'action' => 'add',
            'flash' => $flash
        ]);
    }

    /**
     * Traiter l'ajout d'une dépense
     */
    public function addProcess() {
        $this->requireRole(ROLE_TREASURER);
        $this->validateRequest();

        $data = [
            'category' => $this->getPost('category'),
            'amount' => (float) $this->getPost('amount'),
            'expense_date' => $this->getPost('expense_date'),
            'description' => $this->getPost('description'),
            'status' => 'en attente',
            'document_path' => null
        ];

        // Valider
        $errors = $this->validate($data, [
            'category' => 'required',
            'amount' => 'required|numeric',
            'expense_date' => 'required'
        ]);

        if (!empty($errors)) {
            $this->setFlash('Données invalides', 'error');
            $this->redirect('/index.php?controller=expense&action=add');
        }

        // Gérer l'upload du justificatif
        if (!empty($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $docPath = $this->uploadDocument($_FILES['document']);
            if ($docPath) {
                $data['document_path'] = $docPath;
            }
        }

        $this->expenseModel->recordExpense($data);
        $this->setFlash('Dépense enregistrée avec succès', 'success');
        $this->redirect('/index.php?controller=expense&action=index');
    }

    /**
     * Afficher les détails d'une dépense
     */
    public function view() {
        $this->requireRole(ROLE_TREASURER);

        $id = $this->getQuery('id');
        $expense = $this->expenseModel->findById($id);

        if (!$expense) {
            $this->setFlash('Dépense non trouvée', 'error');
            $this->redirect('/index.php?controller=expense&action=index');
        }

        $this->view('expenses/view', ['expense' => $expense]);
    }

    /**
     * Approuver une dépense
     */
    public function approve() {
        $this->requireRole(ROLE_ADMIN);
        $this->validateRequest();

        $id = $this->getPost('id');
        $this->expenseModel->updateStatus($id, 'approuvée');

        $this->setFlash('Dépense approuvée', 'success');
        $this->redirect('/index.php?controller=expense&action=index');
    }

    /**
     * Rejeter une dépense
     */
    public function reject() {
        $this->requireRole(ROLE_ADMIN);
        $this->validateRequest();

        $id = $this->getPost('id');
        $this->expenseModel->updateStatus($id, 'rejetée');

        $this->setFlash('Dépense rejetée', 'success');
        $this->redirect('/index.php?controller=expense&action=index');
    }

    /**
     * Upload un document justificatif
     */
    private function uploadDocument($file) {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxsize = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $maxsize) {
            return false;
        }

        if (!in_array($file['type'], $allowed)) {
            return false;
        }

        $uploadsDir = PROJECT_ROOT . '/uploads/expenses/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $filename = 'expense_' . time() . '_' . basename($file['name']);
        $filepath = $uploadsDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return '/uploads/expenses/' . $filename;
        }

        return false;
    }
}
?>
