<?php
include 'database.php';
session_start();
header('Content-Type: application/json');

// Handle event comments
if (isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
    
    try {
        // First try with created_at, if that fails, try without it
        try {
            $stmt = $pdo->prepare("SELECT username, comment_text FROM comments WHERE event_id = :event_id ORDER BY created_at DESC");
            $stmt->execute(['event_id' => $eventId]);
        } catch (PDOException $e) {
            // If created_at column doesn't exist, try without ORDER BY
            $stmt = $pdo->prepare("SELECT username, comment_text FROM comments WHERE event_id = :event_id");
            $stmt->execute(['event_id' => $eventId]);
        }
        
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add some debugging
        error_log("Fetched comments for event $eventId: " . json_encode($comments));
        
        echo json_encode(['success' => true, 'comments' => $comments]);
    } catch (PDOException $e) {
        error_log("Error fetching comments: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle report comments (existing functionality)
if (!isset($_GET['report_id'])) {
    echo json_encode([]);
    exit;
}
$reportId = (int)$_GET['report_id'];
$stmt = $pdo->prepare("SELECT username, comments FROM report_vote WHERE report_id = :report_id AND comments IS NOT NULL ORDER BY created_at DESC");
$stmt->execute(['report_id' => $reportId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
