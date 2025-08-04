<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to participate.']);
    exit();
}

$host = 'localhost';
$dbname = 'user_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $loggedInUsername = $_SESSION['username'];
    $eventId = $_POST['event_id'];

    // Check if the user has already participated
    $stmt = $pdo->prepare("SELECT * FROM participants WHERE event_id = :event_id AND username = :username");
    $stmt->execute(['event_id' => $eventId, 'username' => $loggedInUsername]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'already_participated', 'message' => 'You are already in this event.']);
        exit();
    }

    // Insert the user into the participants table
    $stmt = $pdo->prepare("INSERT INTO participants (event_id, username) VALUES (:event_id, :username)");
    $stmt->execute(['event_id' => $eventId, 'username' => $loggedInUsername]);

    echo json_encode(['status' => 'success', 'message' => 'You have successfully joined the event!']);
   
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
