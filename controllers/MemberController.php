<?php
/**
 * Contrôleur pour la gestion des membres
 */

require_once PROJECT_ROOT . '/controllers/BaseController.php';
require_once PROJECT_ROOT . '/models/Member.php';

class MemberController extends BaseController {
    private $memberModel;

    public function __construct() {
        $this->memberModel = new Member();
    }

    /**
     * Afficher la liste des membres
     */
    public function index() {
        $this->requireLogin();

        $searchTerm = $this->getQuery('search', '');
        $status = $this->getQuery('status', null);
        $department = $this->getQuery('department', null);

        $members = $this->memberModel->search($searchTerm, $status, $department);
        $departments = $this->memberModel->getDepartments();
        $flash = $this->getFlash();

        $this->view('members/index', [
            'members' => $members,
            'departments' => $departments,
            'searchTerm' => $searchTerm,
            'status' => $status,
            'department' => $department,
            'flash' => $flash
        ]);
    }

    /**
     * Afficher le formulaire d'ajout
     */
    public function add() {
        $this->requireLogin();

        $departments = $this->memberModel->getDepartments();
        $flash = $this->getFlash();

        $this->view('members/form', [
            'departments' => $departments,
            'member' => null,
            'action' => 'add',
            'flash' => $flash
        ]);
    }

    /**
     * Traiter l'ajout d'un membre
     */
    public function addProcess() {
        $this->requireLogin();
        $this->validateRequest();

        $data = [
            'first_name' => $this->getPost('first_name'),
            'last_name' => $this->getPost('last_name'),
            'email' => $this->getPost('email'),
            'phone' => $this->getPost('phone'),
            'address' => $this->getPost('address'),
            'department' => $this->getPost('department'),
            'join_date' => $this->getPost('join_date'),
            'photo' => null,
            'status' => 'actif'
        ];

        // Valider
        $errors = $this->validate($data, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'join_date' => 'required'
        ]);

        if (!empty($errors)) {
            $this->setFlash('Veuillez remplir correctement le formulaire', 'error');
            $this->redirect('/index.php?controller=member&action=add');
        }

        // Gérer l'upload de photo
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->uploadPhoto($_FILES['photo']);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
        }

        // Créer le membre
        $this->memberModel->create($data);

        $this->setFlash('Membre ajouté avec succès', 'success');
        $this->redirect('/index.php?controller=member&action=index');
    }

    /**
     * Afficher les détails d'un membre
     */
    public function details() {
        $this->requireLogin();

        $id = $this->getQuery('id');
        $member = $this->memberModel->getMemberDetails($id);

        if (!$member) {
            $this->setFlash('Membre non trouvé', 'error');
            $this->redirect('/index.php?controller=member&action=index');
        }

        $this->view('members/view', ['member' => $member]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit() {
        $this->requireLogin();

        $id = $this->getQuery('id');
        $member = $this->memberModel->getMemberDetails($id);

        if (!$member) {
            $this->setFlash('Membre non trouvé', 'error');
            $this->redirect('/index.php?controller=member&action=index');
        }

        $departments = $this->memberModel->getDepartments();

        $this->view('members/form', [
            'member' => $member,
            'departments' => $departments,
            'action' => 'edit'
        ]);
    }

    /**
     * Traiter l'édition d'un membre
     */
    public function editProcess() {
        $this->requireLogin();
        $this->validateRequest();

        $id = $this->getPost('id');
        $member = $this->memberModel->getMemberDetails($id);

        if (!$member) {
            $this->setFlash('Membre non trouvé', 'error');
            $this->redirect('/index.php?controller=member&action=index');
        }

        $data = [
            'first_name' => $this->getPost('first_name'),
            'last_name' => $this->getPost('last_name'),
            'email' => $this->getPost('email'),
            'phone' => $this->getPost('phone'),
            'address' => $this->getPost('address'),
            'department' => $this->getPost('department'),
            'join_date' => $this->getPost('join_date'),
            'status' => $this->getPost('status')
        ];

        // Gérer l'upload de photo
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->uploadPhoto($_FILES['photo']);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
        }

        // Mettre à jour
        $this->memberModel->updateMember($id, $data);

        $this->setFlash('Membre modifié avec succès', 'success');
        $this->redirect('/index.php?controller=member&action=index');
    }

    /**
     * Supprimer un membre
     */
    public function delete() {
        $this->requireRole(ROLE_ADMIN);
        $this->validateRequest();

        $id = $this->getPost('id');
        $this->memberModel->deleteMember($id);

        $this->setFlash('Membre supprimé avec succès', 'success');
        $this->redirect('/index.php?controller=member&action=index');
    }

    /**
     * Upload une photo
     */
    private function uploadPhoto($file) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $maxsize = 2 * 1024 * 1024; // 2MB

        if ($file['size'] > $maxsize) {
            return false;
        }

        if (!in_array($file['type'], $allowed)) {
            return false;
        }

        $uploadsDir = PROJECT_ROOT . '/uploads/members/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $filename = 'member_' . time() . '_' . basename($file['name']);
        $filepath = $uploadsDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return '/uploads/members/' . $filename;
        }

        return false;
    }
}
?>
