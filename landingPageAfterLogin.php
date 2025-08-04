<?php
include 'database.php';

session_start();

// Get logged-in user's profile picture
$profilePicQuery = "SELECT u.username, ep.profile_picture 
                   FROM users u 
                   LEFT JOIN enhanced_profiles ep ON u.username = ep.username 
                   WHERE u.username = :username";
$profileStmt = $pdo->prepare($profilePicQuery);
$profileStmt->execute(['username' => $_SESSION['username']]);
$userData = $profileStmt->fetch(PDO::FETCH_ASSOC);

// Fetch top 3 users based on best_user table
$topUsersQuery = "SELECT bu.username, bu.total_score, u.full_name, ep.profile_picture
                 FROM best_user bu
                 JOIN users u ON bu.username = u.username
                 LEFT JOIN enhanced_profiles ep ON bu.username = ep.username
                 ORDER BY bu.total_score DESC
                 LIMIT 3";
$topUsersStmt = $pdo->prepare($topUsersQuery);
$topUsersStmt->execute();
$topUsers = $topUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Citizen of the Month
$citizenOfMonthQuery = "
    SELECT username, COUNT(username) as count
    FROM todays_best
    GROUP BY username
    ORDER BY count DESC, username DESC
    LIMIT 1";
$citizenOfMonthStmt = $pdo->prepare($citizenOfMonthQuery);
$citizenOfMonthStmt->execute();
$citizenOfMonth = $citizenOfMonthStmt->fetch(PDO::FETCH_ASSOC);

$activeUserCount = 0;
$solved_reports = 0;

try {
    $query = "SELECT COUNT(*) as active_count FROM users";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $activeUserCount = $result['active_count'];
} catch (PDOException $e) {
    $activeUserCount = "Error fetching data";
}

