<?php
// includes/flash.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function display_flash() {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $msg = $_SESSION['flash']['message'];
        $class = ($type === 'success') ? 'notice-success' : (($type === 'error') ? 'notice-danger' : 'notice-info');
        echo '<div class="notice ' . htmlspecialchars($class) . '" style="margin:16px 0;padding:12px;background:#e6ffed;color:#226622;border:1px solid #b6e2c0;border-radius:4px;">' . htmlspecialchars($msg) . '</div>';
        unset($_SESSION['flash']);
    }
}
