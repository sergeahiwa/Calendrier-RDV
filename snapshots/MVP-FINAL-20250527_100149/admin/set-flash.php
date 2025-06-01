<?php
require_once __DIR__ . '/../includes/flash.php';
$type = $_GET['type'] ?? 'info';
$msg = $_GET['msg'] ?? '';
if ($msg) {
    set_flash($type, $msg);
}
// Réponse vide (appelée via fetch)
http_response_code(204);
