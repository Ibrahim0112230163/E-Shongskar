<?php
include 'database.php'; 
session_start();

// Automatically delete expired alerts (same logic as admin_alert.php)
$deleteExpiredAlertsQuery = "
    DELETE FROM alerts 
    WHERE DATE_ADD(date, INTERVAL duration DAY) < CURDATE()
";
$pdo->exec($deleteExpiredAlertsQuery);

// alerts by date and location, grouped by location
$searchLocation = isset($_POST['search_location']) ? $_POST['search_location'] : '';
$specificAlertId = isset($_GET['alert_id']) ? (int)$_GET['alert_id'] : null;

// Query to fetch all alerts grouped by location
if ($specificAlertId) {
    // Show specific alert
    $query = "
        SELECT alert_id, description, date, duration, location
        FROM alerts
        WHERE alert_id = :alert_id
        ORDER BY date DESC
    ";
} else {
    // Group alerts by location
    $query = "
        SELECT location, COUNT(*) as alert_count, GROUP_CONCAT(alert_id ORDER BY date ASC) as alert_ids, 
               GROUP_CONCAT(description ORDER BY date ASC) as descriptions, 
               GROUP_CONCAT(date ORDER BY date ASC) as dates, 
               GROUP_CONCAT(duration ORDER BY date ASC) as durations
        FROM alerts
        GROUP BY location
        ORDER BY location ASC
    ";

    // Search
    if ($searchLocation) {
        $query = "
            SELECT location, COUNT(*) as alert_count, GROUP_CONCAT(alert_id ORDER BY date ASC) as alert_ids, 
                   GROUP_CONCAT(description ORDER BY date ASC) as descriptions, 
                   GROUP_CONCAT(date ORDER BY date ASC) as dates, 
                   GROUP_CONCAT(duration ORDER BY date ASC) as durations
            FROM alerts
            WHERE location LIKE :searchLocation
            GROUP BY location
            ORDER BY location ASC
        ";
    }
}

$stmt = $pdo->prepare($query);
if ($specificAlertId) {
    $stmt->execute(['alert_id' => $specificAlertId]);
} else {
    if ($searchLocation) {
        $stmt->bindValue(':searchLocation', '%' . $searchLocation . '%');
    }
    $stmt->execute();
}

