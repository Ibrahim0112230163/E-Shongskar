<?php
include 'database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['username']) || !isset($_POST['report_id'])) {
    echo json_encode(['success' => false]);
    exit;
}
$reportId = (int)$_POST['report_id'];
$username = $_SESSION['username'];
// Check if already saved
$stmt = $pdo->prepare("SELECT id FROM saved WHERE username = :username AND saves = :saves");
$stmt->execute(['username' => $username, 'saves' => $reportId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    // Unsave (delete)
    $del = $pdo->prepare("DELETE FROM saved WHERE id = :id");
    $del->execute(['id' => $row['id']]);
    echo json_encode(['success' => true, 'saved' => false]);
    exit;
} else {
    // Save
    $ins = $pdo->prepare("INSERT INTO saved (username, saves) VALUES (:username, :saves)");
    $ins->execute(['username' => $username, 'saves' => $reportId]);
    echo json_encode(['success' => true, 'saved' => true]);
    exit;
} 