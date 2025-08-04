<?php
include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $username = $_SESSION['username'];
    
    try {
        if ($type === 'notifications') {
            // Mark all notifications as read
            $query = "UPDATE notifications SET read_status = 1 WHERE recipient = :username AND read_status = 0";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            
            $response = ['success' => true, 'message' => 'Notifications marked as read'];
            
        } elseif ($type === 'messages') {
            // Mark all messages as read
            $query = "UPDATE chat SET read_status = 1 WHERE to_username = :username AND (read_status = 0 OR read_status IS NULL)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            
            $response = ['success' => true, 'message' => 'Messages marked as read'];
            
        } else {
            $response = ['success' => false, 'message' => 'Invalid type'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?> 