<?php
include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Get user data with profile picture
$userQuery = "SELECT * FROM users WHERE username = :username";
$userStmt = $pdo->prepare($userQuery);
$userStmt->execute(['username' => $_SESSION['username']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$userLocation = $user['location'];

// Handle sorting options
$typeSort = isset($_GET['type_sort']) ? $_GET['type_sort'] : '';
$statusSort = isset($_GET['status_sort']) ? $_GET['status_sort'] : '';
$votesSort = isset($_GET['votes_sort']) ? $_GET['votes_sort'] : '';

// Fetch all issue types dynamically from the database
$typeQuery = "SELECT DISTINCT issue_type FROM report_issues";
$typeStmt = $pdo->prepare($typeQuery);
$typeStmt->execute();
$types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

// Build the query with selected filters - using only existing columns
$query = "
    SELECT r.id, r.description, r.username,
    COALESCE(r.status, 'Pending') AS status, 
    r.location, r.issue_type, 
    (SELECT COUNT(*) FROM report_vote WHERE report_vote.report_id = r.id) AS votes,
    u.username AS reporter_name,
    u.dp AS reporter_image
    FROM report_issues r
    JOIN users u ON r.username = u.username
    WHERE r.location = :location
";

// Check if a specific report_id is requested
$specificReportId = isset($_GET['report_id']) ? (int)$_GET['report_id'] : null;
if ($specificReportId) {
    $query .= " AND r.id = :report_id";
}

// Adding conditions based on sorting preferences
$conditions = [];
if ($typeSort) {
    $conditions[] = "r.issue_type = :type_sort";
}
if ($statusSort) {
    $conditions[] = "r.status = :status_sort";
}
$query .= (count($conditions) > 0) ? ' AND ' . implode(' AND ', $conditions) : '';

// Sorting by votes (ascending or descending)
if ($votesSort) {
    $query .= " ORDER BY votes " . ($votesSort == 'asc' ? 'ASC' : 'DESC');
} else {
    $query .= " ORDER BY r.id DESC"; // Default sort by ID (most recent)
}

$stmt = $pdo->prepare($query);
$params = ['location' => $userLocation];
if ($specificReportId) {
    $params['report_id'] = $specificReportId;
}
if ($typeSort) {
    $params['type_sort'] = $typeSort;
}
if ($statusSort) {
    $params['status_sort'] = $statusSort;
}

$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle voting and intense selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['vote'])) {
        // Save vote action to report_vote table
        $reportId = (int)$_POST['report_id'];
        $stmt = $pdo->prepare("INSERT INTO report_vote (report_id, username, vote_status) VALUES (:report_id, :username, 'voted')");
        $stmt->execute(['report_id' => $reportId, 'username' => $_SESSION['username']]);
    } elseif (isset($_POST['intense_option'])) {
        // Save intense selection to report_vote table
        $reportId = (int)$_POST['report_id'];
        $intenseOption = $_POST['intense_option'];
        $stmt = $pdo->prepare("UPDATE report_vote SET intense_option = :intense_option WHERE report_id = :report_id AND username = :username");
        $stmt->execute(['intense_option' => $intenseOption, 'report_id' => $reportId, 'username' => $_SESSION['username']]);
    }
    // Refresh the page to show updated data
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 1. Handle comment add/delete
if (isset($_POST['add_comment']) && isset($_POST['report_id']) && isset($_POST['comment_text'])) {
    $reportId = (int)$_POST['report_id'];
    $commentText = trim($_POST['comment_text']);
    // Only allow one comment per user per report
    $stmt = $pdo->prepare("UPDATE report_vote SET comments = :comment WHERE report_id = :report_id AND username = :username");
    $stmt->execute(['comment' => $commentText, 'report_id' => $reportId, 'username' => $_SESSION['username']]);
    // If no row updated, insert
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO report_vote (report_id, username, comments, vote_status) VALUES (:report_id, :username, :comment, 'not_voted')");
        $stmt->execute(['report_id' => $reportId, 'username' => $_SESSION['username'], 'comment' => $commentText]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['delete_comment']) && isset($_POST['report_id'])) {
    $reportId = (int)$_POST['report_id'];
    $stmt = $pdo->prepare("UPDATE report_vote SET comments = NULL WHERE report_id = :report_id AND username = :username");
    $stmt->execute(['report_id' => $reportId, 'username' => $_SESSION['username']]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// 2. Handle save button
if (isset($_POST['save_report']) && isset($_POST['report_id'])) {
    $reportId = (int)$_POST['report_id'];
    // Fetch report data
    $stmt = $pdo->prepare("SELECT * FROM report_issues WHERE id = :id");
    $stmt->execute(['id' => $reportId]);
    $reportData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reportData) {
        $saves = json_encode($reportData);
        $stmt = $pdo->prepare("INSERT INTO saved (username, saves) VALUES (:username, :saves)");
        $stmt->execute(['username' => $_SESSION['username'], 'saves' => $saves]);
    }
    header("Location: savd.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>E-Shongskar Reports</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f2e24;
            --primary-light: #1a4735;
            --secondary: #4CAF50;
            --accent: #FF6B6B;
            --text: #1a1a1a;
            --text-light: #444;
            --text-lighter: #666;
            --bg-light: #f8f9fa;
            --bg-lighter: #ffffff;
            --border: #e0e0e0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --radius: 12px;
            --radius-lg: 16px;
        }

        body {
            font-family: 'Inter', sans-serif;
            padding-top: 70px;
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        main {
            background: transparent;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Animated background pattern */
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
            background: var(--primary);
            top: -300px;
            right: -100px;
        }
        
        .bg-pattern::after {
            background: var(--secondary);
            bottom: -200px;
            left: -150px;
        }
        
        /* Report card hover effects */
        .report-card {
            transition: var(--transition);
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Custom status badge styles with glow effect */
        .status {
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .status-pending { 
            background-color: #f39c12;
            box-shadow: 0 0 10px rgba(243, 156, 18, 0.3);
        }
        
        .status-progress { 
            background-color: #3498db;
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
        }
        
        .status-solved { 
            background-color: #27ae60;
            box-shadow: 0 0 10px rgba(39, 174, 96, 0.3);
        }
        
        .status-denied { 
            background-color: #e74c3c;
            box-shadow: 0 0 10px rgba(231, 76, 60, 0.3);
        }
        
        /* Button hover effects */
        .btn-hover {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-hover:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .btn-hover:hover:before {
            left: 100%;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f2e24',
                        'primary-light': '#1a4735',
                        secondary: '#4CAF50',
                        accent: '#FF6B6B',
                        'accent-light': '#FFB3B3',
                        dark: '#1a1a1a',
                        'text-light': '#444444',
                        'text-lighter': '#666666',
                        'bg-light': '#f8f9fa',
                    },
                    boxShadow: {
                        'card': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                        'hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        }
    </script>
</head>

<body style="background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);">
    <div class="bg-pattern"></div>
    <?php include 'header.php'; ?>

    <main class="pt-8 pb-16 mx-auto w-[70%] max-w-screen-xl" style="background: transparent;">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-primary relative">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-map-marker-alt text-secondary"></i>
                        Reports in <?php echo htmlspecialchars($userLocation); ?>
                    </span>
                    <span class="block w-16 h-1 bg-secondary mt-2"></span>
                </h2>
            </div>
            <div>
                <a href="report_issue.php" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-light text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-300 hover:shadow-md">
                    <i class="fas fa-plus-circle mr-2"></i> Report an Issue
                </a>
            </div>
        </div>

        <!-- Sorting Options -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8 transition-all duration-300 hover:shadow-md animate-fadeIn">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-primary flex items-center gap-2 mb-3">
                    <i class="fas fa-filter text-secondary"></i>
                    Filter Reports
                </h3>
                <p class="text-sm text-gray-600">Customize which reports you see and how they're ordered.</p>
            </div>
            
            <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex flex-col">
                    <label for="type_sort" class="text-xs text-gray-500 font-medium mb-2 ml-1">Issue Type</label>
                    <div class="relative">
                        <select name="type_sort" id="type_sort" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent text-sm transition-all appearance-none">
                            <option value="">All Types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['issue_type']); ?>"
                                    <?php if ($typeSort == $type['issue_type']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($type['issue_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label for="status_sort" class="text-xs text-gray-500 font-medium mb-2 ml-1">Status</label>
                    <div class="relative">
                        <select name="status_sort" id="status_sort" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent text-sm transition-all appearance-none">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php if ($statusSort == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="In Progress" <?php if ($statusSort == 'In Progress') echo 'selected'; ?>>In Progress</option>
                            <option value="Solved" <?php if ($statusSort == 'Solved') echo 'selected'; ?>>Solved</option>
                            <option value="Denied" <?php if ($statusSort == 'Denied') echo 'selected'; ?>>Denied</option>
                        </select>
                        <i class="fas fa-chevron-down text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label for="votes_sort" class="text-xs text-gray-500 font-medium mb-2 ml-1">Sort Votes</label>
                    <div class="relative">
                        <select name="votes_sort" id="votes_sort" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent text-sm transition-all appearance-none">
                            <option value="">Default Order</option>
                            <option value="asc" <?php if ($votesSort == 'asc') echo 'selected'; ?>>Low to High</option>
                            <option value="desc" <?php if ($votesSort == 'desc') echo 'selected'; ?>>High to Low</option>
                        </select>
                        <i class="fas fa-chevron-down text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-5 py-2 bg-primary hover:bg-primary-light text-white rounded-lg font-medium text-sm transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fas fa-search"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Reports List -->
        <?php if ($reports): ?>
            <div class="space-y-6">
                <?php foreach ($reports as $report): ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg border border-gray-100 report-card animate-fadeIn" id="report-card-<?php echo $report['id']; ?>">
                        <!-- Report Header -->
                        <div class="flex justify-between items-center p-5 border-b border-gray-100 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($report['reporter_image']) && file_exists($report['reporter_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($report['reporter_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($report['reporter_name']); ?>" 
                                         class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-secondary bg-opacity-10 flex items-center justify-center shadow-sm">
                                        <span class="text-secondary font-semibold">
                                            <?php echo substr(htmlspecialchars($report['username']), 0, 1); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($report['reporter_name']); ?></h4>
                                    <p class="text-xs text-gray-500 flex items-center gap-1">
                                        <i class="fas fa-user-edit text-secondary text-xs"></i>
                                        Reported by <?php echo htmlspecialchars($report['username']); ?>
                                    </p>
                                </div>
                            </div>
                            <?php 
                                $statusClasses = [
                                    'Pending' => 'bg-amber-500',
                                    'In Progress' => 'bg-blue-500',
                                    'Solved' => 'bg-green-500',
                                    'Denied' => 'bg-red-500'
                                ];
                                $statusIcons = [
                                    'Pending' => 'fa-clock',
                                    'In Progress' => 'fa-hammer',
                                    'Solved' => 'fa-check-circle',
                                    'Denied' => 'fa-times-circle'
                                ];
                                $statusClass = $statusClasses[$report['status']] ?? 'bg-gray-500';
                                $statusIcon = $statusIcons[$report['status']] ?? 'fa-info-circle';
                            ?>
                            <div class="flex flex-col items-end">
                                <span class="px-3 py-1 rounded-full text-xs font-bold text-white flex items-center gap-1 <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i>
                                    <?php echo htmlspecialchars($report['status']); ?>
                                </span>
                                <span class="text-xs text-gray-400 mt-1">
                                    <i class="far fa-clock mr-1"></i>
                                    <?php echo date('M d, Y', strtotime($report['created_at'] ?? 'now')); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Report Content -->
                        <div class="flex flex-col md:flex-row p-4 gap-6">
                            <div class="md:w-[55%] w-full">
                                <div class="rounded-lg overflow-hidden h-64 md:h-80 bg-gray-100 shadow-inner border border-gray-200 relative group">
                                    <img 
                                        src="view_image.php?id=<?php echo $report['id']; ?>" 
                                        alt="Report Image" 
                                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-start">
                                        <div class="p-4 text-white">
                                            <p class="text-sm font-semibold">Report ID: #<?php echo $report['id']; ?></p>
                                            <p class="text-xs"><?php echo htmlspecialchars($report['location']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Quick action buttons under image -->
                                
                            </div>
                            
                            <div class="md:w-[45%] w-full bg-gray-50 rounded-lg p-5 border border-gray-100">
                                <div class="prose">
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-primary mb-3 flex items-center gap-2">
                                            <i class="fas fa-file-alt text-secondary"></i>
                                            Description
                                        </h3>
                                        <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm mb-5 min-h-[200px]">
                                            <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($report['description']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-2">
                                        <div class="flex-grow bg-white px-3 py-2 rounded-lg shadow-sm border-l-4 border-secondary hover:shadow-md transition-all flex items-center gap-2">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-tag text-secondary"></i>
                                            </div>
                                            <div class="flex-grow">
                                                <p class="text-xs text-gray-500 uppercase font-medium">Category</p>
                                                <p class="text-sm font-medium text-primary truncate"><?php echo htmlspecialchars($report['issue_type']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow bg-white px-3 py-2 rounded-lg shadow-sm border-l-4 border-primary hover:shadow-md transition-all flex items-center gap-2">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-map-marker-alt text-primary"></i>
                                            </div>
                                            <div class="flex-grow">
                                                <p class="text-xs text-gray-500 uppercase font-medium">Location</p>
                                                <p class="text-sm font-medium text-primary truncate"><?php echo htmlspecialchars($report['location']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow bg-white px-3 py-2 rounded-lg shadow-sm border-l-4 border-accent hover:shadow-md transition-all flex items-center gap-2">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-poll text-accent"></i>
                                            </div>
                                            <div class="flex-grow flex items-center">
                                                <div>
                                                    <p class="text-xs text-gray-500 uppercase font-medium">Votes</p>
                                                    <p class="text-sm font-medium text-primary"><?php echo htmlspecialchars($report['votes']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Report Footer -->
                        <div class="flex justify-between items-center p-5 border-t border-gray-100 bg-white">
                            <div class="flex items-center gap-4 flex-wrap">
                                <form method="post" class="inline-flex items-center">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    
                                    <?php
                                    $voteCheckStmt = $pdo->prepare("SELECT vote_status, IFNULL(intense_option, '') AS intense_option FROM report_vote WHERE report_id = :report_id AND username = :username");
                                    $voteCheckStmt->execute(['report_id' => $report['id'], 'username' => $_SESSION['username']]);
                                    $voteData = $voteCheckStmt->fetch(PDO::FETCH_ASSOC);

                                    if (!$voteData): ?>
                                        <button type="submit" name="vote" class="inline-flex items-center gap-2 text-gray-600 hover:text-secondary transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-50">
                                            <i class="fas fa-thumbs-up"></i>
                                            <span>Vote</span>
                                        </button>
                                    <?php elseif ($voteData['vote_status'] === 'voted' && empty($voteData['intense_option'])): ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm text-gray-600 font-medium">Select intensity:</span>
                                            <div class="flex gap-2">
                                                <button type="submit" name="intense_option" value="No" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs transition-colors border border-gray-200">Low</button>
                                                <button type="submit" name="intense_option" value="Yes" class="px-3 py-1 bg-yellow-100 hover:bg-yellow-200 rounded-lg text-xs transition-colors border border-yellow-200 text-yellow-700">Medium</button>
                                                <button type="submit" name="intense_option" value="High" class="px-3 py-1 bg-red-100 hover:bg-red-200 rounded-lg text-xs transition-colors border border-red-200 text-red-700">High</button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                            $intensityClasses = [
                                                'No' => 'bg-gray-100 text-gray-700 border-gray-200',
                                                'Yes' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                                'High' => 'bg-red-100 text-red-700 border-red-200'
                                            ];
                                            $intensityClass = $intensityClasses[$voteData['intense_option']] ?? 'bg-gray-100 text-gray-700';
                                            
                                            // Convert "Yes" to "Medium" for display while keeping the database value intact
                                            $displayIntensity = $voteData['intense_option'];
                                            if ($displayIntensity === 'Yes') {
                                                $displayIntensity = 'Medium';
                                            }
                                        ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border <?php echo $intensityClass; ?>">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Intensity: <?php echo htmlspecialchars($displayIntensity); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </form>
                               
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors p-2 rounded-full hover:bg-gray-50 save-report-btn" data-report-id="<?php echo $report['id']; ?>" title="Save this report">
                                    <i class="fas fa-bookmark"></i>
                                </button>
                                
                                <div class="relative group">
                                    <button class="text-gray-400 hover:text-primary transition-colors p-2 rounded-full hover:bg-gray-50">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10 hidden group-hover:block border border-gray-100">
                                        <ul class="py-2">
                                            <li>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-eye mr-2"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-bell-slash mr-2"></i> Mute Updates
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                    <i class="fas fa-flag mr-2"></i> Report Issue
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Comment Section (hidden by default, shown when Comment button is clicked) -->
                        <div class="comment-section" id="comment-section-<?php echo $report['id']; ?>" style="display:none; padding: 16px; border-top: 1px solid #f3f3f3;">
                            <?php
                            // Fetch all comments for this report
                            $stmtAllC = $pdo->prepare("SELECT username, comments, created_at FROM report_vote WHERE report_id = :report_id AND comments IS NOT NULL ORDER BY created_at DESC");
                            $stmtAllC->execute(['report_id' => $report['id']]);
                            $allComments = $stmtAllC->fetchAll(PDO::FETCH_ASSOC);
                            $userComment = null;
                            if (!empty($allComments)) {
                                foreach ($allComments as $c) {
                                    if ($c['username'] === $_SESSION['username']) {
                                        $userComment = $c;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <!-- Add comment form (if user hasn't commented yet) -->
                            <?php if (!$userComment): ?>
                                <form method="post" class="mb-2 flex gap-2 items-center">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <input type="text" name="comment_text" maxlength="255" placeholder="Add a comment..." class="px-2 py-1 border rounded text-sm flex-1" required>
                                    <button type="submit" name="add_comment" class="px-3 py-1 bg-primary text-white rounded text-xs">Post</button>
                                </form>
                            <?php endif; ?>
                            <!-- Show latest comment -->
                            <?php if (!empty($allComments)): ?>
                                <div class="mb-2">
                                    <strong><?php echo htmlspecialchars($allComments[0]['username']); ?>:</strong>
                                    <span><?php echo htmlspecialchars($allComments[0]['comments']); ?></span>
                                    <?php if ($allComments[0]['username'] === $_SESSION['username']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" name="delete_comment" class="text-red-500 text-xs ml-2" title="Delete Comment">&times;</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php if (count($allComments) > 1): ?>
                                    <button class="see-more-comments text-xs text-blue-600 underline" data-report-id="<?php echo $report['id']; ?>">See more</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm p-16 text-center animate-fadeIn">
                <div class="w-20 h-20 bg-gray-50 rounded-full mx-auto flex items-center justify-center mb-6 border-2 border-gray-100">
                    <i class="fas fa-search text-gray-300 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-semibold text-gray-700 mb-3">No Reports Found</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">There are no reports currently available for your location. Be the first to report an issue in your area!</p>
                <a href="report_issue.php" class="inline-block px-6 py-3 bg-secondary hover:bg-green-600 text-white rounded-lg font-medium transition-all duration-300 shadow-sm hover:shadow-md">
                    <i class="fas fa-plus-circle mr-2"></i> Create a New Report
                </a>
            </div>
        <?php endif; ?>

        <!-- Pagination (Placeholder - Can be implemented with PHP) -->
        <?php if (count($reports) > 5): ?>
        <div class="flex justify-center mt-10">
            <nav class="flex items-center space-x-1">
                <a href="#" class="px-3 py-1 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="#" class="px-3 py-1 rounded-md bg-primary text-white">1</a>
                <a href="#" class="px-3 py-1 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all">2</a>
                <a href="#" class="px-3 py-1 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all">3</a>
                <span class="px-3 py-1 text-gray-500">...</span>
                <a href="#" class="px-3 py-1 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </nav>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-16 animate-fadeIn">
            <div class="bg-white rounded-lg p-5 shadow-sm border-t-4 border-primary text-center">
                <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-full mx-auto flex items-center justify-center mb-3">
                    <i class="fas fa-clipboard-list text-primary"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Total Reports</h4>
                <p class="text-2xl font-bold text-primary"><?php echo count($reports); ?></p>
            </div>
            
            <div class="bg-white rounded-lg p-5 shadow-sm border-t-4 border-secondary text-center">
                <div class="w-12 h-12 bg-secondary bg-opacity-10 rounded-full mx-auto flex items-center justify-center mb-3">
                    <i class="fas fa-check-circle text-secondary"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Solved Issues</h4>
                <p class="text-2xl font-bold text-secondary">
                    <?php 
                        $solvedCount = 0;
                        foreach ($reports as $report) {
                            if ($report['status'] === 'Solved') $solvedCount++;
                        }
                        echo $solvedCount;
                    ?>
                </p>
            </div>
            
            <div class="bg-white rounded-lg p-5 shadow-sm border-t-4 border-blue-500 text-center">
                <div class="w-12 h-12 bg-blue-500 bg-opacity-10 rounded-full mx-auto flex items-center justify-center mb-3">
                    <i class="fas fa-hammer text-blue-500"></i>
                </div>
                <h4 class="font-semibold text-gray-800">In Progress</h4>
                <p class="text-2xl font-bold text-blue-500">
                    <?php 
                        $inProgressCount = 0;
                        foreach ($reports as $report) {
                            if ($report['status'] === 'In Progress') $inProgressCount++;
                        }
                        echo $inProgressCount;
                    ?>
                </p>
            </div>
            
            <div class="bg-white rounded-lg p-5 shadow-sm border-t-4 border-amber-500 text-center">
                <div class="w-12 h-12 bg-amber-500 bg-opacity-10 rounded-full mx-auto flex items-center justify-center mb-3">
                    <i class="fas fa-clock text-amber-500"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Pending</h4>
                <p class="text-2xl font-bold text-amber-500">
                    <?php 
                        $pendingCount = 0;
                        foreach ($reports as $report) {
                            if ($report['status'] === 'Pending') $pendingCount++;
                        }
                        echo $pendingCount;
                    ?>
                </p>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    
    <script>
        // Add smooth scrolling effect
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        // Toggle comment section
        const commentBtns = document.querySelectorAll('.comment-btn');
        commentBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.getAttribute('data-report-id');
                const section = document.getElementById('comment-section-' + reportId);
                if (section.style.display === 'none' || section.style.display === '') {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        });
        // See more comments popup
        const seeMoreBtns = document.querySelectorAll('.see-more-comments');
        seeMoreBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.getAttribute('data-report-id');
                fetch('get_comments.php?report_id=' + reportId)
                    .then(res => res.json())
                    .then(comments => {
                        const list = document.getElementById('comments-modal-list');
                        list.innerHTML = '';
                        comments.forEach(c => {
                            let delBtn = '';
                            if (c.username === <?php echo json_encode($_SESSION['username']); ?>) {
                                delBtn = `<form method=\'post\' style=\'display:inline;\'><input type=\'hidden\' name=\'report_id\' value=\'${reportId}\'><button type=\'submit\' name=\'delete_comment\' class=\'text-red-500 text-xs ml-2\' title=\'Delete Comment\'>&times;</button></form>`;
                            }
                            list.innerHTML += `<div style=\'margin-bottom:8px;\'><strong>${c.username}:</strong> <span>${c.comments}</span> ${delBtn}</div>`;
                        });
                        document.getElementById('comments-modal').style.display = 'flex';
                    });
            });
        });
        function closeCommentsModal() {
            document.getElementById('comments-modal').style.display = 'none';
        }
        // Save report via AJAX
        const saveBtns = document.querySelectorAll('.save-report-btn');
        saveBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.getAttribute('data-report-id');
                const icon = this.querySelector('i');
                fetch('save_report_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'report_id=' + encodeURIComponent(reportId)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.saved) {
                            icon.classList.remove('text-gray-400');
                            icon.classList.add('text-green-500');
                            this.title = 'Saved!';
                            if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('save-msg')) {
                                const msg = document.createElement('span');
                                msg.textContent = 'Saved!';
                                msg.className = 'save-msg text-green-600 text-xs ml-2';
                                this.parentNode.insertBefore(msg, this.nextSibling);
                                setTimeout(() => msg.remove(), 2000);
                            }
                        } else {
                            icon.classList.remove('text-green-500');
                            icon.classList.add('text-gray-400');
                            this.title = 'Save this report';
                            if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('save-msg')) {
                                const msg = document.createElement('span');
                                msg.textContent = 'Unsaved!';
                                msg.className = 'save-msg text-gray-600 text-xs ml-2';
                                this.parentNode.insertBefore(msg, this.nextSibling);
                                setTimeout(() => msg.remove(), 2000);
                            }
                        }
                    } else {
                        icon.classList.add('text-red-500');
                        this.title = 'Error saving';
                    }
                });
            });
        });
    </script>
    <?php if (isset($_GET['report_id'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var reportId = <?php echo json_encode($_GET['report_id']); ?>;
        var el = document.getElementById('report-card-' + reportId);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('highlight-report');
            setTimeout(function() {
                el.classList.remove('highlight-report');
            }, 3000);
        }
    });
    </script>
    <style>
    .highlight-report {
        box-shadow: 0 0 0 4px #4CAF50, 0 10px 15px rgba(76,175,80,0.15);
        border-color: #4CAF50 !important;
        transition: box-shadow 0.5s, border-color 0.5s;
    }
    </style>
    <?php endif; ?>
</body>

</html>