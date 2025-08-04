<?php
include 'database.php';
session_start();

echo "<h2>Contact Requests Table Test</h2>";

// Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'contact_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ contact_requests table exists</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE contact_requests");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data
        $stmt = $pdo->query("SELECT * FROM contact_requests LIMIT 5");
        echo "<h3>Sample Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        if ($stmt->rowCount() > 0) {
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
        } else {
            echo "<tr><td colspan='6'>No data found</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>✗ contact_requests table does not exist</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check notifications table
echo "<h2>Notifications Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ notifications table exists</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE notifications");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>✗ notifications table does not exist</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test current user session
echo "<h2>Session Test</h2>";
if (isset($_SESSION['username'])) {
    echo "<p style='color: green;'>✓ User logged in: " . htmlspecialchars($_SESSION['username']) . "</p>";
    
    // Check if user is a blood donor
    try {
        $stmt = $pdo->prepare("SELECT * FROM blood_donor WHERE user_nid = (SELECT nid_birth_certificate FROM users WHERE username = :username)");
        $stmt->execute(['username' => $_SESSION['username']]);
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ User is a blood donor</p>";
        } else {
            echo "<p style='color: orange;'>⚠ User is not a blood donor</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error checking blood donor status: " . $e->getMessage() . "</p>";
    }
    
    // Check for pending contact requests
    try {
        $stmt = $pdo->prepare("SELECT * FROM contact_requests WHERE donor_username = :username AND status = 'pending'");
        $stmt->execute(['username' => $_SESSION['username']]);
        echo "<p>Pending contact requests: " . $stmt->rowCount() . "</p>";
        
        if ($stmt->rowCount() > 0) {
            echo "<h3>Pending Requests:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
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
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error checking contact requests: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ No user logged in</p>";
}

// Test form submission simulation
echo "<h2>Form Submission Test</h2>";
if (isset($_POST['test_submit'])) {
    echo "<p>Testing form submission...</p>";
    
    // Simulate the contact request action
    $_POST['contact_request_action'] = true;
    $_POST['request_id'] = $_POST['test_request_id'];
    $_POST['action'] = $_POST['test_action'];
    
    // Include the notify.php logic here
    if (isset($_POST['contact_request_action'])) {
        error_log("Contact request action received: " . print_r($_POST, true));
        
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        
        error_log("Processing contact request - ID: $request_id, Action: $action");
        
        try {
            // Get request details
            $getRequestQuery = "SELECT * FROM contact_requests WHERE id = :request_id AND donor_username = :donor_username";
            $stmt = $pdo->prepare($getRequestQuery);
            $stmt->execute([
                'request_id' => $request_id,
                'donor_username' => $_SESSION['username']
            ]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Request details: " . print_r($request, true));
            
            if ($request) {
                // Update request status
                $updateQuery = "UPDATE contact_requests SET status = :status WHERE id = :request_id";
                $stmt = $pdo->prepare($updateQuery);
                $result = $stmt->execute([
                    'status' => $action,
                    'request_id' => $request_id
                ]);
                
                error_log("Update result: " . ($result ? 'success' : 'failed'));
                
                if (!$result) {
                    error_log("Update failed: " . print_r($stmt->errorInfo(), true));
                }
                
                // Send notification to requester
                $message = $action == 'accepted' 
                    ? "Your contact request has been accepted by {$_SESSION['username']}."
                    : "Your contact request has been declined by {$_SESSION['username']}.";
                
                $notificationQuery = "INSERT INTO notifications (recipient, message, type, created_at, read_status) 
                                     VALUES (:recipient, :message, 'contact_response', NOW(), 0)";
                $stmt = $pdo->prepare($notificationQuery);
                $notificationResult = $stmt->execute([
                    'recipient' => $request['requester'],
                    'message' => $message
                ]);
                
                error_log("Notification result: " . ($notificationResult ? 'success' : 'failed'));
                
                if (!$notificationResult) {
                    error_log("Notification failed: " . print_r($stmt->errorInfo(), true));
                }
                
                echo "<p style='color: green;'>✓ Test successful: Request " . $action . "</p>";
            } else {
                error_log("Request not found for ID: $request_id and donor: {$_SESSION['username']}");
                echo "<p style='color: red;'>✗ Test failed: Request not found</p>";
            }
        } catch (PDOException $e) {
            error_log("Database error in contact request: " . $e->getMessage());
            echo "<p style='color: red;'>✗ Test failed: Database error - " . $e->getMessage() . "</p>";
        }
    }
}

// Show test form
echo "<h2>Test Form</h2>";
echo "<form method='POST'>";
echo "<p>Request ID: <input type='number' name='test_request_id' required></p>";
echo "<p>Action: <select name='test_action'><option value='accepted'>Accepted</option><option value='declined'>Declined</option></select></p>";
echo "<p><input type='submit' name='test_submit' value='Test Submit'></p>";
echo "</form>";

echo "<h2>Error Log</h2>";
echo "<p>Check your server's error log for detailed debugging information.</p>";
echo "<p>Common locations:</p>";
echo "<ul>";
echo "<li>XAMPP: xampp/apache/logs/error.log</li>";
echo "<li>WAMP: wamp/logs/apache_error.log</li>";
echo "<li>Linux: /var/log/apache2/error.log</li>";
echo "</ul>";
?> 