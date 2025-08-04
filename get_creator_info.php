<?php
session_start();
include('database.php');
header('Content-Type: application/json');

// Add debugging
error_log("Get creator info request: " . print_r($_GET, true));

if (!isset($_SESSION['username']) || !isset($_GET['event_id'])) {
    error_log("Missing session or event_id for creator info");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$eventId = (int)$_GET['event_id'];
error_log("Loading creator info for event: $eventId");

try {
    $stmt = $pdo->prepare("SELECT u.full_name, u.contact_no, u.location FROM users u JOIN events e ON u.username = e.username WHERE e.id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($creator) {
        error_log("Found creator for event $eventId: " . $creator['full_name']);
        echo json_encode([
            'success' => true, 
            'creator' => [
                'full_name' => htmlspecialchars($creator['full_name']),
                'contact_no' => htmlspecialchars($creator['contact_no']),
                'location' => htmlspecialchars($creator['location'])
            ]
        ]);
    } else {
        error_log("Creator not found for event $eventId");
        echo json_encode(['success' => false, 'message' => 'Creator not found']);
    }
} catch (Exception $e) {
    error_log("Database error in get_creator_info: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