$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Awareness Alerts | E-Shongskar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                },
            },
            corePlugins: {
                preflight: false, // This ensures Tailwind doesn't override Bootstrap styles
            },
        }
    </script>
    <style>
        :root {
            --primary: #0b493e;
            --primary-light: #094236;
            --primary-rgb: 11, 73, 62;
            --secondary: #a8dadc;
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 5s ease-in-out infinite;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
            color: var(--text);
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
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Hero section specific styles */
        .hero-section {
            color: var(--text);
            text-align: center;
            position: relative;
            width: 100%;
            padding-top: 120px;
            padding-bottom: 30px;
        }
        
        .hero-section h2 {
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }
        
        .hero-section p {
            font-size: 1.1rem;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .alert-container {
            margin: 10px auto 20px;
            width: 70%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
            animation: fadeIn 0.8s ease-in-out forwards;
            opacity: 0;
            position: relative;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.07);
        }
        
        .alert-content {
            display: flex;
            flex-direction: column;
            padding: 24px;
            gap: 20px;
        }
        
        .alert-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0.5rem 0;
        }
        
        .alert-description {
            background-color: rgba(248, 250, 252, 0.7);
            padding: 18px;
            border-radius: var(--radius);
            margin-top: 15px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            position: relative;
        }
        
        .alert-description:hover {
            background-color: rgba(248, 250, 252, 0.9);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .alert-description p {
            margin: 0;
            line-height: 1.6;
            color: var(--text-light);
        }
        
        .alert-description::before {
            content: '';
            position: absolute;
            top: 0;
            left: -4px;
            height: 100%;
            width: 4px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-light));
        }
        
        .top-info {
            font-size: 13.5px;
            padding: 16px 24px 14px;
            font-weight: 600;
            color: var(--text-lighter);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: rgba(248, 250, 252, 0.5);
        }
        .alert-btn {
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(11, 73, 62, 0.2);
        }
        
        .alert-btn:hover {
            background: linear-gradient(to right, var(--primary-light), var(--primary));
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(11, 73, 62, 0.3);
            color: white;
        }
        
        .alert-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.6s ease;
        }
        
        .alert-btn:hover:before {
            left: 100%;
        }
        
        .alert-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 8px 0;
        }
        
        .alert-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: var(--text-light);
        }
        
        .alert-meta-item i {
            color: var(--primary);
            font-size: 1rem;
        }
        
        /* Responsive adjustments for alert container */
        @media (max-width: 1200px) {
            .alert-container {
                width: 80%;
            }
        }
        
        @media (max-width: 992px) {
            .alert-container {
                width: 85%;
            }
        }
        
        @media (max-width: 768px) {
            .alert-container {
                width: 90%;
            }
            
            .hero-section {
                padding-top: 80px;
                padding-bottom: 40px;
            }
            
            .alert-content {
                padding: 20px;
            }
            
            .top-info {
                padding: 12px 18px;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
        
        @media (max-width: 576px) {
            .alert-container {
                width: 95%;
            }
            
            .alert-description {
                padding: 15px;
            }
            
            .hero-title-transparent {
                font-size: 1.8rem;
            }
            
            .hero-subtitle-transparent {
                font-size: 1rem;
            }
        }
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 1.2rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.5);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .back-button:hover {
            background: rgba(255,255,255,0.5);
            transform: translateX(-3px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.08);
        }
        .no-alerts {
            text-align: center;
            padding: 60px 40px;
            color: var(--text-light);
            font-size: 17px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4em 0.8em;
            font-size: 85%;
            font-weight: 600;
            line-height: 1;
            color: white;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50px;
            letter-spacing: 0.3px;
        }
        
        .bg-secondary {
            background: linear-gradient(135deg, #4CAF50, #388E3C);
            box-shadow: 0 3px 6px rgba(76, 175, 80, 0.2);
        }
        
        .rounded-pill {
            border-radius: 50rem;
        }
        
        .p-2 {
            padding: 0.5rem;
        }
        
        .me-1 {
            margin-right: 0.25rem;
        }
        
        .me-2 {
            margin-right: 0.5rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .text-primary {
            color: var(--primary);
        }
        
        .text-secondary {
            color: var(--secondary);
        }
        
        .hidden {
            display: none;
        }
        /* Additional decorative circles */
        .decorative-circle-1 {
            position: fixed;
            top: 20%;
            right: 10%;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.05);
            z-index: -1;
        }
        
        .decorative-circle-2 {
            position: fixed;
            bottom: 15%;
            left: 5%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.04);
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
        
        /* Glass morphism effect for search box */
        .glass-input {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-sm);
            color: var(--text);
            transition: all 0.3s ease;
        }
        
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: var(--shadow-md);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .glass-button {
            background: rgba(var(--primary-rgb, 15, 46, 36), 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(var(--primary-rgb, 15, 46, 36), 0.3);
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .glass-button:hover {
            background: rgba(var(--primary-rgb, 15, 46, 36), 0.25);
            transform: translateY(-2px);
        }
        .hero-glass-card {
            background: rgba(255,255,255,0.82);
            backdrop-filter: blur(18px) saturate(180%);
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(60,72,100,0.13);
            padding: 48px 36px 36px 36px;
            max-width: 600px;
            margin: 0 auto 0 auto;
            text-align: center;
            animation: fadeInModal 0.7s cubic-bezier(.4,2,.6,1) both;
        }
        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 18px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            line-height: 1.1;
        }
        .hero-subtitle {
            font-size: 1.18rem;
            color: #2d3a3a;
            font-weight: 500;
            margin-bottom: 0.5em;
            line-height: 1.6;
        }
        .hero-subtext {
            color: #4CAF50;
            font-size: 1.05em;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        @media (max-width: 700px) {
            .hero-glass-card { padding: 28px 10px 18px 10px; }
            .hero-title { font-size: 2rem; }
        }
        .hero-title-transparent {
            font-size: 2.8rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 18px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            line-height: 1.1;
            text-align: center;
        }
        .hero-subtitle-transparent {
            font-size: 1.18rem;
            color: #2d3a3a;
            font-weight: 500;
            margin-bottom: 0.5em;
            line-height: 1.6;
            text-align: center;
        }
        .hero-subtext-transparent {
            color: #4CAF50;
            font-size: 1.05em;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        @media (max-width: 700px) {
            .hero-title-transparent { font-size: 2rem; }
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%); background-attachment: fixed; min-height: 100vh;" class="text-gray-800 font-sans">
    <?php include 'header.php'; ?>
    
    <!-- Animated background pattern -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>
    
    <!-- Hero Section (transparent, 70% width, centered) -->
    <section class="relative overflow-hidden hero-section" style="margin-top: -70px; background: transparent;">
        <div class="container mx-auto px-4 py-8 relative z-10" style="max-width: 1280px; width: 70%;">
            <div class="flex flex-col items-center mt-4">
                <h2 class="hero-title-transparent">Public Awareness Alerts</h2>
                <p class="hero-subtitle-transparent">Public awareness is crucial for addressing natural calamities, government updates, and emergency information.<br>
                <span class="hero-subtext-transparent">Stay informed and prepared.</span></p>
                <div class="w-full max-w-md mx-auto mt-6 mb-0">
                    <form method="post" class="flex items-center gap-2">
                        <input type="text" name="search_location" placeholder="Search Location" value="<?= htmlspecialchars($searchLocation) ?>" 
                            class="w-full px-4 py-3 rounded-lg glass-input" 
                            style="border-radius: var(--radius);">
                        <button type="submit" class="px-4 py-3 rounded-lg glass-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        
    </section>

    <?php if ($specificAlertId && count($alerts) > 0): ?>
        <!-- Display specific alert -->
        <?php foreach ($alerts as $alert): ?>
            <div class="alert-container">
                <div class="top-info">
                    <div>
                        <span class="badge bg-secondary p-2 rounded-pill">Alert ID: <?= htmlspecialchars($alert['alert_id']) ?></span>
                    </div>
                    <div>
                        <i class="far fa-calendar-alt me-1"></i> <?= date('F j, Y', strtotime($alert['date'])) ?>
                    </div>
                </div>
                <div class="alert-content">
                    <div class="alert-text">
                        <div class="alert-meta">
                            <div class="alert-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><strong>Location:</strong> <?= htmlspecialchars($alert['location']) ?></span>
                            </div>
                            <div class="alert-meta-item">
                                <i class="fas fa-clock"></i>
                                <span><strong>Duration:</strong> <?= htmlspecialchars($alert['duration']) ?></span>
                            </div>
                        </div>
                        
                        <div class="alert-description">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            <strong>Description:</strong>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($alert['description'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
    <?php elseif ($searchLocation && count($alerts) > 0): ?>
        <!-- Display matched alerts -->
        <?php 
            $alert_ids = explode(',', $alerts[0]['alert_ids']);
            $descriptions = explode(',', $alerts[0]['descriptions']);
            $dates = explode(',', $alerts[0]['dates']);
            $durations = explode(',', $alerts[0]['durations']);
            
            for ($i = 0; $i < count($alert_ids); $i++):
        ?>
            <div class="alert-container">
                <div class="top-info">
                    <div>
                        <span class="badge bg-secondary p-2 rounded-pill">Alert ID: <?= htmlspecialchars($alert_ids[$i]) ?></span>
                    </div>
                    <div>
                        <i class="far fa-calendar-alt me-1"></i> <?= date('F j, Y', strtotime($dates[$i])) ?>
                    </div>
                </div>
                <div class="alert-content">
                    <div class="alert-text">
                        <div class="alert-meta">
                            <div class="alert-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><strong>Location:</strong> <?= htmlspecialchars($alerts[0]['location']) ?></span>
                            </div>
                            <div class="alert-meta-item">
                                <i class="fas fa-clock"></i>
                                <span><strong>Duration:</strong> <?= htmlspecialchars($durations[$i]) ?></span>
                            </div>
                        </div>
                        
                        <div class="alert-description">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            <strong>Description:</strong>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($descriptions[$i])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
        
    <?php elseif (!$searchLocation): ?>
        <!-- Display all alerts -->
        <?php foreach ($alerts as $alert): ?>
            <?php 
                $alert_ids = explode(',', $alert['alert_ids']);
                $descriptions = explode(',', $alert['descriptions']);
                $dates = explode(',', $alert['dates']);
                $durations = explode(',', $alert['durations']);
                
                for ($i = 0; $i < count($alert_ids); $i++):
            ?>
                <div class="alert-container">
                    <div class="top-info">
                        <div>
                            <span class="badge bg-secondary p-2 rounded-pill">Alert ID: <?= htmlspecialchars($alert_ids[$i]) ?></span>
                        </div>
                        <div>
                            <i class="far fa-calendar-alt me-1"></i> <?= date('F j, Y', strtotime($dates[$i])) ?>
                        </div>
                    </div>
                    <div class="alert-content">
                        <div class="alert-text">
                            <div class="alert-meta">
                                <div class="alert-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><strong>Location:</strong> <?= htmlspecialchars($alert['location']) ?></span>
                                </div>
                                <div class="alert-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><strong>Duration:</strong> <?= htmlspecialchars($durations[$i]) ?></span>
                                </div>
                            </div>
                            
                            <div class="alert-description">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                <strong>Description:</strong>
                                <p class="mt-2"><?= nl2br(htmlspecialchars($descriptions[$i])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert-container">
            <div class="no-alerts">
                <i class="fas fa-info-circle text-secondary mb-3" style="font-size: 3rem;"></i>
                <p>No alerts available for the searched location.</p>
                <a href="alert.php" class="alert-btn mt-3"><i class="fas fa-sync-alt me-2"></i> View All Alerts</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Back to top button -->
    <div id="back-to-top" class="hidden fixed bottom-8 right-8 z-50 p-3 text-white rounded-full shadow-lg cursor-pointer transition-all duration-500 ease-in-out" 
         style="background: linear-gradient(135deg, var(--primary), var(--primary-light)); box-shadow: 0 6px 15px rgba(11, 73, 62, 0.25);"
         onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show or hide the back to top button based on scroll position with smooth animation
        window.onscroll = function() {
            const backToTopButton = document.getElementById('back-to-top');
            
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                backToTopButton.classList.remove('hidden');
                backToTopButton.classList.add('flex');
                setTimeout(() => {
                    backToTopButton.style.opacity = '1';
                    backToTopButton.style.transform = 'translateY(0)';
                }, 10);
            } else {
                backToTopButton.style.opacity = '0';
                backToTopButton.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    backToTopButton.classList.add('hidden');
                    backToTopButton.classList.remove('flex');
                }, 300);
            }
        };
        
        // Add animation delay to alert containers for smoother loading
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize back-to-top button opacity
            const backToTopButton = document.getElementById('back-to-top');
            if (backToTopButton) {
                backToTopButton.style.opacity = '0';
                backToTopButton.style.transform = 'translateY(20px)';
            }
            
            // Animate alert containers with staggered delay
            const alertContainers = document.querySelectorAll('.alert-container');
            alertContainers.forEach((container, index) => {
                container.style.animationDelay = `${0.3 + (index * 0.15)}s`;
            });
            
            // Add hover effect to description boxes
            const descriptions = document.querySelectorAll('.alert-description');
            descriptions.forEach(desc => {
                desc.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                desc.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>