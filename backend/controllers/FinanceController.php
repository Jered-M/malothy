<?php
/**
 * Contrôleur pour la gestion des finances
 */

require_once PROJECT_ROOT . '/controllers/BaseController.php';
require_once PROJECT_ROOT . '/models/Tithe.php';
require_once PROJECT_ROOT . '/models/Offering.php';
require_once PROJECT_ROOT . '/models/Member.php';

class FinanceController extends BaseController {
    private $titheModel;
    private $offeringModel;
    private $memberModel;

    public function __construct() {
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->memberModel = new Member();
    }

    /**
     * Dashboard financier
     */
    public function index() {
        $this->requireRole(ROLE_TREASURER);

        $year = $this->getQuery('year', date('Y'));
        $month = $this->getQuery('month', date('m'));

        $monthlyTithes = $this->titheModel->getMonthlyTotal($year, $month);
        $monthlyOfferings = $this->offeringModel->getMonthlyTotal($year, $month);
        $yearlyTithes = $this->titheModel->getYearlyTotal($year);
        $yearlyOfferings = $this->offeringModel->getYearlyTotal($year);

        $this->view('finance/index', [
            'year' => $year,
            'month' => $month,
            'monthlyTithes' => $monthlyTithes,
            'monthlyOfferings' => $monthlyOfferings,
            'yearlyTithes' => $yearlyTithes,
            'yearlyOfferings' => $yearlyOfferings
        ]);
    }

    /**
     * Liste des dîmes
     */
    public function tithes() {
        $this->requireRole(ROLE_TREASURER);

        $memberId = $this->getQuery('member_id', null);
        $startDate = $this->getQuery('start_date', null);
        $endDate = $this->getQuery('end_date', null);

        $tithes = $this->titheModel->search($memberId, $startDate, $endDate);
        $members = $this->memberModel->findAll('first_name ASC');

        $this->view('finance/tithes', [
            'tithes' => $tithes,
            'members' => $members,
            'memberId' => $memberId,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Ajouter une dîme
     */
    public function addTithe() {
        $this->requireRole(ROLE_TREASURER);

        $members = $this->memberModel->findAll('first_name ASC');
        $flash = $this->getFlash();

        $this->view('finance/add-tithe', [
            'members' => $members,
            'flash' => $flash
        ]);
    }

    /**
     * Traiter l'ajout de dîme
     */
    public function addTitheProcess() {
        $this->requireRole(ROLE_TREASURER);
        $this->validateRequest();

        $data = [
            'member_id' => $this->getPost('member_id'),
            'amount' => (float) $this->getPost('amount'),
            'tithe_date' => $this->getPost('tithe_date'),
            'comment' => $this->getPost('comment')
        ];

        // Valider
        $errors = $this->validate($data, [
            'member_id' => 'required|numeric',
            'amount' => 'required|numeric',
            'tithe_date' => 'required'
        ]);

        if (!empty($errors)) {
            $this->setFlash('Données invalides', 'error');
            $this->redirect('/index.php?controller=finance&action=addTithe');
        }

        $this->titheModel->recordTithe($data);
        $this->setFlash('Dîme enregistrée avec succès', 'success');
        $this->redirect('/index.php?controller=finance&action=tithes');
    }

    /**
     * Liste des offrandes
     */
    public function offerings() {
        $this->requireRole(ROLE_TREASURER);

        $type = $this->getQuery('type', null);
        $startDate = $this->getQuery('start_date', null);
        $endDate = $this->getQuery('end_date', null);

        $offerings = $this->offeringModel->search($type, $startDate, $endDate);

        $this->view('finance/offerings', [
            'offerings' => $offerings,
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Ajouter une offrande
     */
    public function addOffering() {
        $this->requireRole(ROLE_TREASURER);

        $flash = $this->getFlash();

        $this->view('finance/add-offering', [
            'offeringTypes' => OFFERING_TYPES,
            'flash' => $flash
        ]);
    }

    /**
     * Traiter l'ajout d'offrande
     */
    public function addOfferingProcess() {
        $this->requireRole(ROLE_TREASURER);
        $this->validateRequest();

        $data = [
            'type' => $this->getPost('type'),
            'amount' => (float) $this->getPost('amount'),
            'offering_date' => $this->getPost('offering_date'),
            'description' => $this->getPost('description')
        ];

        // Valider
        $errors = $this->validate($data, [
            'type' => 'required',
            'amount' => 'required|numeric',
            'offering_date' => 'required'
        ]);

        if (!empty($errors)) {
            $this->setFlash('Données invalides', 'error');
            $this->redirect('/index.php?controller=finance&action=addOffering');
        }

        $this->offeringModel->recordOffering($data);
        $this->setFlash('Offrande enregistrée avec succès', 'success');
        $this->redirect('/index.php?controller=finance&action=offerings');
    }
}
?>
