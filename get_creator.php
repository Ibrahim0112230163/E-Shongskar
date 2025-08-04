<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit();
}

include 'database.php';

$eventId = $_GET['event_id'] ?? '';

if (empty($eventId)) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT u.full_name, u.contact_no, u.location FROM users u JOIN events e ON u.username = e.username WHERE e.id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($creator) {
        echo json_encode(['success' => true, 'creator' => $creator]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Creator not found.']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
