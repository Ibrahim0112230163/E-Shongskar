<?php
include 'database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "Please log in first.";
    exit;
}

$username = $_SESSION['username'];

echo "<h2>Notification Badge Test</h2>";
echo "<p>Testing for user: <strong>$username</strong></p>";

// Test unread notifications count
try {
    $notifyCountQuery = "SELECT COUNT(*) as count FROM notifications 
                        WHERE recipient = :username AND read_status = 0";
    $notifyStmt = $pdo->prepare($notifyCountQuery);
    $notifyStmt->execute(['username' => $username]);
    $notifyData = $notifyStmt->fetch(PDO::FETCH_ASSOC);
    
    $notificationCount = $notifyData ? $notifyData['count'] : 0;
    echo "<p><strong>Unread Notifications:</strong> $notificationCount</p>";
} catch (PDOException $e) {
    echo "<p><strong>Error getting notifications:</strong> " . $e->getMessage() . "</p>";
}

// Test unread chat messages count
try {
    $chatCountQuery = "SELECT COUNT(*) as count FROM chat 
                       WHERE to_username = :username AND (read_status = 0 OR read_status IS NULL)";
    $chatStmt = $pdo->prepare($chatCountQuery);
    $chatStmt->execute(['username' => $username]);
    $chatData = $chatStmt->fetch(PDO::FETCH_ASSOC);
    
    $unreadChatCount = $chatData ? $chatData['count'] : 0;
    echo "<p><strong>Unread Chat Messages:</strong> $unreadChatCount</p>";
} catch (PDOException $e) {
    echo "<p><strong>Error getting chat messages:</strong> " . $e->getMessage() . "</p>";
}

// Test creating a sample notification
if (isset($_GET['create_notification'])) {
    try {
        $insertQuery = "INSERT INTO notifications (recipient, message, type, created_at, read_status) 
                       VALUES (:recipient, :message, 'test', NOW(), 0)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'recipient' => $username,
            'message' => 'Test notification created at ' . date('Y-m-d H:i:s')
        ]);
        echo "<p><strong>Test notification created successfully!</strong></p>";
    } catch (PDOException $e) {
        echo "<p><strong>Error creating test notification:</strong> " . $e->getMessage() . "</p>";
    }
}

// Test creating a sample chat message
if (isset($_GET['create_message'])) {
    try {
        $insertQuery = "INSERT INTO chat (from_username, to_username, created_at, history, read_status) 
                       VALUES (:from_username, :to_username, NOW(), :message, 0)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'from_username' => 'test_user',
            'to_username' => $username,
            'message' => 'Test message created at ' . date('Y-m-d H:i:s')
        ]);
        echo "<p><strong>Test message created successfully!</strong></p>";
    } catch (PDOException $e) {
        echo "<p><strong>Error creating test message:</strong> " . $e->getMessage() . "</p>";
    }
}

// Test marking all as read
if (isset($_GET['mark_read'])) {
    try {
        // Mark notifications as read
        $notifyQuery = "UPDATE notifications SET read_status = 1 WHERE recipient = :username AND read_status = 0";
        $stmt = $pdo->prepare($notifyQuery);
        $stmt->execute(['username' => $username]);
        
        // Mark messages as read
        $chatQuery = "UPDATE chat SET read_status = 1 WHERE to_username = :username AND (read_status = 0 OR read_status IS NULL)";
        $stmt = $pdo->prepare($chatQuery);
        $stmt->execute(['username' => $username]);
        
        echo "<p><strong>All items marked as read!</strong></p>";
    } catch (PDOException $e) {
        echo "<p><strong>Error marking as read:</strong> " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h3>Test Actions:</h3>";
echo "<p><a href='?create_notification=1'>Create Test Notification</a></p>";
echo "<p><a href='?create_message=1'>Create Test Message</a></p>";
echo "<p><a href='?mark_read=1'>Mark All as Read</a></p>";
echo "<p><a href='test_notification_badges.php'>Refresh Counts</a></p>";
echo "<p><a href='landingPageAfterLogin.php'>Go to Dashboard</a></p>";

echo "<hr>";
echo "<h3>Expected Behavior:</h3>";
echo "<ul>";
echo "<li>When you have unread notifications, the bell icon should show a red badge with the count</li>";
echo "<li>When you have unread messages, the chat icon should show a red badge with the count</li>";
echo "<li>When you visit notify.php, the notification badge should disappear</li>";
echo "<li>When you visit chat.php, the message badge should disappear</li>";
echo "<li>Badges show '9+' if count is greater than 9</li>";
echo "</ul>";
?> 