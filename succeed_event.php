<?php
session_start();
include('database.php');

if (!isset($_SESSION['username'])) {
    echo "Please log in to view the events.";
    exit();
}

$username = $_SESSION['username'];

$sql_user = "SELECT location, verified FROM users WHERE username = :username";
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->bindParam(':username', $username);
$stmt_user->execute();

if ($stmt_user->rowCount() > 0) {
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $user_location = $user['location'];
    $user_verified = $user['verified'];

    $sql = "
        SELECT
            se.*, 
            e.location,
            e.event_description,
            e.username AS event_owner,
            u.verified AS event_owner_verified
        FROM succeed_events se
        JOIN events e ON se.event_id = e.id
        LEFT JOIN users u ON e.username = u.username
        WHERE e.location = :location
        ORDER BY se.finished_date DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':location', $user_location);

    try {
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching data: " . $e->getMessage();
    }
}
if ($user) {
    $user_location = $user['location'];
    $user_verified = $user['verified'];

    // Query events with action status determination
    $sql = "
        SELECT DISTINCT
            se.event_id,
            se.done_u,
            se.done_v,
            se.done_a,
            se.finished_date,
            e.location,
            e.event_description,
            e.username AS event_owner,
            CASE 
                WHEN :user_verified IS NULL THEN 'no_button'
                WHEN se.done_v = 'yes' THEN 'show_checkmark'
                ELSE 'show_button'
            END AS action_status
        FROM succeed_events se
        JOIN events e ON se.event_id = e.id
        WHERE e.location = :user_location
        ORDER BY se.finished_date DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_verified', $user_verified);
    $stmt->bindParam(':user_location', $user_location);

    try {
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching data: " . $e->getMessage();
        exit();
    }

    // Handle button click to update the `done_v` column
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
        $event_id = $_POST['event_id'];

        $update_sql = "
            UPDATE succeed_events
            SET done_v = 'yes'
            WHERE event_id = :event_id
              AND EXISTS (
                  SELECT 1 FROM users
                  WHERE username = :logged_in_username
                  AND verified IS NOT NULL
              )
        ";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(':event_id', $event_id);
        $update_stmt->bindParam(':logged_in_username', $username);

        try {
            $update_stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            echo "Error updating verification status: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Succeed Events | E-Shongskar</title>
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
                            500: '#0b493e', /* Main primary color */
                            600: '#094236',
                            700: '#07362d',
                            800: '#052b24',
                            900: '#021d18',
                        },
                        secondary: {
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
                        },
                        gradient: {
                            start: '#e0f7fa', /* Start color of background gradient */
                            end: '#c8e6c9',   /* End color of background gradient */
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.01)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    },
                    maxWidth: {
                        container: '1280px',
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
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 5s ease-in-out infinite;
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
        
        /* Table styling */
        .events-table {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .events-table th {
            background-color: #0b493e;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }
        
        .events-table tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        
        .events-table tr:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }
        
        .events-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .verified-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0b493e, #207567);
            color: white;
            box-shadow: 0 3px 6px rgba(11, 73, 62, 0.2);
        }
    </style>
</head>

<body style="background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%); background-attachment: fixed; min-height: 100vh;" class="text-gray-800 font-sans">
    <!-- Background pattern with circles -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>
    
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
                <h1 class="text-4xl lg:text-5xl font-bold mb-4 text-center opacity-0 animate-fadeIn">Successful Events</h1>
                <p class="text-lg text-primary-800/80 mb-8 max-w-2xl text-center opacity-0 animate-fadeIn delay-100">Discover verified and successfully completed events in your community!</p>
                
                <div class="flex flex-wrap gap-3 justify-center opacity-0 animate-fadeIn delay-200">
                    <a href="event.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-6 py-3 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                        <i class="fas fa-calendar-plus"></i> 
                        <span>Create Event</span>
                    </a>
                    <a href="response_events.php" class="bg-primary-500/20 hover:bg-primary-500/30 text-primary-800 font-medium px-6 py-3 rounded-full flex items-center gap-2 transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                        <i class="fas fa-arrow-left"></i> 
                        <span>Back to Events</span>
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

        <?php if (!empty($events)): ?>
            <div class="opacity-0 animate-fadeIn delay-200">
                <div class="events-table overflow-x-auto rounded-2xl mb-8">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left">
                                <th class="px-4 py-3">Event ID</th>
                                <th class="px-4 py-3">Location</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">User Verified</th>
                                <th class="px-4 py-3">Representative Verified</th>
                                <th class="px-4 py-3">Admin Verified</th>
                                <th class="px-4 py-3">Finished Date</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3"></th> <!-- Placeholder for the verified logo -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr class="hover:bg-white/50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-primary-600"><?= htmlspecialchars($event['event_id']) ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($event['location']) ?></td>
                                    <td class="px-4 py-3 max-w-xs truncate"><?= htmlspecialchars($event['event_description']) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($event['done_u'] === 'yes'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($event['done_v'] === 'yes'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($event['done_a'] === 'yes'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars(date('M d, Y', strtotime($event['finished_date']))) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($event['action_status'] === 'show_button'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['event_id']) ?>">
                                                <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-all duration-300 shadow-sm hover:shadow-md flex items-center gap-1">
                                                    <i class="fas fa-check-circle"></i> Verify
                                                </button>
                                            </form>
                                        <?php elseif ($event['action_status'] === 'no_button'): ?>
                                            <span class="text-gray-400 text-sm">No Action Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($event['done_v'] === 'yes'): ?>
                                            <div class="verified-badge">
                                                <i class="fas fa-check text-xs"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-8 text-center shadow-card max-w-md mx-auto opacity-0 animate-fadeIn delay-200">
                <div class="text-5xl text-primary-500 mb-4">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-primary-800">No Events Found</h3>
                <p class="text-gray-600 mb-6">No successful events have been found for your location yet.</p>
                <a href="event.php" class="bg-primary-500 hover:bg-primary-600 text-white font-medium px-6 py-3 rounded-full inline-flex items-center gap-2 transition-all duration-300">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create an Event</span>
                </a>
            </div>
        <?php endif; ?>
        </div>
    </section>
    
    <?php include 'footer.php'; ?>

    <script>
        // Enhance background circle animations
        document.addEventListener('DOMContentLoaded', function() {
            const circle1 = document.querySelector('.decorative-circle-1');
            const circle2 = document.querySelector('.decorative-circle-2');
            const circle3 = document.querySelector('.decorative-circle-3');
            
            if (circle1 && circle2 && circle3) {
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
        
        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseover', () => {
                row.classList.add('bg-opacity-50');
            });
            row.addEventListener('mouseout', () => {
                row.classList.remove('bg-opacity-50');
            });
        });
    </script>
</body>

</html>
