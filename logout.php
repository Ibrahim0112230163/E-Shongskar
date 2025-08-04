<?php

include 'database.php';
session_start();

// Check if the user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    $stmt = $pdo->prepare("DELETE FROM local_users_online WHERE username = :username");
    $stmt->execute(['username' => $username]);
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set cache control headers to prevent back button access
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to regular user login
header('Location: login.php');
exit;
?>