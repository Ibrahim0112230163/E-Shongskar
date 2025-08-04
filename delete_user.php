<?php

include 'database.php';

if (!isset($_GET['username'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$username = $_GET['username'];

$stmt = $pdo->prepare("DELETE FROM users WHERE username = :username");
$stmt->execute(['username' => $username]);

header("Location: user_data.php");
exit;
?>
