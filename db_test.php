<?php
include 'database.php';

echo "<h1>Database Test</h1>";

try {
    // Test connection
    $pdo->query('SELECT 1');
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check contact_requests table
    $stmt = $pdo->query("SHOW TABLES LIKE 'contact_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ contact_requests table exists</p>";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE contact_requests");
        echo "<h3>contact_requests structure:</h3>";
        echo "<table border='1'>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td></tr>";
        }
        echo "</table>";
        
        // Show data
        $stmt = $pdo->query("SELECT * FROM contact_requests LIMIT 3");
        echo "<h3>contact_requests data:</h3>";
        if ($stmt->rowCount() > 0) {
            echo "<table border='1'>";
            $first = true;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($row) as $key) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No data in contact_requests table</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ contact_requests table does not exist</p>";
    }
    
    // Check notifications table
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ notifications table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ notifications table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 