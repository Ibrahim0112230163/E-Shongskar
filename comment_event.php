<?php
include 'database.php';
session_start();
header('Content-Type: application/json');

// Add debugging
error_log("Comment event request: " . print_r($_POST, true));
error_log("Session username: " . ($_SESSION['username'] ?? 'not set'));

if (!isset($_SESSION['username']) || !isset($_POST['comment_event_id']) || !isset($_POST['comment_text'])) {
    error_log("Missing required fields for comment");
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$eventId = (int)$_POST['comment_event_id'];
$commentText = trim($_POST['comment_text']);
$username = $_SESSION['username'];

if (empty($commentText)) {
    error_log("Empty comment text");
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit;
}

error_log("Adding comment for event $eventId by user $username: $commentText");

try {
    $pdo->prepare("INSERT INTO comments (event_id, username, comment_text) VALUES (:event_id, :username, :comment_text)")->execute(['event_id' => $eventId, 'username' => $username, 'comment_text' => $commentText]);
    
    // Get updated like and comment counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    $total_likes = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    $total_comments = (int)$stmt->fetchColumn();
    
    $response = [
        'success' => true, 
        'total_likes' => $total_likes, 
        'total_comments' => $total_comments,
        'comment' => [
            'username' => $username,
            'comment_text' => $commentText
        ]
    ];
    error_log("Comment response: " . json_encode($response));
    
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Comment insertion failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
