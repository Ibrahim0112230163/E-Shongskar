<?php
include 'database.php';

try {
    // Check if comments table exists
    $stmt = $pdo->query("DESCRIBE comments");
    echo "<h3>Comments table structure:</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // Check existing comments
    $stmt = $pdo->query("SELECT * FROM comments LIMIT 10");
    echo "<h3>Existing comments (first 10):</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // Test inserting a comment
    echo "<h3>Testing comment insertion:</h3>";
    $testEventId = 1; // Assume event ID 1 exists
    $testUsername = 'test_user';
    $testComment = 'Test comment from debug script';
    
    $stmt = $pdo->prepare("INSERT INTO comments (event_id, username, comment_text) VALUES (?, ?, ?)");
    $result = $stmt->execute([$testEventId, $testUsername, $testComment]);
    
    if ($result) {
        echo "✓ Comment insertion successful<br>";
        echo "Last insert ID: " . $pdo->lastInsertId() . "<br>";
    } else {
        echo "✗ Comment insertion failed<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
