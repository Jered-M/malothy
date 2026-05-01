<?php
$file = 'backend/api/controllers/FinanceController.php';
$content = file_get_contents($file);

$old = "            if (\$paymentRequest['status'] !== 'success') {\n                json_error('Erreur lors de la création de la demande de paiement', 500);\n            }";

$new = "            if (\$paymentRequest['status'] !== 'success') {\n                json_error('Erreur lors de la création de la demande de paiement : ' . (\$paymentRequest['message'] ?? 'Erreur inconnue'), 500);\n            }";

$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
echo "Replacement successful\n";
