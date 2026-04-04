<?php
/**
 * Seed demo data for soutenance
 * Usage: php backend/seed-demo.php [--force]
 */

require_once __DIR__ . '/config/database.php';

$force = in_array('--force', $argv, true);

$db = Database::getInstance()->getConnection();

function table_count($db, $table) {
    $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
    return (int)$stmt->fetchColumn();
}

function table_columns($db, $table) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $stmt = $db->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = ?
        ");
        $stmt->execute([$table]);
    } else {
        $stmt = $db->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ?
        ");
        $stmt->execute([$table]);
    }
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function insert_row($db, $table, $data, $columnsCache) {
    if (!isset($columnsCache[$table])) {
        return;
    }
    $allowed = $columnsCache[$table];
    $filtered = array_intersect_key($data, $allowed);
    if (!$filtered) {
        return;
    }
    $columns = array_keys($filtered);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_values($filtered));
}

function to_date($monthsAgo, $day) {
    $base = new DateTimeImmutable('first day of this month');
    $date = $base->modify("-{$monthsAgo} months")->setDate(
        (int)$base->modify("-{$monthsAgo} months")->format('Y'),
        (int)$base->modify("-{$monthsAgo} months")->format('m'),
        $day
    );
    return $date->format('Y-m-d');
}

$columnsCache = [
    'members' => array_flip(table_columns($db, 'members')),
    'tithes' => array_flip(table_columns($db, 'tithes')),
    'offerings' => array_flip(table_columns($db, 'offerings')),
    'expenses' => array_flip(table_columns($db, 'expenses'))
];

