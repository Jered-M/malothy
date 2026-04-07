<?php
/**
 * API SettingsController
 * Handles application settings
 */

require_once PROJECT_ROOT . '/backend/config/mime.php';

class SettingsController {
    private $db;
    private $settingsColumns = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/settings
     */
    public function index() {
        checkRole(['admin']);

        $selectFields = ['setting_key', 'setting_value'];
        if ($this->hasSettingsColumn('description')) {
            $selectFields[] = 'description';
        }

        $stmt = $this->db->query('SELECT ' . implode(', ', $selectFields) . ' FROM settings');
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($settings as $setting) {
            $data[$setting['setting_key']] = $setting['setting_value'];
        }

        json_response([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * POST /api/settings
     */
    public function create() {
        checkRole(['admin']);
        $input = get_input();

        if (empty($input) || !is_array($input)) {
            json_error('Aucune donnee fournie', 400);
        }

        $this->db->beginTransaction();

        try {
            foreach ($input as $key => $value) {
                $valueString = is_string($value)
                    ? $value
                    : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->saveSetting((string)$key, $valueString);
            }

            $this->db->commit();

            json_response([
                'success' => true,
                'message' => 'Parametres enregistres avec succes'
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            json_error('Erreur SQL: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET|POST /api/settings/home-events
     */
    public function home_events() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            json_response([
                'success' => true,
                'data' => $this->getHomeEventsSetting()
            ]);
        }

        checkRole(['admin']);
        $input = get_input();
        $events = $input['events'] ?? [];

        if (!is_array($events)) {
            json_error('Le champ events doit etre une liste', 400);
        }

        $normalized = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $title = trim(strip_tags((string)($event['title'] ?? '')));
            $period = trim(strip_tags((string)($event['period'] ?? '')));
            $description = trim(strip_tags((string)($event['description'] ?? '')));
            $imageUrl = $this->normalizeHomeEventImageUrl((string)($event['image_url'] ?? ''));

            if ($title === '' || $period === '' || $description === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'period' => $period,
                'description' => $description,
                'image_url' => $imageUrl
            ];
        }

        $normalized = array_slice($normalized, 0, 6);

        $this->saveSetting(
            'homepage_events',
            json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Evenements publies sur la page d accueil'
        );

        json_response([
            'success' => true,
            'message' => 'Agenda publie avec succes',
            'data' => $normalized
        ]);
    }

    /**
     * POST /api/settings/home-event-image
     */
    public function home_event_image() {
        checkRole(['admin']);

        if (empty($_FILES['image'])) {
            json_error('Aucune image fournie', 400);
        }

        $file = $_FILES['image'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            json_error('Erreur lors de l upload de l image', 400);
        }

        $tmpFile = $file['tmp_name'] ?? '';
        if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
            json_error('Fichier upload invalide', 400);
        }

        $mimeType = detect_mime_type($tmpFile, '');
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif'
        ];

        if (!isset($allowedTypes[$mimeType])) {
            json_error('Format non supporte. Utilisez JPG, PNG, WEBP ou GIF.', 400);
        }

        if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
            json_error('Image trop volumineuse (max 8MB)', 400);
        }

        $uploadDir = PROJECT_ROOT . '/uploads/home-events/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            json_error('Impossible de creer le dossier de destination', 500);
        }

        $filename = 'home_event_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$mimeType];
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($tmpFile, $destination)) {
            json_error('Impossible de sauvegarder l image', 500);
        }

        $publicPath = '/uploads/home-events/' . $filename;

        json_response([
            'success' => true,
            'message' => 'Image envoyee avec succes',
            'data' => [
                'path' => $publicPath,
                'url' => $publicPath
            ]
        ]);
    }

    private function getHomeEventsSetting() {
        $raw = $this->getSettingValue('homepage_events');
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeHomeEventImageUrl($imageUrl) {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imageUrl)) {
            return $imageUrl;
        }

        if (preg_match('#^/?uploads/home-events/#i', $imageUrl)) {
            return '/' . ltrim($imageUrl, '/');
        }

        return '';
    }

    private function getSettingValue($key) {
        $stmt = $this->db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : $value;
    }

    private function saveSetting($key, $value, $description = null) {
        $exists = $this->getSettingValue($key) !== null;

        if ($exists) {
            $setClauses = ['setting_value = ?'];
            $params = [$value];

            if ($description !== null && $this->hasSettingsColumn('description')) {
                $setClauses[] = 'description = ?';
                $params[] = $description;
            }

            if ($this->hasSettingsColumn('updated_at')) {
                $setClauses[] = 'updated_at = CURRENT_TIMESTAMP';
            }

            $params[] = $key;

            $stmt = $this->db->prepare('
                UPDATE settings
                SET ' . implode(', ', $setClauses) . '
                WHERE setting_key = ?
            ');
            $stmt->execute($params);
            return;
        }

        $columns = ['setting_key', 'setting_value'];
        $placeholders = ['?', '?'];
        $params = [$key, $value];

        if ($description !== null && $this->hasSettingsColumn('description')) {
            $columns[] = 'description';
            $placeholders[] = '?';
            $params[] = $description;
        }

        $stmt = $this->db->prepare('
            INSERT INTO settings (' . implode(', ', $columns) . ')
            VALUES (' . implode(', ', $placeholders) . ')
        ');
        $stmt->execute($params);
    }

    private function hasSettingsColumn($columnName) {
        return in_array($columnName, $this->getSettingsColumns(), true);
    }

    private function getSettingsColumns() {
        if (is_array($this->settingsColumns)) {
            return $this->settingsColumns;
        }

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'pgsql') {
                $stmt = $this->db->query("
                    SELECT column_name
                    FROM information_schema.columns
                    WHERE table_schema = current_schema()
                      AND table_name = 'settings'
                ");
            } else {
                $stmt = $this->db->query("
                    SELECT column_name
                    FROM information_schema.columns
                    WHERE table_name = 'settings'
                ");
            }

            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $this->settingsColumns = is_array($columns) && $columns
                ? array_values(array_unique($columns))
                : ['setting_key', 'setting_value'];
        } catch (Exception $e) {
            $this->settingsColumns = ['setting_key', 'setting_value'];
        }

        return $this->settingsColumns;
    }
}
