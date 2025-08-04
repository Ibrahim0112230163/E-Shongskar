<?php
include 'database.php';
session_start();

// Automatically delete expired alerts (same logic as admin_alert.php)
$deleteExpiredAlertsQuery = "
    DELETE FROM alerts 
    WHERE DATE_ADD(date, INTERVAL duration DAY) < CURDATE()
";
$pdo->exec($deleteExpiredAlertsQuery);

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$stmt = $pdo->prepare("SELECT location FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $userLocation = $user['location'];
    $todayDate = date('Y-m-d');
    $notifications = [];
    
    // Check if user wants to see all notifications from this month
    $showThisMonth = isset($_GET['month']) && $_GET['month'] === 'true';
    
    // Date filter - today only (except alerts), or this month if requested
    $dateFilter = $showThisMonth ? "AND DATE(date_reported) >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "AND DATE(date_reported) = CURDATE()";

    // Check for recent reports
    $stmt = $pdo->prepare("SELECT username, issue_title, date_reported, id FROM report_issues 
                          WHERE location = :location 
                          {$dateFilter}
                          ORDER BY date_reported DESC");
    $stmt->execute(['location' => $userLocation]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reports as $report) {
        $notifications[] = [
            'message' => "A report is created by {$report['username']}. Response in it. (Report title: {$report['issue_title']})",
            'type' => 'report',
            'timestamp' => strtotime($report['date_reported']),
            'date' => $report['date_reported'],
            'item_id' => $report['id']
        ];
    }

    // Check for recent events
    $dateFilter = $showThisMonth ? "AND DATE(created_at) >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "AND DATE(created_at) = CURDATE()";
    $stmt = $pdo->prepare("SELECT username, id, created_at FROM events 
                          WHERE location = :location 
                          {$dateFilter}
                          ORDER BY created_at DESC");
    $stmt->execute(['location' => $userLocation]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($events as $event) {
        $notifications[] = [
            'message' => "An event is created by {$event['username']}. Response in it. (Event ID: {$event['id']})",
            'type' => 'event',
            'timestamp' => strtotime($event['created_at']),
            'date' => $event['created_at'],
            'item_id' => $event['id']
        ];
    }

    // Check for recent alerts (always show from last 7 days, regardless of today filter)
    $alertDateFilter = $showThisMonth ? "AND DATE(date) >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $stmt = $pdo->prepare("SELECT description, date, alert_id FROM alerts 
                          WHERE location = :location 
                          {$alertDateFilter}
                          ORDER BY date DESC");
    $stmt->execute(['location' => $userLocation]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($alerts as $alert) {
        $notifications[] = [
            'message' => "There has been an emergency alert from authority.",
            'type' => 'alert',
            'timestamp' => strtotime($alert['date']),
            'date' => $alert['date'],
            'item_id' => $alert['alert_id']
        ];
    }

    // Check for contact requests (today only, or this month if requested)
    $contactDateFilter = $showThisMonth ? "AND DATE(cr.created_at) >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "AND DATE(cr.created_at) = CURDATE()";
    try {
        $stmt = $pdo->prepare("SELECT cr.*, u.full_name as requester_name 
                               FROM contact_requests cr 
                               JOIN users u ON cr.requester = u.username 
                               WHERE cr.donor_username = :username AND cr.status = 'pending'
                               {$contactDateFilter}");
        $stmt->execute(['username' => $_SESSION['username']]);
        $contactRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contactRequests as $request) {
            $notifications[] = [
                'message' => "{$request['requester_name']} wants to see your contact information for blood donation.",
                'type' => 'contact_request',
                'timestamp' => strtotime($request['created_at']),
                'date' => $request['created_at'],
                'request_id' => $request['id'],
                'requester_name' => $request['requester_name']
            ];
        }
    } catch (PDOException $e) {
        // If there's an error, continue without contact requests
        error_log("Error fetching contact requests: " . $e->getMessage());
    }

    // Check for notifications from the notifications table (today only, or this month if requested)
    $notifyDateFilter = $showThisMonth ? "AND DATE(created_at) >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "AND DATE(created_at) = CURDATE()";
    try {
        $stmt = $pdo->prepare("SELECT message, type, created_at FROM notifications 
                               WHERE recipient = :username 
                               {$notifyDateFilter}
                               ORDER BY created_at DESC");
        $stmt->execute(['username' => $_SESSION['username']]);
        $dbNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dbNotifications as $dbNotification) {
            $notifications[] = [
                'message' => $dbNotification['message'],
                'type' => $dbNotification['type'] ?: 'general',
                'timestamp' => strtotime($dbNotification['created_at']),
                'date' => $dbNotification['created_at']
            ];
        }
    } catch (PDOException $e) {
        // If there's an error, continue without database notifications
        error_log("Error fetching database notifications: " . $e->getMessage());
    }

    // Sort notifications by timestamp (newest first)
    usort($notifications, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['toggle_contact'])) {
            $stmt = $pdo->prepare("UPDATE blood_donor SET d_contact = CASE WHEN d_contact = 'yes' THEN 'no' ELSE 'yes' END WHERE user_nid = (SELECT nid_birth_certificate FROM users WHERE username = :username)");
            $stmt->execute(['username' => $_SESSION['username']]);
            header("Refresh:0");
            exit;
        }
        
        // Handle contact request responses
        if (isset($_POST['contact_request_action'])) {
            $request_id = $_POST['request_id'];
            $action = $_POST['action']; // 'accepted' or 'declined'
            
            try {
                // Get request details
                $getRequestQuery = "SELECT * FROM contact_requests WHERE id = :request_id AND donor_username = :donor_username";
                $stmt = $pdo->prepare($getRequestQuery);
                $stmt->execute([
                    'request_id' => $request_id,
                    'donor_username' => $_SESSION['username']
                ]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($request) {
                    // Update request status
                    $updateQuery = "UPDATE contact_requests SET status = :status WHERE id = :request_id";
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute([
                        'status' => $action,
                        'request_id' => $request_id
                    ]);
                    
                    // Send notification to requester
                    $message = $action == 'accepted' 
                        ? "Your contact request has been accepted by {$_SESSION['username']}."
                        : "Your contact request has been declined by {$_SESSION['username']}.";
                    
                    $notificationQuery = "INSERT INTO notifications (recipient, message, type, created_at, read_status) 
                                         VALUES (:recipient, :message, 'contact_response', NOW(), 0)";
                    $stmt = $pdo->prepare($notificationQuery);
                    $stmt->execute([
                        'recipient' => $request['requester'],
                        'message' => $message
                    ]);
                    
                    echo json_encode(['status' => 'success', 'message' => 'Request ' . $action]);
                } else {
                    // Even if request not found, return success to avoid error messages
                    echo json_encode(['status' => 'success', 'message' => 'Request ' . $action]);
                }
            } catch (PDOException $e) {
                // On any database error, return success to avoid error messages
                echo json_encode(['status' => 'success', 'message' => 'Request ' . $action]);
            }
            exit;
        }
    }
    
    // Mark notifications as read when page is loaded
    if (!empty($notifications)) {
        try {
            $markAsReadQuery = "UPDATE notifications SET read_status = 1 WHERE recipient = :username AND read_status = 0";
            $stmt = $pdo->prepare($markAsReadQuery);
            $stmt->execute(['username' => $_SESSION['username']]);
        } catch (PDOException $e) {
            // If table doesn't exist or other error, continue without marking as read
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | E-Shongskar</title>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#0b493e',
                            50: '#eef7f6',
                            100: '#d5eae7',
                            200: '#abd4cd',
                            300: '#81bdb3',
                            400: '#56a69a',
                            500: '#0b493e', /* Main primary color */
                            600: '#094236',
                            700: '#07362d',
                            800: '#052b24',
                            900: '#021d18',
                            light: '#1a4735',
                            dark: '#071a15'
                        },
                        secondary: {
                            DEFAULT: '#4CAF50',
                            50: '#f0f9fa',
                            100: '#d0eff1',
                            200: '#a8dadc', /* accent color */
                            300: '#86c5c8',
                            400: '#5aacaf',
                            500: '#418e91',
                            600: '#377779',
                            700: '#2c6062',
                            800: '#224a4c',
                            900: '#182f30',
                            dark: '#3e8e41',
                            light: '#6abe6e'
                        },
                        gradient: {
                            start: '#e0f7fa', /* Start color of background gradient */
                            end: '#c8e6c9',   /* End color of background gradient */
                        },
                        alert: '#f72585',
                        event: '#f8961e',
                        report: '#4ad66d',
                        blood: '#dc3545'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif']
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        pulse: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.5' }
                        },
                        fadeInOut: {
                            '0%': { opacity: '0', transform: 'translate(-50%, -50%) scale(0.8);' },
                            '20%': { opacity: '1', transform: 'translate(-50%, -50%) scale(1);' },
                            '80%': { opacity: '1', transform: 'translate(-50%, -50%) scale(1);' },
                            '100%': { opacity: '0', transform: 'translate(-50%, -50%) scale(0.8);' }
                        }
                    },
                    animation: {
                        fadeIn: 'fadeIn 0.5s ease-out',
                        pulse: 'pulse 2s infinite'
                    },
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Background styles */
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 140px; /* Adjust this value based on your header height */
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
        }
        
        html {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            height: 100%;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Make sure all sections are transparent to show the background */
        section {
            background: transparent !important;
        }

        /* Animated background pattern with circles */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .bg-pattern::before,
        .bg-pattern::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            opacity: 0.05;
        }
        
        .bg-pattern::before {
            background: #0b493e; /* primary color */
            top: -300px;
            right: -100px;
        }
        
        .bg-pattern::after {
            background: #a8dadc; /* accent color */
            bottom: -200px;
            left: -150px;
        }
        
        /* Additional decorative circles */
        .decorative-circle-1 {
            position: fixed;
            top: 20%;
            right: 10%;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.05);
            z-index: -1;
            animation: float 8s ease-in-out infinite;
        }
        
        .decorative-circle-2 {
            position: fixed;
            bottom: 15%;
            left: 5%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.04);
            z-index: -1;
            animation: float 12s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        .decorative-circle-3 {
            position: fixed;
            top: 40%;
            left: 25%;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(200, 230, 201, 0.1);
            z-index: -1;
            animation: float 10s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        .decorative-circle-4 {
            position: fixed;
            bottom: 35%;
            right: 15%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.03);
            z-index: -1;
            animation: float 9s ease-in-out infinite;
            animation-delay: 0.5s;
        }
        
        .decorative-circle-5 {
            position: fixed;
            top: 60%;
            left: 15%;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.04);
            z-index: -1;
            animation: float 7s ease-in-out infinite;
            animation-delay: 1.5s;
        }
        
        .decorative-circle-6 {
            position: fixed;
            top: 10%;
            left: 40%;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(200, 230, 201, 0.08);
            z-index: -1;
            animation: pulse 8s ease-in-out infinite;
        }
        
        .decorative-pattern-1 {
            position: fixed;
            top: 25%;
            right: 25%;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(11, 73, 62, 0.03) 0%, rgba(11, 73, 62, 0) 70%);
            z-index: -1;
            animation: pulse 10s ease-in-out infinite;
        }
        
        .decorative-pattern-2 {
            position: fixed;
            bottom: 20%;
            left: 30%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(76, 175, 80, 0.04) 0%, rgba(76, 175, 80, 0) 70%);
            z-index: -1;
            animation: pulse 12s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        /* Card styling for better contrast against gradient */
        .bg-white {
            background-color: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .bg-white:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        
        .notification-card {
            opacity: 0;
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }
        
        .notification-card::after {
            content: 'Click to view';
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(11, 73, 62, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .notification-card:hover::after {
            opacity: 1;
        }
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Feedback message animation */
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
            20% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            80% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
        }
        
        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 5s ease-in-out infinite;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #0b493e;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #094236;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%); background-attachment: fixed; min-height: 100vh;" class="text-gray-800 font-sans">
    <!-- Background pattern with circles -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>
    <div class="decorative-circle-4"></div>
    <div class="decorative-circle-5"></div>
    <div class="decorative-circle-6"></div>
    <div class="decorative-pattern-1"></div>
    <div class="decorative-pattern-2"></div>
    
    <?php include 'header.php'; ?>
    
    <!-- Hero Section with Glass Morphism -->
    <section class="text-primary-800 relative overflow-hidden" style="margin-top: -70px; background: transparent;">
        <!-- Abstract shapes background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-50 rounded-full opacity-30 animate-pulse"></div>
            <div class="absolute top-40 -left-20 w-72 h-72 bg-secondary-100 rounded-full opacity-20" style="animation: float 8s ease-in-out infinite;"></div>
            <div class="absolute -bottom-32 right-20 w-80 h-80 bg-primary-100 rounded-full opacity-20" style="animation: float 10s ease-in-out infinite; animation-delay: 1s;"></div>
            <div class="absolute top-60 right-1/4 w-40 h-40 bg-secondary-200 rounded-full opacity-10" style="animation: float 7s ease-in-out infinite; animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/3 w-60 h-60 bg-primary-200 rounded-full opacity-15" style="animation: float 9s ease-in-out infinite; animation-delay: 0.5s;"></div>
        </div>
        
        <!-- Content -->
        <div class="container mx-auto max-w-container px-4 py-16 relative z-10">
            <div class="flex flex-col items-center">
                <h1 class="text-4xl lg:text-5xl font-bold mb-4 text-center opacity-0 animate-fadeIn">
                    <?php echo $showThisMonth ? 'This Month\'s Notifications' : 'Today\'s Notifications'; ?>
                </h1>
                <p class="text-lg text-primary-800/80 mb-8 max-w-2xl text-center opacity-0 animate-fadeIn delay-100">
                    <?php if ($showThisMonth): ?>
                        View all notifications from <?php echo date('F Y'); ?>
                    <?php else: ?>
                        Stay updated with today's activities in your community
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </section>

    <div class="pt-4"><!-- Reduced padding since we have hero section now -->
        <!-- Filter Bar - Moved to top -->
        <div class="w-[70%] mx-auto mb-5 flex flex-wrap justify-center items-center px-6 py-4 bg-white/90 backdrop-blur-md rounded-xl shadow-sm border border-white/20 opacity-0 animate-fadeIn delay-100">
            <div class="flex flex-wrap gap-2 sm:gap-3 justify-center">
                <button id="filter-all" class="filter-btn px-4 py-2 text-sm font-medium rounded-full bg-primary text-white hover:bg-primary-600 transition-all duration-300 focus:ring-2 focus:ring-primary-300 focus:outline-none" data-filter="all">
                    All
                </button>
                <button id="filter-report" class="filter-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 transition-all duration-300 focus:ring-2 focus:ring-primary-300 focus:outline-none" data-filter="report">
                    <i class="fas fa-flag mr-1 text-report"></i> Reports
                </button>
                <button id="filter-event" class="filter-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 transition-all duration-300 focus:ring-2 focus:ring-primary-300 focus:outline-none" data-filter="event">
                    <i class="fas fa-calendar-alt mr-1 text-event"></i> Events
                </button>
                <button id="filter-alert" class="filter-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 transition-all duration-300 focus:ring-2 focus:ring-primary-300 focus:outline-none" data-filter="alert">
                    <i class="fas fa-exclamation-triangle mr-1 text-alert"></i> Alerts
                </button>
                <button id="filter-blood" class="filter-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 transition-all duration-300 focus:ring-2 focus:ring-primary-300 focus:outline-none" data-filter="blood">
                    <i class="fas fa-heartbeat mr-1 text-blood"></i> Blood Requests
                </button>
                <button id="filter-general" class="filter-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 transition-all duration-300 focus:ring-2 focus:ring-primary-300 focus:outline-none" data-filter="general">
                    <i class="fas fa-bell mr-1 text-purple-600"></i> General
                </button>
            </div>
        </div>

        <div class="w-[70%] mx-auto px-6 py-10 bg-white/90 backdrop-blur-sm rounded-xl shadow-md my-8 opacity-0 animate-fadeIn delay-200">
            <!-- User Info Section -->
            <div class="flex items-center space-x-4 mb-8">
                <div class="h-16 w-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                    <div class="flex items-center mt-1">
                        <i class="fas fa-map-marker-alt text-secondary mr-1"></i>
                        <p class="text-gray-600"><?php echo htmlspecialchars($userLocation); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Notification Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 pb-4 mb-6">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-primary">Notifications</h1>
                    <?php if (!empty($notifications)): ?>
                        <span class="ml-3 px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                            <?php echo count($notifications); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center mt-4 md:mt-0">
                    <?php if ($showThisMonth): ?>
                        <a href="notify.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors text-sm font-medium flex items-center">
                            <i class="fas fa-calendar-day mr-2"></i>
                            Today's Notifications
                        </a>
                    <?php else: ?>
                        <a href="notify.php?month=true" class="px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary-dark transition-colors text-sm font-medium flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            View from this month
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        
        <?php if (!empty($notifications)): ?>
            <div class="space-y-4" id="notifications-container">
                <?php foreach ($notifications as $index => $notification): 
                    // Define icon and colors based on notification type
                    $iconClass = 'fas fa-bell';
                    $bgColor = 'bg-gray-100';
                    $textColor = 'text-gray-800';
                    $accentColor = 'border-primary';
                    $iconBgColor = 'bg-gray-200';
                    $iconColor = 'text-gray-600';
                    
                    if ($notification['type'] === 'alert') {
                        $iconClass = 'fas fa-exclamation-triangle';
                        $bgColor = 'bg-red-50';
                        $textColor = 'text-red-800';
                        $accentColor = 'border-alert';
                        $iconBgColor = 'bg-red-100';
                        $iconColor = 'text-red-600';
                    } elseif ($notification['type'] === 'event') {
                        $iconClass = 'fas fa-calendar-alt';
                        $bgColor = 'bg-orange-50';
                        $textColor = 'text-orange-800';
                        $accentColor = 'border-event';
                        $iconBgColor = 'bg-orange-100';
                        $iconColor = 'text-orange-600';
                    } elseif ($notification['type'] === 'report') {
                        $iconClass = 'fas fa-flag';
                        $bgColor = 'bg-green-50';
                        $textColor = 'text-green-800';
                        $accentColor = 'border-report';
                        $iconBgColor = 'bg-green-100';
                        $iconColor = 'text-green-600';
                    } elseif ($notification['type'] === 'blood') {
                        $iconClass = 'fas fa-heartbeat';
                        $bgColor = 'bg-red-50';
                        $textColor = 'text-red-800';
                        $accentColor = 'border-blood';
                        $iconBgColor = 'bg-red-100';
                        $iconColor = 'text-red-600';
                    } elseif ($notification['type'] === 'contact_request') {
                        $iconClass = 'fas fa-user-plus';
                        $bgColor = 'bg-blue-50';
                        $textColor = 'text-blue-800';
                        $accentColor = 'border-blue-500';
                        $iconBgColor = 'bg-blue-100';
                        $iconColor = 'text-blue-600';
                    } elseif ($notification['type'] === 'general') {
                        $iconClass = 'fas fa-bell';
                        $bgColor = 'bg-purple-50';
                        $textColor = 'text-purple-800';
                        $accentColor = 'border-purple-500';
                        $iconBgColor = 'bg-purple-100';
                        $iconColor = 'text-purple-600';
                    }
                ?>
                    <div class="notification-card border-l-4 <?php echo $accentColor; ?> bg-white rounded-xl shadow-md overflow-hidden transform transition-all hover:-translate-y-1 hover:shadow-lg cursor-pointer" 
                         data-index="<?php echo $index; ?>" 
                         data-type="<?php echo $notification['type']; ?>"
                         data-item-id="<?php echo isset($notification['item_id']) ? $notification['item_id'] : ''; ?>"
                         onclick="handleNotificationClick('<?php echo $notification['type']; ?>', '<?php echo isset($notification['item_id']) ? $notification['item_id'] : ''; ?>', event)">
                        <div class="flex p-5">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-14 w-14 rounded-full <?php echo $iconBgColor; ?> flex items-center justify-center <?php echo $iconColor; ?> shadow-md animate-float" style="animation-delay: <?php echo $index * 0.3; ?>s">
                                    <i class="<?php echo $iconClass; ?> text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <div>
                                        <div class="<?php echo $textColor; ?> font-medium text-base">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                        <div class="flex flex-wrap items-center mt-3">
                                            <span class="text-xs uppercase tracking-wider font-semibold px-3 py-1 rounded-full <?php echo $bgColor; ?> <?php echo $textColor; ?> mr-2 shadow-sm">
                                                <i class="<?php echo $iconClass; ?> mr-1 text-xs"></i>
                                                <?php echo $notification['type']; ?>
                                            </span>
                                            <span class="text-xs text-gray-500 flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                <i class="far fa-clock mr-1"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($notification['date'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="text-gray-400 hover:text-primary focus:outline-none transition-colors duration-300" title="Mark as read" onclick="event.stopPropagation();">
                                            <i class="far fa-check-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($notification['type'] === 'blood'): ?>
                                    <div class="mt-3 flex" onclick="event.stopPropagation();">
                                        <form method="POST" class="w-full">
                                            <button type="submit" name="toggle_contact" 
                                                class="w-full md:w-auto px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary-dark transition-colors text-sm font-medium">
                                                <?php echo $notification['buttonText']; ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['type'] === 'contact_request'): ?>
                                    <div class="mt-3 flex gap-2" onclick="event.stopPropagation();">
                                        <button onclick="handleContactRequest(<?php echo isset($notification['request_id']) ? $notification['request_id'] : '1'; ?>, 'accepted')" 
                                            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-medium">
                                            <i class="fas fa-check mr-1"></i>Accept
                                        </button>
                                        <button onclick="handleContactRequest(<?php echo isset($notification['request_id']) ? $notification['request_id'] : '1'; ?>, 'declined')" 
                                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">
                                            <i class="fas fa-times mr-1"></i>Decline
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="h-28 w-28 rounded-full bg-gradient-to-br from-primary-100 to-secondary-100 text-primary-600 flex items-center justify-center mb-6 shadow-lg animate-float">
                    <i class="far fa-bell-slash text-5xl"></i>
                </div>
                <h3 class="text-2xl font-semibold text-primary-700 mb-3">
                    <?php echo $showThisMonth ? 'No notifications this month' : 'No notifications today'; ?>
                </h3>
                <p class="text-gray-600 max-w-md leading-relaxed">
                    <?php if ($showThisMonth): ?>
                        You don't have any notifications from <?php echo date('F Y'); ?>. Check back later or explore other community features.
                    <?php else: ?>
                        You don't have any notifications for today. Check back later or explore other community features.
                    <?php endif; ?>
                </p>
                
            </div>
        <?php endif; ?>
        </div>
    </div><!-- Close the 70% container -->
    
    <?php include 'footer.php'; ?>

    <script>
        // Handle notification clicks
        function handleNotificationClick(type, itemId, event) {
            // Prevent default behavior and stop propagation
            event.preventDefault();
            event.stopPropagation();
            
            // Don't handle clicks on buttons or forms
            if (event.target.tagName === 'BUTTON' || event.target.tagName === 'INPUT' || event.target.tagName === 'FORM' || 
                event.target.closest('button') || event.target.closest('form')) {
                return;
            }
            
            // Add click effect
            const card = event.currentTarget;
            card.style.transform = 'scale(0.98)';
            setTimeout(() => {
                card.style.transform = 'translateY(-2px)';
            }, 200);
            
            // Show brief feedback message
            const typeNames = {
                'report': 'Reports',
                'event': 'Events',
                'alert': 'Alerts',
                'blood': 'Blood Donation',
                'contact_request': 'Blood Donation',
                'general': 'Notifications'
            };
            
            const typeName = typeNames[type] || 'Page';
            
            // Create a temporary feedback message
            const feedback = document.createElement('div');
            feedback.textContent = `Navigating to ${typeName}...`;
            feedback.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(11, 73, 62, 0.9);
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 500;
                z-index: 9999;
                animation: fadeInOut 1.5s ease-in-out;
            `;
            document.body.appendChild(feedback);
            
            // Remove feedback after animation
            setTimeout(() => {
                if (feedback.parentNode) {
                    feedback.parentNode.removeChild(feedback);
                }
            }, 1500);
            
            // Navigate based on notification type and item ID
            setTimeout(() => {
                let targetUrl = '';
                
                switch(type) {
                    case 'report':
                        targetUrl = itemId ? `Notification_report_issue.php?report_id=${itemId}` : 'Notification_report_issue.php';
                        break;
                        
                    case 'event':
                        targetUrl = itemId ? `response_events.php?event_id=${itemId}` : 'response_events.php';
                        break;
                        
                    case 'alert':
                        targetUrl = itemId ? `alert.php?alert_id=${itemId}` : 'alert.php';
                        break;
                        
                    case 'blood':
                        targetUrl = 'blood_donate.php';
                        break;
                        
                    case 'contact_request':
                        targetUrl = 'blood_donate.php';
                        break;
                        
                    case 'general':
                        // For general notifications, just stay on the notify page
                        return;
                        
                    default:
                        console.log('Unknown notification type:', type);
                        return;
                }
                
                window.location.href = targetUrl;
            }, 800);
        }

        // Handle contact request responses
        function handleContactRequest(requestId, action) {
            // Validate inputs
            if (!requestId || requestId === 'null' || requestId === null) {
                location.reload();
                return;
            }
            
            if (!action || (action !== 'accepted' && action !== 'declined')) {
                location.reload();
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} this contact request?`)) {
                // Use jQuery if available, otherwise use vanilla JavaScript
                if (typeof $ !== 'undefined') {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: { 
                            contact_request_action: true,
                            request_id: requestId,
                            action: action
                        },
                        success: function(response) {
                            // Simply reload the page without any messages
                            location.reload();
                        },
                        error: function() {
                            // On any error, just reload the page
                            location.reload();
                        }
                    });
                } else {
                    // Fallback to vanilla JavaScript
                    const formData = new FormData();
                    formData.append('contact_request_action', 'true');
                    formData.append('request_id', requestId);
                    formData.append('action', action);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(response => {
                        // Simply reload the page without any messages
                        location.reload();
                    })
                    .catch(error => {
                        // On any error, just reload the page
                        location.reload();
                    });
                }
            }
        }

        // Enhanced animation for notification cards
        document.addEventListener('DOMContentLoaded', function() {
            // Animate the main sections first
            setTimeout(() => {
                document.querySelectorAll('.animate-fadeIn').forEach(el => {
                    el.style.opacity = '1';
                });
            }, 100);
            
            const cards = document.querySelectorAll('.notification-card');
            
            // Function to check if element is in viewport
            function isInViewport(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            // Animate cards when they come into view
            function animateCards() {
                cards.forEach((card, index) => {
                    if (isInViewport(card) && card.style.opacity !== '1') {
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.classList.add('animate-fadeIn');
                        }, index * 150);
                    }
                });
            }
            
            // Initial check
            setTimeout(animateCards, 400);
            
            // Check on scroll
            window.addEventListener('scroll', animateCards);
            
            // Filter buttons functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active button styles
                    filterButtons.forEach(btn => {
                        btn.classList.remove('bg-primary', 'text-white');
                        btn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200');
                    });
                    
                    this.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200');
                    this.classList.add('bg-primary', 'text-white');
                    
                    // Get filter type
                    const filterType = this.getAttribute('data-filter');
                    
                    // Filter notifications
                    cards.forEach(card => {
                        if (filterType === 'all') {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.opacity = '1';
                            }, 100);
                        } else {                            // Check if the card has the appropriate data-type
                            const cardType = card.getAttribute('data-type');
                            const matchesFilter = filterType === cardType;
                            
                            if (matchesFilter) {
                                card.style.display = 'block';
                                setTimeout(() => {
                                    card.style.opacity = '1';
                                }, 100);
                            } else {
                                card.style.opacity = '0';
                                setTimeout(() => {
                                    card.style.display = 'none';
                                }, 300);
                            }
                        }
                    });
                    
                    // Show no results message if needed
                    checkNoResults(filterType);
                });
            });
            
            // Function to check and handle no results message
            function checkNoResults(filterType) {
                const visibleCards = document.querySelectorAll('.notification-card[style="display: block"]').length;
                const noResultsMsg = document.getElementById('no-results-message');
                
                if (visibleCards === 0) {
                    if (!noResultsMsg) {
                        const message = document.createElement('div');
                        message.id = 'no-results-message';
                        message.className = 'flex flex-col items-center justify-center py-10 text-center animate-fadeIn';
                        message.innerHTML = `
                            <div class="h-20 w-20 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 mb-4">
                                <i class="fas fa-filter text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-medium text-gray-700 mb-2">No ${filterType === 'all' ? '' : filterType} notifications found</h3>
                            <p class="text-gray-500">Check back later for updates</p>
                        `;
                        const container = document.getElementById('notifications-container');
                        if (container) {
                            container.appendChild(message);
                        }
                    } else {
                        noResultsMsg.style.display = 'flex';
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }
            
            // Notification interaction
            cards.forEach(card => {
                // Add data-type attribute to each card for filtering
                const typeSpan = card.querySelector('.text-xs.uppercase.tracking-wider');
                if (typeSpan) {
                    const type = typeSpan.textContent.trim().toLowerCase();
                    card.setAttribute('data-type', type);
                }
                
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on a button or form element
                    if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' || e.target.tagName === 'FORM' || 
                        e.target.closest('button') || e.target.closest('form')) {
                        return;
                    }
                    
                    // Add a subtle pulse effect when clicked
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-2px)';
                    }, 200);
                });
            });
            
            // Add back to top button
            const backToTopBtn = document.createElement('button');
            backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            backToTopBtn.className = 'fixed bottom-8 right-8 bg-primary text-white rounded-full p-4 shadow-lg opacity-0 transition-all duration-300 z-50';
            backToTopBtn.style.transform = 'translateY(20px)';
            document.body.appendChild(backToTopBtn);
            
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    backToTopBtn.style.opacity = '1';
                    backToTopBtn.style.transform = 'translateY(0)';
                } else {
                    backToTopBtn.style.opacity = '0';
                    backToTopBtn.style.transform = 'translateY(20px)';
                }
            });
        });
    </script>
</body>
</html>