<?php
include 'database.php';
session_start();

echo "<h1>Contact Requests Debug</h1>";

if (!isset($_SESSION['username'])) {
    echo "<p style='color: red;'>Please log in first</p>";
    exit;
}

$username = $_SESSION['username'];
echo "<p><strong>Current user:</strong> " . htmlspecialchars($username) . "</p>";

// Test database connection
try {
    $pdo->query('SELECT 1');
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if contact_requests table exists
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
        
        // Show all contact requests
        $stmt = $pdo->query("SELECT * FROM contact_requests ORDER BY created_at DESC LIMIT 10");
        echo "<h3>Recent Contact Requests:</h3>";
        if ($stmt->rowCount() > 0) {
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
        } else {
            echo "<p>No contact requests found</p>";
        }
        
        // Check for pending requests for current user
        $stmt = $pdo->prepare("SELECT * FROM contact_requests WHERE donor_username = :username AND status = 'pending'");
        $stmt->execute(['username' => $username]);
        echo "<h3>Pending Requests for Current User:</h3>";
        if ($stmt->rowCount() > 0) {
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
        } else {
            echo "<p>No pending requests for current user</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ contact_requests table does not exist</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking contact_requests table: " . $e->getMessage() . "</p>";
}

// Check notifications table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ notifications table exists</p>";
        
        // Show recent notifications for current user
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient = :username ORDER BY created_at DESC LIMIT 5");
        $stmt->execute(['username' => $username]);
        echo "<h3>Recent Notifications for Current User:</h3>";
        if ($stmt->rowCount() > 0) {
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
        } else {
            echo "<p>No notifications found for current user</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ notifications table does not exist</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking notifications table: " . $e->getMessage() . "</p>";
}

// Test form submission
if (isset($_POST['test_action'])) {
    echo "<h3>Testing Action:</h3>";
    echo "<p><strong>POST data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Simulate the contact request action
    if (isset($_POST['contact_request_action'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        
        echo "<p><strong>Processing:</strong> Request ID = $request_id, Action = $action</p>";
        
        try {
            // Get request details
            $getRequestQuery = "SELECT * FROM contact_requests WHERE id = :request_id AND donor_username = :donor_username";
            $stmt = $pdo->prepare($getRequestQuery);
            $stmt->execute([
                'request_id' => $request_id,
                'donor_username' => $username
            ]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Request found:</strong> " . ($request ? 'Yes' : 'No') . "</p>";
            if ($request) {
                echo "<p><strong>Request details:</strong></p>";
                echo "<pre>" . print_r($request, true) . "</pre>";
                
                // Update request status
                $updateQuery = "UPDATE contact_requests SET status = :status WHERE id = :request_id";
                $stmt = $pdo->prepare($updateQuery);
                $result = $stmt->execute([
                    'status' => $action,
                    'request_id' => $request_id
                ]);
                
                echo "<p><strong>Update result:</strong> " . ($result ? 'Success' : 'Failed') . "</p>";
                
                if (!$result) {
                    echo "<p><strong>Update error:</strong></p>";
                    echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
                }
                
                // Send notification to requester
                $message = $action == 'accepted' 
                    ? "Your contact request has been accepted by $username."
                    : "Your contact request has been declined by $username.";
                
                $notificationQuery = "INSERT INTO notifications (recipient, message, type, created_at, read_status) 
                                     VALUES (:recipient, :message, 'contact_response', NOW(), 0)";
                $stmt = $pdo->prepare($notificationQuery);
                $notificationResult = $stmt->execute([
                    'recipient' => $request['requester'],
                    'message' => $message
                ]);
                
                echo "<p><strong>Notification result:</strong> " . ($notificationResult ? 'Success' : 'Failed') . "</p>";
                
                if (!$notificationResult) {
                    echo "<p><strong>Notification error:</strong></p>";
                    echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
                }
                
                echo "<p style='color: green;'>✓ Test completed successfully</p>";
            } else {
                echo "<p style='color: red;'>✗ Request not found</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
        }
    }
}

// Show test form
echo "<h3>Test Form</h3>";
echo "<form method='POST'>";
echo "<p>Request ID: <input type='number' name='request_id' required></p>";
echo "<p>Action: <select name='action'><option value='accepted'>Accepted</option><option value='declined'>Declined</option></select></p>";
echo "<p><input type='hidden' name='contact_request_action' value='1'></p>";
echo "<p><input type='submit' name='test_action' value='Test Action'></p>";
echo "</form>";

echo "<p><a href='notify.php'>Go to Notifications Page</a></p>";
echo "<p><a href='blood_donate.php'>Go to Blood Donation Page</a></p>";
?> 