// ---- Members ----
$members = [
    ['first_name' => 'Josue', 'last_name' => 'Mena', 'email' => 'josue.mena@maloty.com', 'phone' => '+243810000001', 'address' => 'Gombe', 'department' => 'Chorale', 'join_date' => '2024-01-15', 'status' => 'actif'],
    ['first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie.martin@maloty.com', 'phone' => '+243810000002', 'address' => 'Kintambo', 'department' => 'Accueil', 'join_date' => '2024-02-02', 'status' => 'actif'],
    ['first_name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'jean.dupont@maloty.com', 'phone' => '+243810000003', 'address' => 'Ngaliema', 'department' => 'Jeunesse', 'join_date' => '2024-03-10', 'status' => 'actif'],
    ['first_name' => 'Aline', 'last_name' => 'Kany', 'email' => 'aline.kany@maloty.com', 'phone' => '+243810000004', 'address' => 'Lemba', 'department' => 'Intercession', 'join_date' => '2024-03-25', 'status' => 'actif'],
    ['first_name' => 'Patrick', 'last_name' => 'Ilunga', 'email' => 'patrick.ilunga@maloty.com', 'phone' => '+243810000005', 'address' => 'Bandal', 'department' => 'Logistique', 'join_date' => '2024-04-11', 'status' => 'actif'],
    ['first_name' => 'Clarisse', 'last_name' => 'Mwamba', 'email' => 'clarisse.mwamba@maloty.com', 'phone' => '+243810000006', 'address' => 'Ngiri-Ngiri', 'department' => 'Femmes', 'join_date' => '2024-04-26', 'status' => 'actif'],
    ['first_name' => 'Kevin', 'last_name' => 'Mutombo', 'email' => 'kevin.mutombo@maloty.com', 'phone' => '+243810000007', 'address' => 'Kasa-Vubu', 'department' => 'Jeunesse', 'join_date' => '2024-05-05', 'status' => 'actif'],
    ['first_name' => 'Rachel', 'last_name' => 'Nkosi', 'email' => 'rachel.nkosi@maloty.com', 'phone' => '+243810000008', 'address' => 'Kimbanseke', 'department' => 'Enfance', 'join_date' => '2024-05-19', 'status' => 'actif'],
    ['first_name' => 'Samuel', 'last_name' => 'Kabongo', 'email' => 'samuel.kabongo@maloty.com', 'phone' => '+243810000009', 'address' => 'Matete', 'department' => 'Securite', 'join_date' => '2024-06-07', 'status' => 'actif'],
    ['first_name' => 'Esther', 'last_name' => 'Mulumba', 'email' => 'esther.mulumba@maloty.com', 'phone' => '+243810000010', 'address' => 'Kisenso', 'department' => 'Administration', 'join_date' => '2024-06-21', 'status' => 'actif'],
    ['first_name' => 'Junior', 'last_name' => 'Banza', 'email' => 'junior.banza@maloty.com', 'phone' => '+243810000011', 'address' => 'Mont Ngafula', 'department' => 'Jeunesse', 'join_date' => '2024-07-12', 'status' => 'actif'],
    ['first_name' => 'Chantal', 'last_name' => 'Lukusa', 'email' => 'chantal.lukusa@maloty.com', 'phone' => '+243810000012', 'address' => 'Selembao', 'department' => 'Louange', 'join_date' => '2024-08-03', 'status' => 'actif'],
    ['first_name' => 'Joel', 'last_name' => 'Mayele', 'email' => 'joel.mayele@maloty.com', 'phone' => '+243810000013', 'address' => 'Masina', 'department' => 'Technique', 'join_date' => '2024-08-18', 'status' => 'inactif'],
    ['first_name' => 'Prisca', 'last_name' => 'Ngoma', 'email' => 'prisca.ngoma@maloty.com', 'phone' => '+243810000014', 'address' => 'Nsele', 'department' => 'Protocole', 'join_date' => '2024-09-05', 'status' => 'actif'],
    ['first_name' => 'David', 'last_name' => 'Lutumba', 'email' => 'david.lutumba@maloty.com', 'phone' => '+243810000015', 'address' => 'Ndjili', 'department' => 'Hommes', 'join_date' => '2024-09-23', 'status' => 'actif']
];

$existingEmails = $db->query("SELECT email FROM members WHERE email IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$existingPhones = $db->query("SELECT phone FROM members WHERE phone IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

foreach ($members as $member) {
    $email = strtolower(trim($member['email']));
    $phone = trim($member['phone']);
    if (!$force && (in_array($email, $existingEmails) || in_array($phone, $existingPhones))) {
        continue;
    }

    $stmt = $db->prepare("
        INSERT INTO members (first_name, last_name, email, phone, address, department, join_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $member['first_name'],
        $member['last_name'],
        $member['email'],
        $member['phone'],
        $member['address'],
        $member['department'],
        $member['join_date'],
        $member['status']
    ]);
}

$memberIds = $db->query("SELECT id FROM members ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
if (!$memberIds) {
    echo "Aucun membre trouve, seed annule.\n";
    exit(1);
}

$adminId = $db->prepare("SELECT id FROM users WHERE email = ?");
$adminId->execute(['admin@maloty.com']);
$adminId = $adminId->fetchColumn();
if (!$adminId) {
    $adminId = $db->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetchColumn();
}

// ---- Tithes ----
if ($force || table_count($db, 'tithes') === 0) {
    $tithePlan = [
        0 => [500000, 650000, 300000, 700000, 700000], // 2 850 000
        1 => [420000, 380000, 510000, 260000],
        2 => [300000, 350000, 400000],
        3 => [280000, 240000, 220000],
        4 => [200000, 180000, 160000],
        5 => [150000, 120000, 110000]
    ];

    foreach ($tithePlan as $monthsAgo => $amounts) {
        foreach ($amounts as $index => $amount) {
            $memberId = $memberIds[$index % count($memberIds)];
            $row = [
                'member_id' => $memberId,
                'amount' => $amount,
                'currency' => 'CDF',
                'tithe_date' => to_date($monthsAgo, 5 + $index * 3),
                'payment_status' => 'paid',
                'comment' => 'Dime reguliere',
                'recorded_at' => date('Y-m-d H:i:s'),
                'recorded_by' => $adminId
            ];
            insert_row($db, 'tithes', $row, $columnsCache);
        }
    }
}

// ---- Offerings / Cotisations ----
if ($force || table_count($db, 'offerings') === 0) {
    $offeringPlan = [
        0 => [
            ['type' => 'culte', 'amount' => 450000],
            ['type' => 'evenement', 'amount' => 300000],
            ['type' => 'cotisation', 'amount' => 400000]
        ],
        1 => [
            ['type' => 'culte', 'amount' => 320000],
            ['type' => 'mission', 'amount' => 250000]
        ],
        2 => [
            ['type' => 'culte', 'amount' => 280000],
            ['type' => 'cotisation', 'amount' => 220000]
        ],
        3 => [
            ['type' => 'evenement', 'amount' => 210000],
            ['type' => 'autre', 'amount' => 150000]
        ],
        4 => [
            ['type' => 'mission', 'amount' => 180000]
        ],
        5 => [
            ['type' => 'culte', 'amount' => 140000]
        ]
    ];

    foreach ($offeringPlan as $monthsAgo => $rows) {
        foreach ($rows as $index => $row) {
            $data = [
                'type' => $row['type'],
                'amount' => $row['amount'],
                'currency' => 'CDF',
                'offering_date' => to_date($monthsAgo, 8 + $index * 4),
                'payment_status' => 'paid',
                'description' => 'Collecte soutenance',
                'recorded_at' => date('Y-m-d H:i:s'),
                'recorded_by' => $adminId
            ];
            insert_row($db, 'offerings', $data, $columnsCache);
        }
    }
}

// ---- Expenses ----
if ($force || table_count($db, 'expenses') === 0) {
    $expensePlan = [
        0 => [
            ['category' => 'loyer', 'amount' => 350000, 'description' => 'Loyer salle principale'],
            ['category' => 'salaire', 'amount' => 420000, 'description' => 'Equipe technique'],
            ['category' => 'entretien', 'amount' => 160000, 'description' => 'Maintenance']
        ],
        1 => [
            ['category' => 'mission', 'amount' => 220000, 'description' => 'Soutien mission locale'],
            ['category' => 'communion', 'amount' => 90000, 'description' => 'Articles communion']
        ],
        2 => [
            ['category' => 'entretien', 'amount' => 140000, 'description' => 'Entretien materiel']
        ]
    ];

    foreach ($expensePlan as $monthsAgo => $rows) {
        foreach ($rows as $index => $row) {
            $date = to_date($monthsAgo, 12 + $index * 3);
            $data = [
                'category' => $row['category'],
                'amount' => $row['amount'],
                'expense_date' => $date,
                'description' => $row['description'],
                'document_path' => null,
                'status' => 'approuvee',
                'recorded_at' => date('Y-m-d H:i:s'),
                'recorded_by' => $adminId,
                'approved_by' => $adminId,
                'approval_date' => $date . ' 10:00:00'
            ];
            insert_row($db, 'expenses', $data, $columnsCache);
        }
    }
}

echo "Seed demo termine.\n";
