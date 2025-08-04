<?php
include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    echo "Please log in first.";
    exit;
}

$username = $_SESSION['username'];

echo "<h2>Debug Notifications</h2>";
echo "<p>Testing for user: <strong>$username</strong></p>";

// Get user location
$userLocationQuery = "SELECT location FROM users WHERE username = :username";
$userStmt = $pdo->prepare($userLocationQuery);
$userStmt->execute(['username' => $username]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($userData) {
    $userLocation = $userData['location'];
    echo "<p><strong>User location:</strong> $userLocation</p>";
    
    // Test recent reports
    echo "<h3>Recent Reports (Last 7 days)</h3>";
    $reportQuery = "SELECT username, issue_title, date_reported FROM report_issues 
                   WHERE location = :location 
                   AND DATE(date_reported) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   ORDER BY date_reported DESC";
    $stmt = $pdo->prepare($reportQuery);
    $stmt->execute(['location' => $userLocation]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($reports) {
        echo "<ul>";
        foreach ($reports as $report) {
            echo "<li>{$report['username']} - {$report['issue_title']} - {$report['date_reported']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No recent reports found.</p>";
    }
    
    // Test recent events
    echo "<h3>Recent Events (Last 7 days)</h3>";
    $eventQuery = "SELECT username, id, created_at FROM events 
                  WHERE location = :location 
                  AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  ORDER BY created_at DESC";
    $stmt = $pdo->prepare($eventQuery);
    $stmt->execute(['location' => $userLocation]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($events) {
        echo "<ul>";
        foreach ($events as $event) {
            echo "<li>{$event['username']} - Event ID: {$event['id']} - {$event['created_at']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No recent events found.</p>";
    }
    
    // Test recent alerts
    echo "<h3>Recent Alerts (Last 7 days)</h3>";
    $alertQuery = "SELECT description, date FROM alerts 
                  WHERE location = :location 
                  AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  ORDER BY date DESC";
    $stmt = $pdo->prepare($alertQuery);
    $stmt->execute(['location' => $userLocation]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($alerts) {
        echo "<ul>";
        foreach ($alerts as $alert) {
            echo "<li>{$alert['description']} - {$alert['date']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No recent alerts found.</p>";
    }
    
    // Test contact requests
    echo "<h3>Pending Contact Requests</h3>";
    $contactQuery = "SELECT COUNT(*) as count FROM contact_requests WHERE donor_username = :username AND status = 'pending'";
    $stmt = $pdo->prepare($contactQuery);
    $stmt->execute(['username' => $username]);
    $contactCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Pending contact requests: $contactCount</p>";
    
    // Calculate total notifications
    $totalNotifications = count($reports) + count($events) + count($alerts) + $contactCount;
    echo "<h3>Total Notifications</h3>";
    echo "<p>Reports: " . count($reports) . "</p>";
    echo "<p>Events: " . count($events) . "</p>";
    echo "<p>Alerts: " . count($alerts) . "</p>";
    echo "<p>Contact Requests: $contactCount</p>";
    echo "<p><strong>Total: $totalNotifications</strong></p>";
    
} else {
    echo "<p><strong>❌ User location not found</strong></p>";
}

// Test the header query
echo "<hr>";
echo "<h3>Testing Header Query</h3>";

try {
    // Get user location first
    $userLocationQuery = "SELECT location FROM users WHERE username = :username";
    $userStmt = $pdo->prepare($userLocationQuery);
    $userStmt->execute(['username' => $username]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $userLocation = $userData['location'];
        
        // Count recent reports (last 7 days)
        $reportQuery = "SELECT COUNT(*) as count FROM report_issues 
                       WHERE location = :location 
                       AND DATE(date_reported) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $stmt = $pdo->prepare($reportQuery);
        $stmt->execute(['location' => $userLocation]);
        $reportCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count recent events (last 7 days)
        $eventQuery = "SELECT COUNT(*) as count FROM events 
                      WHERE location = :location 
                      AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $stmt = $pdo->prepare($eventQuery);
        $stmt->execute(['location' => $userLocation]);
        $eventCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count recent alerts (last 7 days)
        $alertQuery = "SELECT COUNT(*) as count FROM alerts 
                      WHERE location = :location 
                      AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $stmt = $pdo->prepare($alertQuery);
        $stmt->execute(['location' => $userLocation]);
        $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count pending contact requests
        $contactQuery = "SELECT COUNT(*) as count FROM contact_requests WHERE donor_username = :username AND status = 'pending'";
        $stmt = $pdo->prepare($contactQuery);
        $stmt->execute(['username' => $username]);
        $contactCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Also check for unread notifications in the notifications table
        $notifyQuery = "SELECT COUNT(*) as count FROM notifications WHERE recipient = :username AND read_status = 0";
        $stmt = $pdo->prepare($notifyQuery);
        $stmt->execute(['username' => $username]);
        $notifyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Sum all notification sources
        $notificationCount = $reportCount + $eventCount + $alertCount + $contactCount + $notifyCount;
        
        echo "<p><strong>Header query result:</strong> $notificationCount total notifications</p>";
        echo "<p>Reports: $reportCount, Events: $eventCount, Alerts: $alertCount, Contacts: $contactCount, Notifications: $notifyCount</p>";
        
        if ($notificationCount > 0) {
            echo "<p><strong>✅ Badge should show: " . ($notificationCount > 9 ? '9+' : $notificationCount) . "</strong></p>";
        } else {
            echo "<p><strong>ℹ️ No badge should show (count is 0)</strong></p>";
        }
        
    } else {
        echo "<p><strong>❌ User location not found for header query</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p><strong>❌ Header query error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='landingPageAfterLogin.php'>Go to Dashboard</a></p>";
echo "<p><a href='notify.php'>Go to Notifications Page</a></p>";
echo "<p><a href='test_notification_badges.php'>Go to Badge Test</a></p>";
?> 