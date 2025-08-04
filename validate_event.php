<?php
include 'database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$eventId = $data['event_id'] ?? null;

if ($eventId) {
    $stmt = $pdo->prepare("SELECT username FROM events WHERE id = :id");
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        echo json_encode(['success' => true, 'username' => $event['username']]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
