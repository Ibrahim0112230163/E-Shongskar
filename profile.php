<?php
// Start session at the very top
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'user_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Concatenate address (Division, Location, Street, House No) for display
$address = $user['division'] . ', ' . $user['location'];
if (!empty($user['street']) && !empty($user['house_no'])) {
    $address .= ', ' . $user['street'] . ', House No: ' . $user['house_no'];
} else {
    $address .= ', House No: ' . $user['house_no'];
}

// Fetch report descriptions for the logged-in user
$reportStmt = $pdo->prepare("SELECT id, description, status, date_reported FROM report_issues WHERE username = :username ORDER BY date_reported DESC");
$reportStmt->execute(['username' => $_SESSION['username']]);
$reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch events for the logged-in user
$eventStmt = $pdo->prepare("SELECT * FROM events WHERE username = :username ORDER BY created_at DESC");
$eventStmt->execute(['username' => $_SESSION['username']]);
$events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if the user is already a blood donor
$stmt = $pdo->prepare("SELECT * FROM blood_donor WHERE user_nid = :user_nid");
$stmt->execute([':user_nid' => $user['nid_birth_certificate']]);
$donationRecord = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission for removing blood donation record
if (isset($_POST['remove_record'])) {
    $stmt = $pdo->prepare("DELETE FROM blood_donor WHERE user_nid = :user_nid");
    $stmt->execute([':user_nid' => $user['nid_birth_certificate']]);
    $donationRecord = null;
}

// Handle form submission for donating blood
if (isset($_POST['donate_blood'])) {
    $stmt = $pdo->prepare("INSERT INTO blood_donor (user_nid, blood_group, contact_no, next_date) VALUES (:user_nid, :blood_group, :contact_no, :next_date)");
    $nextDate = date('Y-m-d', strtotime('+3 months'));
    $stmt->execute([
        ':user_nid' => $user['nid_birth_certificate'],
        ':blood_group' => $user['blood_group'],
        ':contact_no' => $user['contact_no'],
        ':next_date' => $nextDate
    ]);
    $donationRecord = ['next_date' => $nextDate];
}

if (isset($_POST['delete_report'])) {
    $report_id = $_POST['report_id'];
    $stmt = $pdo->prepare("DELETE FROM report_issues WHERE id = :id AND username = :username");
    $stmt->execute([':id' => $report_id, ':username' => $_SESSION['username']]);
    header("Location: profile.php");
    exit;
}

if (isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = :event_id");
    $stmt->execute([':event_id' => $event_id]);
    header("Location: profile.php");
    exit;
}

// Handle form submission for marking event as succeeded
if (isset($_POST['mark_succeeded'])) {
    $event_id = $_POST['event_id'];
    $finished_date = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO succeed_events (event_id, done_u, done_v, done_a, finished_date) 
                           VALUES (:event_id, 'yes', 'no', 'no', :finished_date)");
    $stmt->execute([
        ':event_id' => $event_id,
        ':finished_date' => $finished_date
    ]);
    header("Location: profile.php");
    exit;
}

// Fetch enhanced profile information
$enhancedStmt = $pdo->prepare("SELECT * FROM enhanced_profiles WHERE username = :username");
$enhancedStmt->execute([':username' => $user['username']]);
$enhancedProfile = $enhancedStmt->fetch(PDO::FETCH_ASSOC);

// Handle profile picture upload
if (isset($_POST['submit_enhanced_profile'])) {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_pictures/';

        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $filename = uniqid($_SESSION['username'] . '_', true) . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $destination = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
            // Check if record exists
            $checkStmt = $pdo->prepare("SELECT id FROM enhanced_profiles WHERE username = :username");
            $checkStmt->execute([':username' => $_SESSION['username']]);

            if ($checkStmt->fetch()) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE enhanced_profiles SET profile_picture = :picture WHERE username = :username");
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO enhanced_profiles (username, profile_picture) VALUES (:username, :picture)");
            }

            $stmt->execute([
                ':username' => $_SESSION['username'],
                ':picture' => $destination
            ]);

            // Refresh the enhanced profile data
            $enhancedStmt = $pdo->prepare("SELECT * FROM enhanced_profiles WHERE username = :username");
            $enhancedStmt->execute([':username' => $user['username']]);
            $enhancedProfile = $enhancedStmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    // Handle social media link submission
    if (!empty($_POST['social_media_platform']) && !empty($_POST['social_media_link'])) {
        $stmt = $pdo->prepare("INSERT INTO social_media (username, platform, link) VALUES (:username, :platform, :link)");
        $stmt->execute([
            ':username' => $_SESSION['username'],
            ':platform' => $_POST['social_media_platform'],
            ':link' => $_POST['social_media_link']
        ]);
    }

    header("Location: profile.php");
    exit;
}

