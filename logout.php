<?php
require_once __DIR__ . '/includes/functions.php';

// "?scope=admin" logs out only the admin session; otherwise both are cleared.
if (input('scope') === 'admin') {
    unset($_SESSION['admin_id']);
    flash('success', 'You have been logged out of the admin panel.');
    redirect('login.php?mode=admin');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Location: ' . url('login.php'));
exit;
