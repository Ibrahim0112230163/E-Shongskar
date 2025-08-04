<?php
session_start();
include('database.php');
header('Content-Type: application/json');

// Add debugging
error_log("Like event request: " . print_r($_POST, true));
error_log("Session username: " . ($_SESSION['username'] ?? 'not set'));

if (!isset($_SESSION['username']) || !isset($_POST['like_event_id'])) {
    error_log("Missing session or event_id");
    echo json_encode(['success' => false, 'message' => 'Missing session or event_id']);
    exit;
}

$eventId = (int)$_POST['like_event_id'];
$username = $_SESSION['username'];

error_log("Processing like for event $eventId by user $username");

$stmt = $pdo->prepare("SELECT like_id FROM likes WHERE event_id = :event_id AND username = :username");
$stmt->execute(['event_id' => $eventId, 'username' => $username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Unlike
    $pdo->prepare("DELETE FROM likes WHERE like_id = :like_id")->execute(['like_id' => $row['like_id']]);
    $liked = false;
    error_log("Unliked event $eventId");
} else {
    // Like
    $pdo->prepare("INSERT INTO likes (event_id, username) VALUES (:event_id, :username)")->execute(['event_id' => $eventId, 'username' => $username]);
    $liked = true;
    error_log("Liked event $eventId");
}

// Get updated like count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE event_id = :event_id");
$stmt->execute(['event_id' => $eventId]);
$total_likes = (int)$stmt->fetchColumn();

// Get updated comment count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE event_id = :event_id");
$stmt->execute(['event_id' => $eventId]);
$total_comments = (int)$stmt->fetchColumn();

$response = ['success' => true, 'liked' => $liked, 'total_likes' => $total_likes, 'total_comments' => $total_comments];
error_log("Like response: " . json_encode($response));

echo json_encode($response);
?>