// Fetch social media information
$socialMediaStmt = $pdo->prepare("SELECT * FROM social_media WHERE username = :username");
$socialMediaStmt->execute([':username' => $user['username']]);
$socialMediaLinks = $socialMediaStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for voluntary section
if (isset($_POST['submit_voluntary'])) {
    $selectedCategory = $_POST['category'];
    $stmt = $pdo->prepare("INSERT INTO your_voluntary (username, category) VALUES (:username, :category)");
    $stmt->execute([
        ':username' => $_SESSION['username'],
        ':category' => $selectedCategory
    ]);
    $message = "Thank you for enrolling in the '$selectedCategory' category!";
}

// Handle removal of all records for the user
if (isset($_POST['remove_all'])) {
    $deleteStmt = $pdo->prepare("DELETE FROM your_voluntary WHERE username = :username");
    $deleteStmt->execute([':username' => $_SESSION['username']]);
    $message = "All your enrolled categories have been removed.";
}

// Fetch all enrolled categories for the user
$categoriesStmt = $pdo->prepare("SELECT id, category FROM your_voluntary WHERE username = :username");
$categoriesStmt->execute([':username' => $_SESSION['username']]);
$enrolledCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$is_verified = !empty($user['verified']) ? true : false;

// Calculate user statistics
$totalReports = count($reports);
$solvedReports = count(array_filter($reports, function ($report) {
    return $report['status'] === 'Solved';
}));
$totalEvents = count($events);
$succeededEvents = 0;

foreach ($events as $event) {
    $stmt = $pdo->prepare("SELECT * FROM succeed_events WHERE event_id = :event_id");
    $stmt->execute([':event_id' => $event['id']]);
    if ($stmt->fetch()) {
        $succeededEvents++;
    }
}

// Calculate user score/level
$userScore = ($totalReports * 10) + ($solvedReports * 20) + ($totalEvents * 15) + ($succeededEvents * 25) + (count($enrolledCategories) * 5);
$userLevel = floor($userScore / 100) + 1;
$progressToNextLevel = $userScore % 100;

// Fetch recent activities
$recentActivities = [];
foreach ($reports as $report) {
    $recentActivities[] = [
        'type' => 'report',
        'title' => 'Report Submitted',
        'description' => substr($report['description'], 0, 50) . '...',
        'date' => $report['date_reported'],
        'status' => $report['status']
    ];
}

foreach ($events as $event) {
    $recentActivities[] = [
        'type' => 'event',
        'title' => 'Event Created',
        'description' => substr($event['event_description'], 0, 50) . '...',
        'date' => $event['created_at']
    ];
}