try {
    $query = "SELECT COUNT(*) as reports_solved FROM report_issues WHERE status = 'solved'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $solved_reports = $result['reports_solved'];
} catch (PDOException $e) {
    $solved_reports = "Error fetching data";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shongskar - Community Safety Platform</title>
    <meta name="description" content="E-Shongskar connects communities for safety, emergency response, and neighborhood collaboration">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text);
            line-height: 1.6;
            background-color: var(--bg-light);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.2;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--primary);
            position: relative;
        }

        .section-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--secondary);
            margin: 15px auto 30px;
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 50px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: var(--radius);
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #3e8e41;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        
        /* Hero Section */
        .hero {
            padding-top: 120px;
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            overflow: hidden;
            position: relative;
        }

        .hero-container {
            display: flex;
            align-items: center;
            min-height: 80vh;
            position: relative;
        }

        .hero-content {
            flex: 1;
            padding-right: 40px;
            z-index: 2;
        }

        .hero-title {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 40px;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }

        .hero-image {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .hero-img {
            width: 100%;
            max-width: 600px;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.1));
        }

        .hero-pattern {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(76, 175, 80, 0.1) 0%, rgba(15, 46, 36, 0.1) 100%);
            top: 50%;
            right: -200px;
            transform: translateY(-50%);
            z-index: 0;
        }

        /* Services Section */
        .services {
            background-color: var(--bg-lighter);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .service-card {
            background-color: white;
            border-radius: var(--radius);
            padding: 30px;
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(76, 175, 80, 0.1);
            border-radius: 50%;
        }

        .service-icon img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .service-title {
            font-size: 1.25rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .service-description {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* User Ranking Section */
        .ranking {
            background-color: #f0f9ff;
            padding: 60px 0;
        }

        .ranking-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--primary);
            position: relative;
        }

        .ranking-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--secondary);
            margin: 15px auto 30px;
            border-radius: 2px;
        }

        .ranking-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 50px;
        }

        .top-users {
            background-color: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }

        .top-users-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--primary);
        }

        .top-users-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .top-users-list li {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .top-users-list li:last-child {
            border-bottom: none;
        }

        .user-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 500;
            color: white;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: var(--primary);
        }

        .user-username {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .user-score {
            font-weight: 500;
            color: var(--primary);
        }

        /* Features Section */
        .features {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            position: relative;
            overflow: hidden;
        }

        .features-pattern {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(76, 175, 80, 0.1) 0%, rgba(15, 46, 36, 0.1) 100%);
            bottom: -300px;
            left: -200px;
            z-index: 0;
        }

        .features-container {
            position: relative;
            z-index: 1;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background-color: white;
            border-radius: var(--radius);
            padding: 40px 30px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .feature-card:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: var(--secondary);
            transition: var(--transition);
            transform: scaleX(0);
            transform-origin: left;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .feature-card:hover:after {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
        }

        .feature-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .feature-title {
            font-size: 1.25rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .feature-description {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Stats Section */
        .stats {
            background-color: var(--bg-lighter);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .stat-card {
            background-color: white;
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(15, 46, 36, 0.1);
            border-radius: 50%;
        }

        .stat-icon img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
        }

        /* Testimonials Section */
        .testimonials {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .testimonial-card {
            background-color: white;
            border-radius: var(--radius);
            padding: 30px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .testimonial-content {
            margin-bottom: 20px;
            color: var(--text-light);
            font-style: italic;
            position: relative;
        }

        .testimonial-content:before {
            content: '"';
            font-size: 3rem;
            color: rgba(15, 46, 36, 0.1);
            position: absolute;
            top: -20px;
            left: -10px;
            font-family: serif;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info {
            flex: 1;
        }

        .author-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .author-rating {
            display: flex;
            gap: 5px;
        }

        .star {
            color: #FFD700;
            font-size: 1rem;
        }

        

        /* Animations */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

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

        .fade-in {
            animation: fadeIn 1s ease-out forwards;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        .delay-3 {
            animation-delay: 0.6s;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-container {
                flex-direction: column;
                text-align: center;
            }

            .hero-content {
                padding-right: 0;
                margin-bottom: 50px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-image {
                margin-top: 30px;
            }

            .hero-pattern {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .section {
                padding: 60px 0;
            }

            .section-title {
                font-size: 2rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .btn {
                width: 100%;
            }

            .mobile-menu-btn {
                display: block;
            }

            .nav {
                position: fixed;
                top: 80px;
                left: 0;
                width: 100%;
                background-color: var(--primary);
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                box-shadow: var(--shadow-lg);
                transform: translateY(-150%);
                transition: var(--transition);
                z-index: 999;
            }

            .nav.active {
                transform: translateY(0);
            }

            .nav-list {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 576px) {
            .section {
                padding: 50px 0;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="hero section">
        <div class="container hero-container">
            <div class="hero-content fade-in">
                <h1 class="hero-title">Empowering Communities Through Technology</h1>
                <p class="hero-subtitle">E-Shongskar connects neighbors, enhances safety, and fosters collaboration to create stronger, more resilient communities.</p>
                <div class="hero-buttons">
                    <a href="report_issue.php" class="btn btn-primary">Report Issue</a>
                    <a href="blood_donation.php" class="btn btn-outline">Search for Blood</a>
                </div>
            </div>
            <div class="hero-image fade-in delay-1">
                <img src="phoneDisplay.png" alt="E-Shongskar App Preview" class="hero-img">
            </div>
            <div class="hero-pattern"></div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services section">
        <div class="container">
            <h2 class="section-title">Our Comprehensive Services</h2>
            <p class="section-subtitle">Everything you need to stay connected, safe, and informed in your community</p>
            
            <div class="services-grid">
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <img src="ServiceReportImg.jpg" alt="Report Icon">
                    </div>
                    <h3 class="service-title">Incident Reporting</h3>
                    <p class="service-description">Quickly report neighborhood incidents with real-time alerts to keep everyone informed and safe.</p>
                </div>
                
                <div class="service-card fade-in delay-1">
                    <div class="service-icon">
                        <img src="ServiceEventImg.jpg" alt="Events Icon">
                    </div>
                    <h3 class="service-title">Community Events</h3>
                    <p class="service-description">Discover and organize local events to strengthen community bonds and participation.</p>
                </div>
                
                <div class="service-card fade-in delay-2">
                    <div class="service-icon">
                        <img src="ServiceSosImg.jpg" alt="SOS Icon">
                    </div>
                    <h3 class="service-title">Emergency SOS</h3>
                    <p class="service-description">Instant emergency alerts with your location sent to nearby community members and authorities.</p>
                </div>
                
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <img src="ServiceBloodImg.jpg" alt="Blood Donation Icon">
                    </div>
                    <h3 class="service-title">Blood Donation Network</h3>
                    <p class="service-description">Connect donors with recipients in critical need through our life-saving blood donation platform.</p>
                </div>
                
                <div class="service-card fade-in delay-1">
                    <div class="service-icon">
                        <img src="ServiceChatImg.jpg" alt="Chat Icon">
                    </div>
                    <h3 class="service-title">Neighborhood Chat</h3>
                    <p class="service-description">Secure messaging with nearby residents for quick communication and coordination.</p>
                </div>
                
                <div class="service-card fade-in delay-2">
                    <div class="service-icon">
                        <img src="ServiceLocationImg.jpg" alt="Location Icon">
                    </div>
                    <h3 class="service-title">Location Services</h3>
                    <p class="service-description">Share your location during emergencies or when organizing community activities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- User Ranking Section -->
    <section class="section" style="background-color: #f0f9ff;">
        <div class="container">
            <h2 class="section-title">Community Champions</h2>
            <p class="section-subtitle">Recognizing our most impactful community members</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Top Users Ranking - Enhanced Design -->
                <div class="bg-white rounded-lg shadow-lg p-6 fade-in" style="overflow: hidden; position: relative; border-top: 5px solid var(--secondary);">
                    <div style="position: absolute; top: -30px; right: -30px; width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.05)); z-index: 0;"></div>
                    <div style="position: absolute; bottom: -20px; left: -20px; width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, rgba(15, 46, 36, 0.1), rgba(15, 46, 36, 0.05)); z-index: 0;"></div>
                    
                    <div style="position: relative; z-index: 1;">
                        <h3 class="text-xl font-bold mb-6 text-center" style="color: var(--primary); font-size: 1.5rem; position: relative; display: inline-block; left: 50%; transform: translateX(-50%);">
                            <i class="fas fa-trophy" style="color: var(--secondary); margin-right: 10px; font-size: 1.8rem; vertical-align: middle;"></i>
                            Top Community Contributors
                        </h3>
                        
                        <div style="margin: 30px 0;">
                            <?php foreach ($topUsers as $index => $user): 
                                $rankStyle = '';
                                $rankIcon = '';
                                $bgColor = '';
                                
                                if ($index === 0) {
                                    $rankStyle = 'gold';
                                    $rankIcon = '👑';
                                    $bgColor = 'linear-gradient(135deg, #fef9c3 0%, #fde047 100%)';
                                } elseif ($index === 1) {
                                    $rankStyle = 'silver';
                                    $rankIcon = '🥈';
                                    $bgColor = 'linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%)';
                                } else {
                                    $rankStyle = 'bronze';
                                    $rankIcon = '🥉';
                                    $bgColor = 'linear-gradient(135deg, #fff7ed 0%, #fdba74 100%)';
                                }
                            ?>
                                <div style="display: flex; align-items: center; margin-bottom: 20px; padding: 15px; border-radius: 12px; background: <?= $bgColor ?>; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transform: scale(<?= 1 - ($index * 0.03) ?>); transition: all 0.3s ease; position: relative;" 
                                     onmouseover="this.style.transform='scale(<?= 1.02 - ($index * 0.03) ?>)'; this.style.boxShadow='0 8px 25px rgba(0, 0, 0, 0.15)';" 
                                     onmouseout="this.style.transform='scale(<?= 1 - ($index * 0.03) ?>)'; this.style.boxShadow='0 4px 15px rgba(0, 0, 0, 0.1)';">
                                    
                                    <!-- Large Rank Icon -->
                                    <div style="position: absolute; top: -10px; right: -10px; background-color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15); font-size: 24px;">
                                        <?= $rankIcon ?>
                                    </div>
                                    
                                    <!-- Rank Number with Larger Circle -->
                                    <div style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; color: white; margin-right: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); background-color: <?= $index === 0 ? '#eab308' : ($index === 1 ? '#64748b' : '#f97316') ?>;">
                                        <?= $index + 1 ?>
                                    </div>
                                    
                                    <!-- User Avatar - Larger Size -->
                                    <div style="width: 65px; height: 65px; border-radius: 50%; overflow: hidden; margin-right: 20px; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                        <?php if ($user['profile_picture']): ?>
                                            <img src="data:image/jpeg;base64,<?= base64_encode($user['profile_picture']) ?>" 
                                                alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-light), var(--primary)); color: white; font-size: 1.8rem; font-weight: bold;">
                                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- User Info with Enhanced Typography -->
                                    <div style="flex: 1;">
                                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary); margin-bottom: 3px;">
                                            <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--text-light);">
                                            @<?= htmlspecialchars($user['username']) ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Score Badge -->
                                    <div style="background: <?= $index === 0 ? 'rgba(234, 179, 8, 0.2)' : ($index === 1 ? 'rgba(100, 116, 139, 0.2)' : 'rgba(249, 115, 22, 0.2)') ?>; padding: 8px 15px; border-radius: 20px; text-align: center;">
                                        <div style="font-weight: 700; font-size: 1.4rem; color: <?= $index === 0 ? '#b45309' : ($index === 1 ? '#475569' : '#c2410c') ?>;">
                                            <?= number_format($user['total_score']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">
                                            points
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($topUsers) === 0): ?>
                                <div style="text-align: center; padding: 40px 0; color: var(--text-light);">
                                    <i class="fas fa-users" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 15px; display: block;"></i>
                                    <div style="font-size: 1.1rem; font-weight: 500;">No ranking data available yet</div>
                                    <p style="margin-top: 10px; font-size: 0.9rem;">Be the first to contribute and earn your spot!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Citizen of the Month -->
                <div class="bg-white rounded-lg shadow-md p-6 fade-in delay-1">
                    <h3 class="text-xl font-bold mb-4 text-center" style="color: var(--primary);">Citizen of the Month</h3>
                    
                    <?php if ($citizenOfMonth): ?>
                        <div class="flex flex-col items-center">
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 mb-4" style="border-color: #0f766e;">
                                <?php 
                                    // Fetch profile picture for citizen of the month
                                    $citizenQuery = "SELECT ep.profile_picture, u.full_name 
                                                   FROM users u 
                                                   LEFT JOIN enhanced_profiles ep ON u.username = ep.username 
                                                   WHERE u.username = :username";
                                    $citizenStmt = $pdo->prepare($citizenQuery);
                                    $citizenStmt->execute(['username' => $citizenOfMonth['username']]);
                                    $citizenData = $citizenStmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                
                                
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold mb-1" style="color: var(--primary);">
                                    <?= htmlspecialchars($citizenData['full_name'] ?? $citizenOfMonth['username']) ?>
                                </div>
                                
                                
                                <div class="inline-block px-4 py-1 rounded-full text-sm font-semibold" 
                                     style="background-color: #ecfdf5; color: #047857;">
                                    <?= number_format($citizenOfMonth['count']) ?> times awarded
                                </div>
                                
                                <div class="mt-4 text-gray-600">
                                    <p>Recognized for exceptional community contributions and consistent positive impact.</p>
                                </div>
                                
                                <div class="mt-5">
                                    <div class="p-3 rounded-md" style="background-color: #f0fdf4; border-left: 4px solid #22c55e;">
                                        <div class="text-sm font-semibold" style="color: #15803d;">Achievement</div>
                                        <div>Making our community a better place for everyone!</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <div class="mb-3">No citizen of the month selected yet</div>
                            <p>Continue contributing to become our next featured citizen!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features section">
        <div class="features-pattern"></div>
        <div class="container features-container">
            <h2 class="section-title">Key Features</h2>
            <p class="section-subtitle">Designed to keep you safe and connected with your community</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <img src="alert-removebg-preview.png" alt="Safety Alerts Icon">
                    </div>
                    <h3 class="feature-title">Real-time Safety Alerts</h3>
                    <p class="feature-description">Receive instant notifications about incidents in your area to stay situationally aware and avoid potential dangers.</p>
                </div>
                
                <div class="feature-card fade-in delay-1">
                    <div class="feature-icon">
                        <img src="connect-removebg-preview.png" alt="Stay Informed Icon">
                    </div>
                    <h3 class="feature-title">Community Network</h3>
                    <p class="feature-description">Build connections with neighbors to share information, resources, and support when needed most.</p>
                </div>
                
                <div class="feature-card fade-in delay-2">
                    <div class="feature-icon">
                        <img src="sos-removebg-preview.png" alt="SOS Feature Icon">
                    </div>
                    <h3 class="feature-title">Emergency Response</h3>
                    <p class="feature-description">One-tap SOS with automatic location sharing to alert nearby help in critical situations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats section">
        <div class="container">
            <h2 class="section-title">Our Impact</h2>
            <p class="section-subtitle">Join thousands of community members making their neighborhoods safer</p>
            
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-icon">
                        <img src="people-removebg-preview.png" alt="Community Members Icon">
                    </div>
                    <div class="stat-number"><?= $activeUserCount ?></div>
                    <div class="stat-label">Active Community Members</div>
                </div>
                
                <div class="stat-card fade-in delay-1">
                    <div class="stat-icon">
                        <img src="360_F_288138556_2EnxidqIM2bzlpIySUIg8P12pb9Eqw3i.jpg" alt="Issues Resolved Icon">
                    </div>
                    <div class="stat-number"><?= $solved_reports ?></div>
                    <div class="stat-label">Issues Successfully Resolved</div>
                </div>
                
                <div class="stat-card fade-in delay-2">
                    <div class="stat-icon">
                        <img src="blood.jpg" alt="Blood Donations Icon">
                    </div>
                    <div class="stat-number">***</div>
                    <div class="stat-label">Life-saving Blood Donations</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials section">
        <div class="container">
            <h2 class="section-title">Community Voices</h2>
            <p class="section-subtitle">Hear what our users say about their E-Shongskar experience</p>
            
            <div class="testimonials-grid">
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        "E-Shongskar transformed how our neighborhood communicates. When we had a break-in attempt, the alert system helped us respond immediately and prevent theft."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="monpura.jpg" alt="Nowar Sharmin">
                        </div>
                        <div class="author-info">
                            <div class="author-name">Nowar Sharmin</div>
                            <div class="author-rating">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card fade-in delay-1">
                    <div class="testimonial-content">
                        "As someone who lives alone, the SOS feature gives me peace of mind. I know help is just one tap away if I ever need it."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="chris.jpg" alt="Michael Chen">
                        </div>
                        <div class="author-info">
                            <div class="author-name">Michael Chen</div>
                            <div class="author-rating">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card fade-in delay-2">
                    <div class="testimonial-content">
                        "The blood donation network saved my nephew's life when he needed an urgent transfusion. The community response was overwhelming and heartwarming."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="safa.jpg" alt="Safia Kabir">
                        </div>
                        <div class="author-info">
                            <div class="author-name">Safia Kabir</div>
                            <div class="author-rating">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('.nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        
        // Header scroll effect
        const header = document.querySelector('.header');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Animation on scroll
        const fadeElements = document.querySelectorAll('.fade-in');
        
        const fadeInOnScroll = () => {
            fadeElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        };
        
        // Initialize elements as invisible
        fadeElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        });
        
        window.addEventListener('scroll', fadeInOnScroll);
        window.addEventListener('load', fadeInOnScroll);
    </script>
</body>
</html>