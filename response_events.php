<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo "Please log in to view events.";
    exit();
}

include 'database.php';

// Function to get relative time
function getRelativeTime($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return "Just now";
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

$loggedInUsername = $_SESSION['username'];

// user's location
$stmt = $pdo->prepare("SELECT location FROM users WHERE username = :username");
$stmt->execute(['username' => $loggedInUsername]);
$userLocation = $stmt->fetchColumn();

//user's "your_voluntary" category
$voluntaryStmt = $pdo->prepare("SELECT category FROM your_voluntary WHERE username = :username");
$voluntaryStmt->execute(['username' => $loggedInUsername]);
$userCategory = $voluntaryStmt->fetchColumn();

// Before the events loop, fetch all liked event IDs for the logged-in user
$likedEventIds = [];
$likeStmt = $pdo->prepare("SELECT event_id FROM likes WHERE username = :username");
$likeStmt->execute(['username' => $loggedInUsername]);
foreach ($likeStmt->fetchAll(PDO::FETCH_COLUMN) as $eid) {
    $likedEventIds[(int)$eid] = true;
}

// Retrieve events
$specificEventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

$query = "
    SELECT e.*, 
           (SELECT COUNT(*) FROM likes WHERE event_id = e.id) AS total_likes, 
           (SELECT COUNT(*) FROM comments WHERE event_id = e.id) AS total_comments
    FROM events e
    WHERE e.location = :location
      AND e.id NOT IN (SELECT event_id FROM succeed_events)
";

// Add specific event filter if event_id is provided
if ($specificEventId) {
    $query .= " AND e.id = :event_id";
}

// Apply sorting based on parameter
switch ($sortBy) {
    case 'popular':
        $query .= " ORDER BY (total_likes + total_comments) DESC, e.created_at DESC";
        break;
    case 'category':
        $query .= " ORDER BY 
            CASE 
                WHEN e.category LIKE :userCategory THEN 1 ELSE 0 
            END DESC, 
            e.category ASC, 
            e.created_at DESC";
        break;
    case 'latest':
    default:
        $query .= " ORDER BY 
            CASE 
                WHEN e.category LIKE :userCategory THEN 1 ELSE 0 
            END DESC, 
            e.created_at DESC,
            (total_likes + total_comments) DESC";
        break;
}

$params = ['location' => $userLocation];
if ($sortBy === 'category' || $sortBy === 'latest') {
    $params['userCategory'] = '%' . $userCategory . '%';
}
if ($specificEventId) {
    $params['event_id'] = $specificEventId;
}

$stmt = $pdo->prepare($query);
try {
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error and show a user-friendly message
    error_log("Database error in response_events.php: " . $e->getMessage());
    $events = [];
    $error_message = "Sorry, there was an error loading the events. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Portal | E-Shongskar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef7f6',
                            100: '#d5eae7',
                            200: '#abd4cd',
                            300: '#81bdb3',
                            400: '#56a69a',
                            500: '#0b493e',
                            /* Main primary color */
                            600: '#094236',
                            700: '#07362d',
                            800: '#052b24',
                            900: '#021d18',
                        },
                        secondary: {
                            50: '#f0f9fa',
                            100: '#d0eff1',
                            200: '#a8dadc',
                            /* accent color */
                            300: '#86c5c8',
                            400: '#5aacaf',
                            500: '#418e91',
                            600: '#377779',
                            700: '#2c6062',
                            800: '#224a4c',
                            900: '#182f30',
                        },
                        gradient: {
                            start: '#e0f7fa',
                            /* Start color of background gradient */
                            end: '#c8e6c9',
                            /* End color of background gradient */
                        },
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.01)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',

                    },
                    maxWidth: {
                        container: '70%',
                    }
                },
            },
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .animate-float {
            animation: float 5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Scrollbar customization */
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

        /* Background styles */
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 140px;
            /* Adjust this value based on your header height */
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
        }

        html {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            height: 100%;
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

        /* Card styling for better contrast against gradient */
        .bg-white {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        /* Event cards - wider and shorter */
        .event-card {
            min-height: 300px;
            display: flex;
            flex-direction: column;
        }

        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
            max-height: 6em;
        }

        /* Compact info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        @media (max-width: 640px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .event-card {
                min-height: auto;
            }
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
            background: #0b493e;
            /* primary color */
            top: -300px;
            right: -100px;
        }

        .bg-pattern::after {
            background: #a8dadc;
            /* accent color */
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
        }

        /* Bottom section additional decorative circles */
        .decorative-circle-4 {
            position: fixed;
            bottom: 8%;
            right: 12%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.03);
            z-index: -1;
            animation: float 8s ease-in-out infinite;
        }

        .decorative-circle-5 {
            position: fixed;
            bottom: 25%;
            left: 35%;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.06);
            z-index: -1;
            animation: float 7s ease-in-out infinite;
            animation-delay: 1s;
        }

        .decorative-circle-6 {
            position: fixed;
            bottom: 12%;
            right: 30%;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(200, 230, 201, 0.07);
            z-index: -1;
            animation: float 9s ease-in-out infinite;
            animation-delay: 1.5s;
        }

        .decorative-circle-7 {
            position: fixed;
            bottom: 5%;
            left: 20%;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.02);
            z-index: -1;
            animation: float 6s ease-in-out infinite;
            animation-delay: 2s;
        }

        /* Additional decorative circles for the bottom of the page */
        .decorative-circle-8 {
            position: fixed;
            bottom: 3%;
            right: 45%;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.04);
            z-index: -1;
            animation: float 7.5s ease-in-out infinite;
            animation-delay: 0.5s;
        }

        .decorative-circle-9 {
            position: fixed;
            bottom: 8%;
            left: 42%;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.03);
            z-index: -1;
            animation: float 8.5s ease-in-out infinite;
            animation-delay: 1.8s;
        }

        .decorative-circle-10 {
            position: fixed;
            bottom: 18%;
            right: 6%;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: rgba(200, 230, 201, 0.05);
            z-index: -1;
            animation: float 9.5s ease-in-out infinite;
            animation-delay: 2.2s;
        }

        .decorative-circle-11 {
            position: fixed;
            bottom: 30%;
            left: 15%;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.07);
            z-index: -1;
            animation: float 6.5s ease-in-out infinite;
            animation-delay: 1.2s;
        }

        /* Fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        /* Add subtle movement for decorative circles */
        .decorative-circle-1,
        .decorative-circle-2,
        .decorative-circle-3 {
            animation: float 8s ease-in-out infinite;
        }

        .decorative-circle-2 {
            animation-delay: 2s;
        }

        .decorative-circle-3 {
            animation-delay: 1s;
        }
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
    <div class="decorative-circle-7"></div>
    <div class="decorative-circle-8"></div>
    <div class="decorative-circle-9"></div>
    <div class="decorative-circle-10"></div>
    <div class="decorative-circle-11"></div>

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

                <h1 class="text-4xl lg:text-5xl font-bold mb-4 text-center opacity-0 animate-fadeIn">Events Portal</h1>
                <p class="text-lg text-primary-800/80 mb-8 max-w-2xl text-center opacity-0 animate-fadeIn delay-100">Discover and join community events happening around you!</p>

                <div class="flex flex-wrap gap-3 justify-center opacity-0 animate-fadeIn delay-200">
                    <a href="event.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-6 py-3 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Create Event</span>
                    </a>
                    <a href="succeed_event.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-6 py-3 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                        <i class="fas fa-check-circle"></i>
                        <span>Succeed Events</span>
                    </a>
                    <a href="fund.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-6 py-3 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Make Funding</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Wave shape divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" fill="transparent" style="opacity: 0.4;">
                <path d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,69.3C672,64,768,64,864,69.3C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
            </svg>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-12 px-4" style="background: transparent !important;">
        <div class="container mx-auto max-w-container">
            
            <?php if (isset($error_message)): ?>
                <div class="mb-8 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($debug): ?>
                <div class="mb-8 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-bug"></i>
                        <span class="font-semibold">Debug Information</span>
                    </div>
                    <div class="text-sm space-y-1">
                        <p><strong>Sort By:</strong> <?php echo htmlspecialchars($sortBy); ?></p>
                        <p><strong>User Location:</strong> <?php echo htmlspecialchars($userLocation); ?></p>
                        <p><strong>User Category:</strong> <?php echo htmlspecialchars($userCategory); ?></p>
                        <p><strong>Events Found:</strong> <?php echo count($events); ?></p>
                        <p><strong>Query:</strong> <code class="bg-blue-200 px-1 rounded"><?php echo htmlspecialchars($query); ?></code></p>
                        <p><strong>Parameters:</strong> <code class="bg-blue-200 px-1 rounded"><?php echo htmlspecialchars(json_encode($params)); ?></code></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Sorting Options -->
            <div class="mb-8 bg-white/80 backdrop-blur-sm rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-primary-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-sort-amount-down text-primary-500"></i>
                    Sort Events
                </h3>
                <div class="flex flex-wrap gap-3">
                    <a href="?sort=latest<?php echo $specificEventId ? '&event_id=' . $specificEventId : ''; ?>" 
                       class="px-4 py-2 rounded-lg transition-colors text-sm font-medium <?php echo $sortBy === 'latest' ? 'bg-primary-500 text-white' : 'bg-white border border-primary-500 text-primary-500 hover:bg-primary-50'; ?>">
                        <i class="fas fa-clock mr-2"></i>Latest First
                    </a>
                    <a href="?sort=popular<?php echo $specificEventId ? '&event_id=' . $specificEventId : ''; ?>" 
                       class="px-4 py-2 rounded-lg transition-colors text-sm font-medium <?php echo $sortBy === 'popular' ? 'bg-primary-500 text-white' : 'bg-white border border-primary-500 text-primary-500 hover:bg-primary-50'; ?>">
                        <i class="fas fa-fire mr-2"></i>Most Popular
                    </a>
                    <a href="?sort=category<?php echo $specificEventId ? '&event_id=' . $specificEventId : ''; ?>" 
                       class="px-4 py-2 rounded-lg transition-colors text-sm font-medium <?php echo $sortBy === 'category' ? 'bg-primary-500 text-white' : 'bg-white border border-primary-500 text-primary-500 hover:bg-primary-50'; ?>">
                        <i class="fas fa-tags mr-2"></i>By Category
                    </a>
                    <span class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                        <i class="fas fa-check mr-2"></i>Currently: 
                        <?php 
                        switch($sortBy) {
                            case 'popular': echo 'Most Popular'; break;
                            case 'category': echo 'By Category'; break;
                            default: echo 'Latest First'; break;
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <!-- Events Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
                <?php if (empty($events)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl p-8 shadow-sm">
                            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Events Found</h3>
                            <p class="text-gray-500">There are no events available in your location at the moment.</p>
                        </div>
                    </div>
                <?php else: ?>
                <?php $delay = 0;
                foreach ($events as $event):
                    $animationDelay = $delay * 0.1;
                    $delay++;
                    $isLiked = !empty($likedEventIds[$event['id']]);
                ?>
                    <?php
                    // Get funding data
                    $fundStmt = $pdo->prepare("SELECT target_amount FROM fund WHERE event_id = :event_id");
                    $fundStmt->execute(['event_id' => $event['id']]);
                    $targetAmount = $fundStmt->fetchColumn();

                    $donateStmt = $pdo->prepare("SELECT SUM(amount) AS total_donations FROM donate WHERE event_id = :event_id");
                    $donateStmt->execute(['event_id' => $event['id']]);
                    $totalDonations = $donateStmt->fetchColumn();
                    $totalDonations = $totalDonations ? $totalDonations : 0;
                    $totalFund = $targetAmount ? ($targetAmount - $totalDonations) : 0;

                    // Calculate funding progress percentage
                    $progressPercent = 0;
                    if ($targetAmount && $targetAmount > 0) {
                        $progressPercent = min(100, round(($totalDonations / $targetAmount) * 100));
                    }
                    ?>

                    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-card hover:shadow-card-hover transition-all duration-300 overflow-hidden transform hover:-translate-y-2 opacity-0 animate-fadeIn h-full flex flex-col event-card" style="animation-delay: <?php echo $animationDelay; ?>s;">
                        <!-- Card Header -->
                        <div class="bg-gradient-to-r from-primary-500 to-primary-600 p-5 relative flex-shrink-0">
                            <span class="absolute top-4 right-4 bg-white/20 text-xs font-medium text-white px-3 py-1 rounded-full">
                                ID: <?php echo htmlspecialchars($event['id']); ?>
                            </span>
                            
                            <?php
                            // Check if event is new (created within last 24 hours)
                            $eventDate = new DateTime($event['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($eventDate);
                            $isNew = $diff->days == 0 && $diff->h < 24;
                            ?>
                            
                            <?php if ($isNew): ?>
                                <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full animate-pulse">
                                    <i class="fas fa-star mr-1"></i>NEW
                                </span>
                            <?php endif; ?>
                            
                            <h2 class="text-xl font-bold text-white"><?php echo htmlspecialchars($event['category']); ?></h2>
                            <div class="flex items-center gap-2 mt-1 text-white/80 text-sm">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo getRelativeTime($event['created_at']); ?></span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 flex-grow flex flex-col">
                            <!-- Description - Enhanced with more space -->
                            <div class="text-gray-700 mb-6 flex-shrink-0">
                                <h3 class="text-sm font-semibold text-gray-500 mb-2 flex items-center gap-2">
                                    <i class="fas fa-info-circle text-primary-500"></i>
                                    Event Description
                                    <?php if ($isNew): ?>
                                        <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full font-medium">
                                            <i class="fas fa-clock mr-1"></i><?php echo getRelativeTime($event['created_at']); ?>
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-primary-500 max-h-40 overflow-y-auto">
                                    <p class="text-gray-800 leading-relaxed whitespace-pre-line">
                                        <?php echo htmlspecialchars($event['event_description']); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Compact Details Grid -->
                            <div class="mb-6 flex-shrink-0">
                                <div class="info-grid">
                                    <!-- Location & Contact in first row -->
                                    <div class="flex items-center bg-gray-50 p-3 rounded-lg">
                                        <div class="w-8 h-8 bg-primary-50 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                            <i class="fas fa-map-marker-alt text-primary-500 text-sm"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs text-gray-500 font-medium">Location</p>
                                            <p class="text-sm text-gray-700 truncate"><?php echo htmlspecialchars($event['location']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center bg-gray-50 p-3 rounded-lg">
                                        <div class="w-8 h-8 bg-primary-50 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                            <i class="fas fa-phone text-primary-500 text-sm"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs text-gray-500 font-medium">Contact</p>
                                            <p class="text-sm text-gray-700 truncate"><?php echo htmlspecialchars($event['contact']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Engagement spans full width on mobile, or takes remaining space -->
                                    <div class="flex items-center bg-gray-50 p-3 rounded-lg sm:col-span-2">
                                        <div class="w-8 h-8 bg-primary-50 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                            <i class="fas fa-heart text-primary-500 text-sm"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs text-gray-500 font-medium">Engagement</p>
                                            <p class="text-sm text-gray-700 engagement-count" data-event-id="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['total_likes']); ?> likes · <?php echo htmlspecialchars($event['total_comments']); ?> comments</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Funding Information -->
                            <div class="mb-6 bg-gray-50 p-4 rounded-lg flex-shrink-0">
                                <h3 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <i class="fas fa-hand-holding-usd text-primary-500"></i> Funding Status
                                </h3>

                                <?php if ($targetAmount): ?>
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                        <div class="bg-primary-500 h-2.5 rounded-full" style="width: <?php echo $progressPercent; ?>%"></div>
                                    </div>

                                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                                        <span>Raised: <?php echo htmlspecialchars($totalDonations); ?></span>
                                        <span>Target: <?php echo htmlspecialchars($targetAmount); ?></span>
                                    </div>

                                    <p class="text-sm font-medium <?php echo $totalFund > 0 ? 'text-amber-600' : 'text-green-600'; ?>">
                                        <?php if ($totalFund > 0): ?>
                                            Still needs: <?php echo htmlspecialchars($totalFund); ?>
                                        <?php else: ?>
                                            Funding goal reached!
                                        <?php endif; ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-600 italic flex items-center gap-2">
                                        <i class="fas fa-info-circle text-gray-400"></i>
                                        No funding information available for this event
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons (at bottom of card) -->
                            <div class="flex flex-wrap gap-2 mt-auto">
                                <button type="button" class="participate-btn px-3 py-1.5 text-xs bg-primary-500 hover:bg-primary-600 text-white rounded-md transition flex items-center gap-1" data-event-id="<?php echo $event['id']; ?>">
                                    <i class="fas fa-user-plus"></i> Participate
                                </button>
                                <button type="button" class="participants-btn px-3 py-1.5 text-xs bg-white border border-primary-500 text-primary-500 hover:bg-primary-50 rounded-md transition flex items-center gap-1" data-event-id="<?php echo $event['id']; ?>">
                                    <i class="fas fa-users"></i> Participants
                                </button>
                                <button type="button" class="creator-modal-btn px-3 py-1.5 text-xs bg-white border border-primary-500 text-primary-500 hover:bg-primary-50 rounded-md transition flex items-center gap-1" data-event-id="<?php echo $event['id']; ?>">
                                    <i class="fas fa-user-circle"></i> Creator
                                </button>
                                <button type="button" class="comments-modal-btn px-3 py-1.5 text-xs bg-white border border-blue-500 text-blue-600 hover:bg-blue-50 rounded-md transition flex items-center gap-1" data-event-id="<?php echo $event['id']; ?>">
                                    <i class="fas fa-comment-dots"></i> Comments
                                </button>
                                <!-- Like Button -->
                                <form method="post" style="display:inline;" class="like-form" data-event-id="<?php echo $event['id']; ?>">
                                    <input type="hidden" name="like_event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 text-xs border border-green-500 rounded-md transition flex items-center gap-1 like-btn <?php echo $isLiked ? 'bg-green-50 text-green-700' : 'bg-white text-green-600 hover:bg-green-50'; ?>">
                                        <i class="fas fa-thumbs-up"></i> Like (<span class="like-count"><?php echo (int)$event['total_likes']; ?></span>)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Load More Button -->
            <div class="flex justify-center mt-12">
                
            </div>
        </div>
    </section>

    <!-- Participants Modal -->
    <div id="participantsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
            <div class="bg-primary-500 text-white p-4 flex justify-between items-center">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-users"></i>
                    Event Participants
                </h2>
                <button id="closeModal" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-4">
                <div id="participantsContent" class="space-y-3">
                    <!-- Participants will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Creator Modal -->
    <div id="creatorModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
            <div class="bg-primary-500 text-white p-4 flex justify-between items-center">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-user-circle"></i>
                    Event Creator
                </h2>
                <button id="closeCreatorModal" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <div id="creatorContent">
                    <!-- Creator info will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Modal -->
    <div id="commentsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
            <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-comments"></i>
                    Comments
                </h2>
                <button id="closeCommentsModal" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-4 flex flex-col" style="max-height: calc(80vh - 80px);">
                <!-- Quick Comment Form -->
                <form id="modalCommentForm" class="flex gap-2 mb-4 p-3 bg-gray-50 rounded-lg">
                    <input type="hidden" id="modalEventId" name="comment_event_id" value="">
                    <input type="text" id="modalCommentText" name="comment_text" maxlength="255" placeholder="Add a comment..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors duration-200">Post</button>
                </form>

                <!-- Comments Content -->
                <div id="commentsContent" class="space-y-3 overflow-y-auto flex-1">
                    <!-- Comments will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Add CSS for button hover effect
        document.head.insertAdjacentHTML('beforeend', `
            <style>
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
            </style>
        `);

        // Creator Modal
        const creatorModalBtns = document.querySelectorAll('.creator-modal-btn');
        const creatorModal = document.getElementById('creatorModal');
        const closeCreatorModal = document.getElementById('closeCreatorModal');
        const creatorContent = document.getElementById('creatorContent');

        creatorModalBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');

                // Show modal
                creatorModal.classList.remove('hidden');

                // Show loading state
                creatorContent.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-primary-500 mb-4"></i>
                        <p class="text-gray-600">Loading creator information...</p>
                    </div>
                `;

                // Fetch creator info
                fetch('get_creator.php?event_id=' + encodeURIComponent(eventId))
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.creator) {
                            creatorContent.innerHTML = `
                            <div class="space-y-4">
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="w-12 h-12 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold text-xl mr-4">
                                        ${data.creator.full_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800 text-lg">${data.creator.full_name}</h3>
                                        <p class="text-sm text-gray-600">Event Creator</p>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-user text-primary-500 w-5"></i>
                                        <span class="text-gray-700">${data.creator.full_name}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-phone text-primary-500 w-5"></i>
                                        <span class="text-gray-700">${data.creator.contact_no}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-map-marker-alt text-primary-500 w-5"></i>
                                        <span class="text-gray-700">${data.creator.location}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        } else {
                            creatorContent.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-user-slash text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">Creator information not available.</p>
                            </div>
                        `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching creator info:', error);
                        creatorContent.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                            <p class="text-red-600">Error loading creator information.</p>
                        </div>
                    `;
                    });
            });
        });

        // Comments Modal
        const commentsModalBtns = document.querySelectorAll('.comments-modal-btn');
        const commentsModal = document.getElementById('commentsModal');
        const closeCommentsModal = document.getElementById('closeCommentsModal');
        const commentsContent = document.getElementById('commentsContent');
        const modalCommentForm = document.getElementById('modalCommentForm');
        const modalEventIdInput = document.getElementById('modalEventId');
        const modalCommentText = document.getElementById('modalCommentText');

        commentsModalBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');

                // Set event ID for comment form
                modalEventIdInput.value = eventId;

                // Show modal
                commentsModal.classList.remove('hidden');

                // Clear comment input
                modalCommentText.value = '';

                // Load comments
                loadComments(eventId);
            });
        });

        function loadComments(eventId) {
            console.log('Loading comments for event ID:', eventId);

            // Show loading state
            commentsContent.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-blue-500 mb-4"></i>
                    <p class="text-gray-600">Loading comments...</p>
                </div>
            `;

            // Fetch comments
            fetch('get_comments.php?event_id=' + encodeURIComponent(eventId))
                .then(res => {
                    console.log('Response status:', res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('Comments data received:', data);

                    if (data.success && data.comments && data.comments.length > 0) {
                        let commentsHtml = '';
                        data.comments.forEach(comment => {
                            commentsHtml += `
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">
                                        ${comment.username.charAt(0).toUpperCase()}
                                    </div>
                                    <span class="font-medium text-blue-600">${comment.username}</span>
                                </div>
                                <p class="text-gray-700 ml-11">${comment.comment_text}</p>
                            </div>
                        `;
                        });
                        commentsContent.innerHTML = commentsHtml;
                    } else {
                        console.log('No comments found or data.success is false');
                        commentsContent.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-comments text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600">No comments yet.</p>
                            <p class="text-sm text-gray-500 mt-2">Be the first to comment!</p>
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching comments:', error);
                    commentsContent.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                        <p class="text-red-600">Error loading comments.</p>
                        <p class="text-sm text-gray-500 mt-2">Check console for details</p>
                    </div>
                `;
                });
        }

        // Modal comment form submission
        modalCommentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const eventId = modalEventIdInput.value;
            const commentText = modalCommentText.value.trim();

            console.log('Submitting comment for event:', eventId, 'Comment:', commentText);

            if (!commentText) {
                alert('Please enter a comment before posting.');
                return;
            }

            fetch('comment_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'comment_event_id=' + encodeURIComponent(eventId) + '&comment_text=' + encodeURIComponent(commentText)
                })
                .then(res => {
                    console.log('Comment response status:', res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('Comment response data:', data);

                    if (data.success) {
                        // Clear the input
                        modalCommentText.value = '';

                        // Update the comment count in the main page
                        const engagementElement = document.querySelector(`.engagement-count[data-event-id="${eventId}"]`);
                        if (engagementElement) {
                            engagementElement.textContent = `${data.total_likes} likes · ${data.total_comments} comments`;
                        }

                        // Reload comments in modal
                        loadComments(eventId);
                    } else {
                        console.error('Comment submission failed:', data.message);
                        alert('Failed to post comment: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error posting comment:', error);
                    alert('Error posting comment. Please try again.');
                });
        });

        // Close modals
        closeCreatorModal.addEventListener('click', () => {
            creatorModal.classList.add('hidden');
        });

        closeCommentsModal.addEventListener('click', () => {
            commentsModal.classList.add('hidden');
        });

        // Close modals when clicking outside
        creatorModal.addEventListener('click', (e) => {
            if (e.target === creatorModal) {
                creatorModal.classList.add('hidden');
            }
        });

        commentsModal.addEventListener('click', (e) => {
            if (e.target === commentsModal) {
                commentsModal.classList.add('hidden');
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (!creatorModal.classList.contains('hidden')) {
                    creatorModal.classList.add('hidden');
                }
                if (!commentsModal.classList.contains('hidden')) {
                    commentsModal.classList.add('hidden');
                }
            }
        });

        // AJAX Like
        const likeForms = document.querySelectorAll('.like-form');
        likeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const eventId = this.getAttribute('data-event-id');
                const btn = this.querySelector('.like-btn');
                const countSpan = this.querySelector('.like-count');
                fetch('like_event.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'like_event_id=' + encodeURIComponent(eventId)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Update like button count
                            countSpan.textContent = data.total_likes;
                            btn.classList.toggle('bg-green-50', data.liked);
                            btn.classList.toggle('text-green-700', data.liked);

                            // Update engagement section
                            const engagementElement = document.querySelector(`.engagement-count[data-event-id="${eventId}"]`);
                            if (engagementElement) {
                                engagementElement.textContent = `${data.total_likes} likes · ${data.total_comments} comments`;
                            }
                        }
                    });
            });
        });

        // Participate Button AJAX
        const participateBtns = document.querySelectorAll('.participate-btn');
        participateBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');
                const button = this;

                // Disable button and show loading state
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Participating...';

                fetch('participate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'event_id=' + encodeURIComponent(eventId)
                    })
                    .then(res => res.json())
                    .then(data => {
                        // Reset button state
                        button.disabled = false;

                        if (data.status === 'success') {
                            // Show success message
                            button.innerHTML = '<i class="fas fa-check"></i> Joined!';
                            button.classList.remove('bg-primary-500', 'hover:bg-primary-600');
                            button.classList.add('bg-green-500', 'hover:bg-green-600');

                            // Reset after 3 seconds
                            setTimeout(() => {
                                button.innerHTML = '<i class="fas fa-user-plus"></i> Participate';
                                button.classList.remove('bg-green-500', 'hover:bg-green-600');
                                button.classList.add('bg-primary-500', 'hover:bg-primary-600');
                            }, 3000);
                        } else if (data.status === 'already_participated') {
                            // Show "You are in" message
                            button.innerHTML = '<i class="fas fa-user-check"></i> You are in';
                            button.classList.remove('bg-primary-500', 'hover:bg-primary-600');
                            button.classList.add('bg-blue-500', 'hover:bg-blue-600');

                            // Reset after 3 seconds
                            setTimeout(() => {
                                button.innerHTML = '<i class="fas fa-user-plus"></i> Participate';
                                button.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                                button.classList.add('bg-primary-500', 'hover:bg-primary-600');
                            }, 3000);
                        } else {
                            // Show error message
                            button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                            button.classList.remove('bg-primary-500', 'hover:bg-primary-600');
                            button.classList.add('bg-red-500', 'hover:bg-red-600');

                            // Reset after 3 seconds
                            setTimeout(() => {
                                button.innerHTML = '<i class="fas fa-user-plus"></i> Participate';
                                button.classList.remove('bg-red-500', 'hover:bg-red-600');
                                button.classList.add('bg-primary-500', 'hover:bg-primary-600');
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        button.classList.remove('bg-primary-500', 'hover:bg-primary-600');
                        button.classList.add('bg-red-500', 'hover:bg-red-600');

                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-user-plus"></i> Participate';
                            button.classList.remove('bg-red-500', 'hover:bg-red-600');
                            button.classList.add('bg-primary-500', 'hover:bg-primary-600');
                        }, 3000);
                    });
            });
        });

        // Participants Modal
        const participantsBtns = document.querySelectorAll('.participants-btn');
        const modal = document.getElementById('participantsModal');
        const closeModal = document.getElementById('closeModal');
        const participantsContent = document.getElementById('participantsContent');

        participantsBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');

                // Show modal
                modal.classList.remove('hidden');

                // Show loading state
                participantsContent.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-primary-500 mb-4"></i>
                        <p class="text-gray-600">Loading participants...</p>
                    </div>
                `;

                // Fetch participants
                fetch('show_participant.php?event_id=' + encodeURIComponent(eventId))
                    .then(res => res.text())
                    .then(data => {
                        // Extract participants data from the HTML response
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(data, 'text/html');
                        const participants = doc.querySelectorAll('.participant');

                        if (participants.length > 0) {
                            let participantsHtml = '';
                            participants.forEach(participant => {
                                const name = participant.querySelector('h3').textContent;
                                const username = participant.querySelector('p').textContent.replace('Username: ', '');

                                participantsHtml += `
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                        ${name.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">${name}</h3>
                                        <p class="text-sm text-gray-600">@${username}</p>
                                    </div>
                                </div>
                            `;
                            });
                            participantsContent.innerHTML = participantsHtml;
                        } else {
                            participantsContent.innerHTML = `
                                <div class="text-center py-8">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-600">No participants yet.</p>
                                    <p class="text-sm text-gray-500 mt-2">Be the first to join this event!</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching participants:', error);
                        participantsContent.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                                <p class="text-red-600">Error loading participants.</p>
                            </div>
                        `;
                    });
            });
        });

        // Close modal
        closeModal.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>