// Sort activities by date
usort($recentActivities, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$recentActivities = array_slice($recentActivities, 0, 5);

// Pagination function
function paginate($items, $perPage = 4, $page = 1)
{
    $totalItems = count($items);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages));

    $offset = ($page - 1) * $perPage;
    $paginatedItems = array_slice($items, $offset, $perPage);

    return [
        'items' => $paginatedItems,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

// Current page for reports and events
$reportPage = isset($_GET['report_page']) ? (int)$_GET['report_page'] : 1;
$eventPage = isset($_GET['event_page']) ? (int)$_GET['event_page'] : 1;

// Paginate reports and events
$paginatedReports = paginate($reports, 4, $reportPage);
$paginatedEvents = paginate($events, 4, $eventPage);

// Helper function for status badges
function getStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'in progress':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'solved':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'cancelled':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - E-Shongskar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#0f2e24',
                            light: '#1a4735'
                        },
                        secondary: {
                            DEFAULT: '#4CAF50',
                            dark: '#3e8e41'
                        },
                        accent: '#FF6B6B'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif']
                    },
                    borderRadius: {
                        'custom': '12px',
                        'custom-lg': '16px'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Background styles */
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 110px; /* Adjust this value based on your header height */
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden; /* Prevent horizontal scrollbars from animated elements */
        }
        
        html {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            height: 100%;
            overflow-x: hidden; /* Ensure the pattern isn't cut off */
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Make sure all sections are transparent to show the background */
        section {
            background: transparent !important;
        }
        
        /* Animated background pattern with circles - Exactly as in response_events.php */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        
        .bg-pattern::before,
        .bg-pattern::after {
            content: '';
            position: absolute;
            width: 1200px;
            height: 1200px;
            border-radius: 50%;
            opacity: 0.08;
        }
        
        .bg-pattern::before {
            background: #0b493e; /* primary color */
            top: -600px;
            right: -200px;
            animation: pulseAndRotate 25s infinite alternate;
        }
        
        .bg-pattern::after {
            background: #a8dadc; /* accent color */
            bottom: -400px;
            left: -300px;
            animation: pulseAndRotate 30s infinite alternate-reverse;
        }
        
        /* Additional decorative circles */
        .decorative-circle-1 {
            position: fixed;
            top: 20%;
            right: 10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.15);
            z-index: -1;
            transform-origin: center;
            animation: pulse 10s infinite alternate;
        }
        
        .decorative-circle-2 {
            position: fixed;
            bottom: 15%;
            left: 5%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.12);
            z-index: -1;
            transform-origin: center;
            animation: pulse 12s infinite alternate-reverse;
        }
        
        .decorative-circle-3 {
            position: fixed;
            top: 40%;
            left: 25%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(200, 230, 201, 0.2);
            z-index: -1;
            transform-origin: center;
            animation: pulse 15s infinite alternate;
        }
        
        @keyframes pulseAndRotate {
            0% { transform: scale(1) rotate(0deg); opacity: 0.08; }
            50% { transform: scale(1.05) rotate(5deg); opacity: 0.10; }
            100% { transform: scale(0.95) rotate(-5deg); opacity: 0.06; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }

        /* Custom animations - Identical to reference page */
        @keyframes float {
            0% { transform: translateY(0px) translateX(0px); }
            25% { transform: translateY(-10px) translateX(5px); }
            50% { transform: translateY(-15px) translateX(-5px); }
            75% { transform: translateY(-5px) translateX(-8px); }
            100% { transform: translateY(0px) translateX(0px); }
        }
        
        .animate-float {
            animation: float 5s ease-in-out infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            transition: stroke-dasharray 0.35s;
            transform-origin: 50% 50%;
        }

        .activity-item {
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background-color: rgba(76, 175, 80, 0.1);
            transform: translateX(5px);
        }
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }

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
    </style>
</head>

<body style="background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%); background-attachment: fixed; min-height: 100vh;" class="text-gray-800 font-sans">
    <!-- Background pattern with circles -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>
    
    <!-- Abstract shapes background - Identical to reference page -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-50 rounded-full opacity-30 animate-pulse"></div>
        <div class="absolute top-40 -left-20 w-72 h-72 bg-green-100 rounded-full opacity-20" style="animation: float 8s ease-in-out infinite;"></div>
        <div class="absolute -bottom-32 right-20 w-80 h-80 bg-teal-100 rounded-full opacity-20" style="animation: float 10s ease-in-out infinite; animation-delay: 1s;"></div>
        <div class="absolute top-60 right-1/4 w-40 h-40 bg-green-200 rounded-full opacity-10" style="animation: float 7s ease-in-out infinite; animation-delay: 2s;"></div>
        <div class="absolute bottom-20 left-1/3 w-60 h-60 bg-teal-200 rounded-full opacity-15" style="animation: float 9s ease-in-out infinite; animation-delay: 0.5s;"></div>
    </div>
    
    <?php include 'header.php'; ?>

    <div class="pt-6"><!-- Added padding to separate header from content -->
        <div class="container mx-auto px-4 py-8 w-[70%]"><!-- Set width to 70% -->
            <!-- Header Section -->
            <div class="mb-8 opacity-0 animate-fadeIn">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-primary-500">My Profile</h1>
                        <p class="text-gray-700 mt-2">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
                    </div>
                    <div class="flex gap-4">
                        <a href="dashboard.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-6 py-3 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                            <i class="fas fa-arrow-left"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Profile Info -->
                <div class="lg:col-span-1 opacity-0 animate-fadeIn delay-100">
                    <!-- Profile Card -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-6 card-hover">
                        <div class="text-center mb-6">
                            <div class="relative inline-block">
                                <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-secondary mx-auto mb-4">
                                    <?php if ($enhancedProfile && !empty($enhancedProfile['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($enhancedProfile['profile_picture']); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-primary-light text-white text-4xl">
                                            <span><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_verified): ?>
                                    <div class="absolute -top-2 -right-2 bg-secondary text-white rounded-full p-2">
                                        <i class="fas fa-check"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h2 class="text-2xl font-bold text-primary mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                            <p class="text-gray-600 mb-4">@<?php echo htmlspecialchars($user['username']); ?></p>

                            <!-- Level and Progress -->
                            <div class="mb-4">
                                <div class="flex items-center justify-center mb-2">
                                    <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-medium">Level <?php echo $userLevel; ?></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                    <div class="bg-secondary h-2 rounded-full" style="width: <?php echo $progressToNextLevel; ?>%"></div>
                                </div>
                                <p class="text-xs text-gray-500"><?php echo $progressToNextLevel; ?>% to next level</p>
                            </div>

                            <!-- Social Media Links -->
                            <div class="flex justify-center space-x-4 mb-4">
                                <?php foreach ($socialMediaLinks as $socialMedia): ?>
                                    <a href="<?php echo htmlspecialchars($socialMedia['link']); ?>" target="_blank" class="text-gray-600 hover:text-primary transition-colors">
                                        <i class="fab fa-<?php echo strtolower($socialMedia['platform']); ?> text-xl"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <button class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-light transition-colors w-full"
                                onclick="openEditModal()">
                                <i class="fas fa-edit mr-2"></i>Edit Profile
                            </button>
                        </div>

                        <!-- Personal Information -->
                        <div class="space-y-3 mt-6 border-t pt-4">
                            <div class="flex items-center">
                                <i class="fas fa-id-card text-secondary w-5 mr-3"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($user['nid_birth_certificate']); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-secondary w-5 mr-3"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone text-secondary w-5 mr-3"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($user['contact_no']); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-secondary w-5 mr-3"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($address); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-tint text-secondary w-5 mr-3"></i>
                                <span class="text-gray-700">Blood Group: <?php echo htmlspecialchars($user['blood_group']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Card -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-6 card-hover animate-float">
                        <h3 class="text-lg font-semibold text-primary mb-4">My Statistics</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-file-alt text-blue-500"></i>
                                    </div>
                                    <span class="text-gray-700">Total Reports</span>
                                </div>
                                <span class="text-lg font-semibold text-primary"><?php echo $totalReports; ?></span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    </div>
                                    <span class="text-gray-700">Solved Reports</span>
                                </div>
                                <span class="text-lg font-semibold text-primary"><?php echo $solvedReports; ?></span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar-alt text-purple-500"></i>
                                    </div>
                                    <span class="text-gray-700">Created Events</span>
                                </div>
                                <span class="text-lg font-semibold text-primary"><?php echo $totalEvents; ?></span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-trophy text-amber-500"></i>
                                    </div>
                                    <span class="text-gray-700">Succeeded Events</span>
                                </div>
                                <span class="text-lg font-semibold text-primary"><?php echo $succeededEvents; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t">
                        <a href="logout.php" class="w-full bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>

                <!-- Right Column - Content -->
                <div class="lg:col-span-2 opacity-0 animate-fadeIn delay-200">
                    <!-- Activity Dashboard -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-6 card-hover">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-primary">Recent Activities</h3>
                            <span class="text-sm text-gray-500 px-3 py-1 bg-gray-100 rounded-full">Last 5 activities</span>
                        </div>

                        <div class="space-y-4">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="border-l-4 <?php echo $activity['type'] === 'report' ? 'border-blue-500' : 'border-purple-500'; ?> pl-4 py-2 activity-item">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($activity['title']); ?></h4>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($activity['date'])); ?></span>
                                                <?php if (isset($activity['status'])): ?>
                                                    <span class="block mt-1 px-2 py-1 text-xs rounded-full <?php echo getStatusBadgeClass($activity['status']); ?>">
                                                        <?php echo htmlspecialchars($activity['status']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-history text-gray-400 text-4xl mb-4"></i>
                                    <p class="text-gray-500">No recent activities found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Blood Donation Section -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-6 card-hover opacity-0 animate-fadeIn delay-300">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">Blood Donation</h3>
                            <i class="fas fa-heartbeat text-red-500 text-2xl"></i>
                        </div>

                        <?php if ($donationRecord): ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                    <div>
                                        <p class="font-medium text-green-800">Thank you for being a blood donor!</p>
                                        <p class="text-sm text-green-600">Next eligible donation: <strong><?php echo date('M d, Y', strtotime($donationRecord['next_date'])); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                            <form method="POST">
                                <button type="submit" name="remove_record" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-times mr-2"></i>Remove Record
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-gray-600 mb-4">Donating blood is a simple and generous act that can save lives. Every donation helps those in need.</p>
                            <form method="POST">
                                <button type="submit" name="donate_blood" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-heartbeat mr-2"></i>Become a Donor
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Voluntary Service Section -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-6 card-hover opacity-0 animate-fadeIn delay-300">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">Voluntary Service</h3>
                            <i class="fas fa-hands-helping text-blue-500 text-2xl"></i>
                        </div>

                        <?php if (isset($message)): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <p class="text-blue-800"><?php echo $message; ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="mb-6">
                            <div class="flex gap-4">
                                <select name="category" class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                    <option value="">Select Category</option>
                                    <option value="Community Development">Community Development</option>
                                    <option value="Health and Wellness">Health and Wellness</option>
                                    <option value="Educational and Awareness">Educational and Awareness</option>
                                    <option value="Social and Cultural">Social and Cultural</option>
                                    <option value="Environment and Sustainability">Environment and Sustainability</option>
                                    <option value="Others">Others</option>
                                </select>
                                <button type="submit" name="submit_voluntary" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Enroll
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($enrolledCategories)): ?>
                            <div class="mb-4">
                                <h4 class="font-medium text-gray-800 mb-3">Your Enrollments</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php foreach ($enrolledCategories as $category): ?>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex items-center">
                                                <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                                                <span class="text-blue-800"><?php echo htmlspecialchars($category['category']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <form method="POST">
                                <button type="submit" name="remove_all" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Remove All
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">No enrolled categories found.</p>
                        <?php endif; ?>
                    </div>

                    <!-- My Reports Section -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-6 card-hover opacity-0 animate-fadeIn" style="animation-delay: 0.4s;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-primary">My Reports</h3>
                            <a href="report_issue.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-4 py-2 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                                <i class="fas fa-plus"></i>
                                <span>New Report</span>
                            </a>
                        </div>

                        <?php if (!empty($reports)): ?>
                            <div class="space-y-4">
                                <?php foreach ($paginatedReports['items'] as $report): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:border-secondary transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-800 mb-2"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                                    <span><i class="fas fa-calendar-alt mr-1"></i><?php echo date('M d, Y', strtotime($report['date_reported'])); ?></span>
                                                    <span class="px-2 py-1 rounded-full text-xs <?php echo getStatusBadgeClass($report['status']); ?>">
                                                        <?php echo htmlspecialchars($report['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <form method="POST" class="ml-4">
                                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                <button type="submit" name="delete_report" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this report?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Pagination for Reports -->
                                <?php if ($paginatedReports['totalPages'] > 1): ?>
                                    <div class="flex justify-center items-center space-x-2 mt-6">
                                        <?php if ($paginatedReports['currentPage'] > 1): ?>
                                            <a href="?report_page=<?php echo $paginatedReports['currentPage'] - 1; ?>&event_page=<?php echo $eventPage; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $paginatedReports['totalPages']; $i++): ?>
                                            <a href="?report_page=<?php echo $i; ?>&event_page=<?php echo $eventPage; ?>"
                                                class="px-3 py-1 rounded-md <?php echo ($i == $paginatedReports['currentPage']) ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($paginatedReports['currentPage'] < $paginatedReports['totalPages']): ?>
                                            <a href="?report_page=<?php echo $paginatedReports['currentPage'] + 1; ?>&event_page=<?php echo $eventPage; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                                <p class="text-gray-500">No reports submitted yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- My Events Section -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 card-hover opacity-0 animate-fadeIn" style="animation-delay: 0.5s;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-primary">My Events</h3>
                            <a href="event.php" class="bg-secondary/20 hover:bg-secondary/30 text-primary-800 font-medium px-4 py-2 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                                <i class="fas fa-plus"></i>
                                <span>Create Event</span>
                            </a>
                        </div>

                        <?php if (!empty($events)): ?>
                            <div class="space-y-4">
                                <?php foreach ($paginatedEvents['items'] as $event): ?>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM succeed_events WHERE event_id = :event_id");
                                    $stmt->execute([':event_id' => $event['id']]);
                                    $succeedEvent = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:border-secondary transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-800 mb-2"><?php echo htmlspecialchars($event['event_description']); ?></p>
                                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($event['location']); ?></span>
                                                    <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($event['created_at'])); ?></span>
                                                    <?php if ($succeedEvent): ?>
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 border border-green-200 rounded-full text-xs">Succeeded</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <?php if (!$succeedEvent): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                        <button type="submit" name="mark_succeeded" class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700" title="Mark as Succeeded" onclick="return confirm('Mark this event as succeeded?');">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" name="delete_event" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700" title="Delete Event" onclick="return confirm('Are you sure you want to delete this event?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Pagination for Events -->
                                <?php if ($paginatedEvents['totalPages'] > 1): ?>
                                    <div class="flex justify-center items-center space-x-2 mt-6">
                                        <?php if ($paginatedEvents['currentPage'] > 1): ?>
                                            <a href="?event_page=<?php echo $paginatedEvents['currentPage'] - 1; ?>&report_page=<?php echo $reportPage; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $paginatedEvents['totalPages']; $i++): ?>
                                            <a href="?event_page=<?php echo $i; ?>&report_page=<?php echo $reportPage; ?>"
                                                class="px-3 py-1 rounded-md <?php echo ($i == $paginatedEvents['currentPage']) ? 'bg-secondary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($paginatedEvents['currentPage'] < $paginatedEvents['totalPages']): ?>
                                            <a href="?event_page=<?php echo $paginatedEvents['currentPage'] + 1; ?>&report_page=<?php echo $reportPage; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-alt text-gray-400 text-4xl mb-4"></i>
                                <p class="text-gray-500">No events created yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- Closing the 70% width container -->

    <!-- Edit Profile Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-xl max-w-md w-full border border-white/20">
                <div class="flex items-center justify-between p-6 border-b border-gray-200/70">
                    <h3 class="text-xl font-semibold text-primary-500">Edit Profile</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-primary-500 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data" class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Social Media Platform</label>
                        <select name="social_media_platform" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="instagram">Instagram</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="github">GitHub</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Link</label>
                        <input type="url" name="social_media_link" placeholder="https://..." class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" name="submit_enhanced_profile" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Initialize animations for fade-in elements
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in elements with animate-fadeIn class
            document.querySelectorAll('.animate-fadeIn').forEach(el => {
                setTimeout(() => {
                    el.style.opacity = '1';
                }, 100);
            });
            
            // Add enhanced movement to the background circles for better visibility
            const circle1 = document.querySelector('.decorative-circle-1');
            const circle2 = document.querySelector('.decorative-circle-2');
            const circle3 = document.querySelector('.decorative-circle-3');
            const bgPattern = document.querySelector('.bg-pattern');
            
            if (circle1 && circle2 && circle3) {
                // Initial placement to ensure visibility
                circle1.style.opacity = '1';
                circle2.style.opacity = '1';
                circle3.style.opacity = '1';
                
                // More dynamic movement with scaling
                let angle1 = 0, angle2 = 0, angle3 = 0;
                
                setInterval(() => {
                    // First circle: wider movement
                    angle1 += 0.02;
                    const x1 = Math.sin(angle1) * 30;
                    const y1 = Math.cos(angle1) * 20;
                    circle1.style.transform = `translate(${x1}px, ${y1}px) scale(${0.95 + Math.sin(angle1) * 0.1})`;
                    
                    // Second circle: different rhythm
                    angle2 += 0.015;
                    const x2 = Math.sin(angle2) * 25;
                    const y2 = Math.cos(angle2 * 1.5) * 25;
                    circle2.style.transform = `translate(${x2}px, ${y2}px) scale(${0.98 + Math.sin(angle2) * 0.08})`;
                    
                    // Third circle: yet another pattern
                    angle3 += 0.025;
                    const x3 = Math.sin(angle3 * 0.8) * 15;
                    const y3 = Math.cos(angle3) * 30;
                    circle3.style.transform = `translate(${x3}px, ${y3}px) scale(${0.97 + Math.cos(angle3) * 0.12})`;
                    
                }, 50); // Much faster refresh rate for smoother animation
            }
        });
    </script>
</body>

</html>