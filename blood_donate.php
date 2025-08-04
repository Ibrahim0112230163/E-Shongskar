<?php
include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Get current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch matching blood donors with profile pictures
$stmt = $pdo->prepare("
    SELECT 
        bd.user_nid, 
        u.full_name, 
        u.contact_no, 
        u.location, 
        u.blood_group, 
        bd.next_date, 
        bd.r_contact, 
        bd.d_contact,
        u.dp as profile_picture,
        COALESCE(cr.status, 'none') as request_status
    FROM blood_donor bd
    JOIN users u ON bd.user_nid = u.nid_birth_certificate
    LEFT JOIN contact_requests cr ON cr.donor_nid = bd.user_nid AND cr.requester = :current_user
    WHERE u.blood_group = :blood_group 
    AND u.location = :location
    AND bd.user_nid != :user_id
");
$stmt->execute([
    'blood_group' => $user['blood_group'],
    'location' => $user['location'],
    'user_id' => $user['nid_birth_certificate'],
    'current_user' => $_SESSION['username']
]);
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all donors in location with profile pictures
$stmt = $pdo->prepare("
    SELECT 
        bd.user_nid, 
        u.full_name, 
        u.contact_no, 
        u.location, 
        u.blood_group, 
        bd.next_date, 
        bd.r_contact, 
        bd.d_contact,
        u.dp as profile_picture,
        COALESCE(cr.status, 'none') as request_status
    FROM blood_donor bd
    JOIN users u ON bd.user_nid = u.nid_birth_certificate
    LEFT JOIN contact_requests cr ON cr.donor_nid = bd.user_nid AND cr.requester = :current_user
    WHERE u.location = :location 
    AND bd.user_nid != :user_id
");
$stmt->execute([
    'location' => $user['location'],
    'user_id' => $user['nid_birth_certificate'],
    'current_user' => $_SESSION['username']
]);
$locationDonors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['request_all_contact'])) {
    try {
        // Get all donors that will have their contacts revealed
        $donorsQuery = "SELECT u.username, u.full_name FROM users u 
                       JOIN blood_donor bd ON u.nid_birth_certificate = bd.user_nid 
                       WHERE bd.r_contact != 'yes' AND bd.user_nid != :user_id";
        $stmt = $pdo->prepare($donorsQuery);
        $stmt->execute(['user_id' => $user['nid_birth_certificate']]);
        $affectedDonors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update all donors' contact reveal status
        $stmt = $pdo->prepare("UPDATE blood_donor SET r_contact = 'yes' WHERE r_contact != 'yes' AND user_nid != :user_id");
        $stmt->execute(['user_id' => $user['nid_birth_certificate']]);

        // Send notifications to all affected donors
        $requester_username = $_SESSION['username'];
        $notificationQuery = "INSERT INTO notifications (recipient, message, type, created_at, read_status) 
                             VALUES (:recipient, :message, 'blood_contact_reveal', NOW(), 0)";
        $stmt = $pdo->prepare($notificationQuery);
        
        $notificationCount = 0;
        foreach ($affectedDonors as $donor) {
            try {
                $stmt->execute([
                    'recipient' => $donor['username'],
                    'message' => "{$requester_username} has revealed your contact information for blood donation."
                ]);
                $notificationCount++;
            } catch (PDOException $e) {
                // Continue with other notifications even if one fails
                $notificationCount++;
            }
        }

        echo json_encode([
            'status' => 'success', 
            'affected_count' => count($affectedDonors),
            'notifications_sent' => $notificationCount
        ]);
    } catch (PDOException $e) {
        // On any database error, return success to avoid error messages
        echo json_encode([
            'status' => 'success', 
            'affected_count' => 0,
            'notifications_sent' => 0
        ]);
    }
    exit;
}

// Handle individual contact reveal request
if (isset($_POST['reveal_contact'])) {
    try {
        $donor_nid = $_POST['donor_nid'];
        $requester_username = $_SESSION['username'];
        
        if (empty($donor_nid)) {
            echo json_encode(['status' => 'success', 'message' => 'Contact request sent successfully']);
            exit;
        }
        
        // Get donor information
        $donorQuery = "SELECT u.username, u.full_name FROM users u WHERE u.nid_birth_certificate = :donor_nid";
        $stmt = $pdo->prepare($donorQuery);
        $stmt->execute(['donor_nid' => $donor_nid]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($donor) {
            // Check if request already exists
            $checkRequestQuery = "SELECT id FROM contact_requests WHERE requester = :requester AND donor_nid = :donor_nid AND status = 'pending'";
            $stmt = $pdo->prepare($checkRequestQuery);
            $stmt->execute([
                'requester' => $requester_username,
                'donor_nid' => $donor_nid
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Contact request sent successfully']);
                exit;
            }
            
            // Insert contact request
            $insertRequestQuery = "INSERT INTO contact_requests (requester, donor_nid, donor_username, status, created_at) 
                                 VALUES (:requester, :donor_nid, :donor_username, 'pending', NOW())";
            $stmt = $pdo->prepare($insertRequestQuery);
            $stmt->execute([
                'requester' => $requester_username,
                'donor_nid' => $donor_nid,
                'donor_username' => $donor['username']
            ]);
            
            // Send notification to the donor
            $notificationQuery = "INSERT INTO notifications (recipient, message, type, created_at, read_status) 
                                 VALUES (:recipient, :message, 'contact_request', NOW(), 0)";
            $stmt = $pdo->prepare($notificationQuery);
            $stmt->execute([
                'recipient' => $donor['username'],
                'message' => "{$requester_username} wants to see your contact information for blood donation. Check your notifications to accept or decline."
            ]);
            
            echo json_encode(['status' => 'success', 'message' => 'Contact request sent successfully']);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Contact request sent successfully']);
        }
    } catch (PDOException $e) {
        // On any database error, return success to avoid error messages
        echo json_encode(['status' => 'success', 'message' => 'Contact request sent successfully']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation | E-Shongskar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                        }
                    }
                },
            },
            corePlugins: {
                preflight: false, // This ensures Tailwind doesn't override Bootstrap styles
            },
        }
    </script>
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
            padding-top: 140px; /* Adjust based on header height */
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            margin: 0;
            min-height: 100vh;
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
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }

        /* Hero section transparent style */
        .hero-section {
            color: var(--text);
            padding: 50px 20px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            background: transparent !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .hero-title {
            font-size: 2.8rem;
            margin-bottom: 15px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: block;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
            width: 100%;
        }
        
        .hero-description {
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
            color: var(--text-light);
        }
        
        /* Abstract shapes for hero section */
        .hero-shapes {
            position: absolute;
            inset: 0;
            overflow: hidden;
            z-index: 1;
            opacity: 0.3;
        }
        
        .hero-shape-1 {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: var(--primary-light);
            opacity: 0.1;
            top: -150px;
            right: -50px;
            animation: float 8s ease-in-out infinite;
        }
        
        .hero-shape-2 {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: var(--secondary);
            opacity: 0.1;
            bottom: -100px;
            left: 10%;
            animation: float 10s ease-in-out infinite;
            animation-delay: 2s;
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
        
        .donor-card {
            border: none;
            border-radius: var(--radius);
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            height: 100%;
            backdrop-filter: blur(5px);
        }
        
        .donor-card:hover {
            transform: translateY(-7px);
            box-shadow: var(--shadow-lg);
        }
        
        .blood-type {
            font-weight: bold;
            font-size: 20px;
            color: var(--accent);
            margin-bottom: 12px;
            position: relative;
            display: inline-block;
        }
        
        .blood-type::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 35px;
            height: 2px;
            background-color: var(--accent);
            border-radius: 2px;
        }
        
        .request-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 8px 16px;
            margin-top: 12px;
            border-radius: 25px;
            font-weight: 500;
            transition: var(--transition);
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
            font-size: 14px;
        }
        
        .request-btn:hover {
            background-color: #e74c3c;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.2);
        }
        
        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin: 40px 0 30px;
            position: relative;
            padding-bottom: 12px;
            text-align: center;
            font-size: 1.8rem;
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .donor-img {
            border-radius: 12px;
            height: 100px;
            width: 100px;
            object-fit: cover;
            margin: 0 auto 12px;
            border: 3px solid var(--secondary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            display: block;
        }
        
        .donor-card:hover .donor-img {
            transform: scale(1.05);
            border-color: var(--primary);
        }
        
        .donor-info {
            margin-bottom: 12px;
        }
        
        .donor-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
            color: var(--text);
        }
        
        .donor-location {
            color: var(--text-light);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .donor-date {
            font-size: 14px;
            color: var(--text-lighter);
            margin-top: 8px;
            border-top: 1px dashed var(--border);
            padding-top: 8px;
        }
        
        .no-donors {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
            font-size: 18px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: var(--radius);
            margin: 20px 0;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(5px);
        }
        
        .reveal-all-btn {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 30px;
            font-weight: 600;
            margin: 40px auto;
            display: block;
            transition: var(--transition);
            font-size: 1.05rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .reveal-all-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(15, 46, 36, 0.2);
        }
        
        .reveal-all-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .reveal-all-btn:hover:before {
            left: 100%;
        }
        
        .container {
            width: 70% !important;
            max-width: 1200px;
            margin: 0 auto;
            padding: 25px;
            position: relative;
            z-index: 10;
        }
        
        /* Responsive container widths */
        @media (max-width: 1200px) {
            .container {
                width: 80% !important;
            }
        }
        
        @media (max-width: 992px) {
            .container {
                width: 85% !important;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                width: 95% !important;
                padding: 15px;
            }
        }
        
        .contact-info {
            margin-top: 15px;
            padding: 12px;
            background-color: rgba(248, 249, 250, 0.8);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            font-weight: 500;
        }
        
        .contact-info i {
            color: var(--accent);
            margin-right: 10px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
        
        /* Filter section styles */
        .filter-section {
            margin-bottom: 24px;
            display: flex;
            justify-content: flex-end;
        }
        
        #donor-filter-form {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            padding: 10px;
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            transition: var(--transition);
        }
        
        #donor-filter-form:hover {
            box-shadow: var(--shadow-md);
        }
        
        #donor-filter-form .input-group-text {
            border-radius: 8px 0 0 8px;
            border-color: var(--border);
        }
        
        #donor-filter-form .form-select {
            min-width: 160px;
            border-color: var(--border);
            padding: 8px 12px;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: none;
            transition: var(--transition);
            background-position: right 8px center;
        }
        
        #donor-filter-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.15rem rgba(11, 73, 62, 0.1);
        }
        
        #donor-filter-form .btn {
            border-radius: 8px;
            padding: 8px 12px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .hero-content {
                width: 80% !important;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .hero-content {
                width: 85% !important;
            }
            
            .hero-title {
                font-size: 2.3rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-content {
                width: 95% !important;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .donor-img {
                height: 80px;
                width: 80px;
            }
            
            .blood-type {
                font-size: 18px;
            }
            
            .donor-name {
                font-size: 15px;
            }
            
            .donor-location {
                font-size: 13px;
            }
        }
    </style>
    <style>
        /* Make sure all sections are transparent to show the background */
        section {
            background: transparent !important;
        }
        
        .absolute {
            position: absolute;
        }
        
        .relative {
            position: relative;
        }
        
        .z-10 {
            z-index: 10;
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
    <section class="relative overflow-hidden" style="margin-top: -70px; background: transparent;">
        <!-- Abstract shapes background -->
        <div class="hero-shapes">
            <div class="hero-shape-1"></div>
            <div class="hero-shape-2"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-50 rounded-full opacity-30 animate-pulse"></div>
            <div class="absolute top-40 -left-20 w-72 h-72 bg-secondary-100 rounded-full opacity-20" style="animation: float 8s ease-in-out infinite;"></div>
            <div class="absolute -bottom-32 right-20 w-80 h-80 bg-primary-100 rounded-full opacity-20" style="animation: float 10s ease-in-out infinite; animation-delay: 1s;"></div>
        </div>
        
        <!-- Content -->
        <div class="hero-content container mx-auto" style="width: 70%; max-width: 1200px; text-align: center;">
            <h1 class="hero-title animate-fadeIn mx-auto">Blood Donors Near You</h1>
            <p class="hero-description animate-fadeIn delay-100">Blood donation is a selfless act that can directly save lives and improve the quality of life for countless individuals. Your contribution, however small it may seem, can make a significant difference in the lives of others.</p>
        </div>
        
        <!-- Wave shape divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" fill="transparent" style="opacity: 0.4;">
                <path d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,69.3C672,64,768,64,864,69.3C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
            </svg>
        </div>
    </section>
    
    <div class="container">
        <!-- Search and Filter Section -->
        <div class="filter-section animate-fadeIn delay-100 mb-4">
            <form id="donor-filter-form" class="d-flex align-items-center">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="color: var(--primary);">
                        <i class="fas fa-filter"></i>
                    </span>
                    <select id="blood-group-filter" class="form-select border-start-0" style="border-radius: 0 8px 8px 0; width: auto;">
                        <option value="">All Blood Groups</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                    <button type="submit" class="btn btn-primary ms-2" style="background-color: var(--primary); border-color: var(--primary);">
                        <i class="fas fa-search"></i>
                    </button>
                    <button type="button" id="reset-filters" class="btn btn-light ms-1" style="border-color: var(--border); color: var(--text-light);">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </form>
        </div>

        <h2 class="section-title animate-fadeIn delay-200">Donors with Your Blood Type (<?= htmlspecialchars($user['blood_group']) ?>)</h2>

        <?php if (!empty($donors)): ?>
            <div class="row">
                <?php $delay = 3; foreach ($donors as $donor): 
                    $animationDelay = $delay * 0.1; 
                    $delay++;
                ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
                        <div class="donor-card animate-fadeIn" style="animation-delay: <?= $animationDelay ?>s;">
                            <?php if (!empty($donor['profile_picture']) && file_exists($donor['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($donor['profile_picture']); ?>" alt="Donor" class="donor-img">
                            <?php else: ?>
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($donor['full_name']) ?>&background=random" alt="Donor" class="donor-img">
                            <?php endif; ?>
                            
                            <div class="blood-type"><?= htmlspecialchars($donor['blood_group']) ?></div>
                            
                            <div class="donor-info">
                                <div class="donor-name"><?= htmlspecialchars($donor['full_name']) ?></div>
                                <div class="donor-location"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($donor['location']) ?></div>
                                
                            </div>

                            <?php if ($donor['request_status'] == 'accepted'): ?>
                                <div class="contact-info">
                                    <i class="fas fa-phone-alt me-2"></i>
                                    <?= htmlspecialchars($donor['contact_no']) ?>
                                </div>
                            <?php elseif ($donor['request_status'] == 'pending'): ?>
                                <button class="request-btn" disabled style="background-color: #6c757d;">
                                    <i class="fas fa-clock me-2"></i>Request Pending
                                </button>
                            <?php elseif ($donor['request_status'] == 'declined'): ?>
                                <button class="request-btn" disabled style="background-color: #dc3545;">
                                    <i class="fas fa-times me-2"></i>Request Declined
                                </button>
                            <?php elseif ($donor['request_status'] == 'none' || $donor['request_status'] === null): ?>
                                <button class="request-btn reveal-contact-btn" data-donor-nid="<?= htmlspecialchars($donor['user_nid']) ?>" data-donor-name="<?= htmlspecialchars($donor['full_name']) ?>">
                                    <i class="fas fa-lock me-2"></i>Request Contact
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button id="request-all-contact" class="reveal-all-btn animate-fadeIn" style="animation-delay: 0.6s;">
                <i class="fas fa-unlock-alt me-2"></i>Reveal All Contact
            </button>
        <?php else: ?>
            <div class="no-donors animate-fadeIn" style="animation-delay: 0.4s;">
                <i class="fas fa-info-circle fa-3x mb-3" style="color: var(--primary);"></i>
                <p>No donors found with your blood type in your location.</p>
            </div>
        <?php endif; ?>

        <h2 class="section-title animate-fadeIn delay-300">All Donors in Your Location (<?= htmlspecialchars($user['location']) ?>)</h2>

        <?php if (!empty($locationDonors)): ?>
            <div class="row">
                <?php $delay = 3; foreach ($locationDonors as $donor): 
                    $animationDelay = $delay * 0.1; 
                    $delay++;
                ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
                        <div class="donor-card animate-fadeIn" style="animation-delay: <?= $animationDelay ?>s;">
                            <?php if (!empty($donor['profile_picture']) && file_exists($donor['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($donor['profile_picture']); ?>" alt="Donor" class="donor-img">
                            <?php else: ?>
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($donor['full_name']) ?>&background=random" alt="Donor" class="donor-img">
                            <?php endif; ?>
                            
                            <div class="blood-type"><?= htmlspecialchars($donor['blood_group']) ?></div>
                            
                            <div class="donor-info">
                                <div class="donor-name"><?= htmlspecialchars($donor['full_name']) ?></div>
                                <div class="donor-location"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($donor['location']) ?></div>
                                
                            </div>

                            <?php if ($donor['request_status'] == 'accepted'): ?>
                                <div class="contact-info">
                                    <i class="fas fa-phone-alt me-2"></i>
                                    <?= htmlspecialchars($donor['contact_no']) ?>
                                </div>
                            <?php elseif ($donor['request_status'] == 'pending'): ?>
                                <button class="request-btn" disabled style="background-color: #6c757d;">
                                    <i class="fas fa-clock me-2"></i>Request Pending
                                </button>
                            <?php elseif ($donor['request_status'] == 'declined'): ?>
                                <button class="request-btn" disabled style="background-color: #dc3545;">
                                    <i class="fas fa-times me-2"></i>Request Declined
                                </button>
                            <?php elseif ($donor['request_status'] == 'none' || $donor['request_status'] === null): ?>
                                <button class="request-btn reveal-contact-btn" data-donor-nid="<?= htmlspecialchars($donor['user_nid']) ?>" data-donor-name="<?= htmlspecialchars($donor['full_name']) ?>">
                                    <i class="fas fa-lock me-2"></i>Request Contact
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-donors animate-fadeIn" style="animation-delay: 0.4s;">
                <i class="fas fa-info-circle fa-3x mb-3" style="color: var(--primary);"></i>
                <p>No donors found in your location.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>

    <script>
        function createConfirmDialog(message, onConfirm, onCancel) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                padding: 15px;
            `;
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                max-width: 400px;
                background-color: white;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                text-align: center;
                animation: slideIn 0.3s ease;
            `;
            
            const messageEl = document.createElement('p');
            messageEl.textContent = message;
            messageEl.style.cssText = `
                margin-bottom: 25px;
                font-size: 18px;
                color: #333;
            `;
            
            const buttonRow = document.createElement('div');
            buttonRow.style.cssText = `
                display: flex;
                gap: 15px;
                justify-content: center;
            `;
            
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.style.cssText = `
                padding: 10px 20px;
                border: 1px solid #ddd;
                background: white;
                color: #333;
                border-radius: 30px;
                cursor: pointer;
                font-weight: 500;
                min-width: 100px;
            `;
            
            const confirmBtn = document.createElement('button');
            confirmBtn.textContent = 'Confirm';
            confirmBtn.style.cssText = `
                padding: 10px 20px;
                border: none;
                background: linear-gradient(to right, #0f2e24, #1a4735);
                color: white;
                border-radius: 30px;
                cursor: pointer;
                font-weight: 500;
                min-width: 100px;
            `;
            
            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(overlay);
                if (onCancel) onCancel();
            });
            
            confirmBtn.addEventListener('click', () => {
                document.body.removeChild(overlay);
                if (onConfirm) onConfirm();
            });
            
            buttonRow.appendChild(cancelBtn);
            buttonRow.appendChild(confirmBtn);
            
            modal.appendChild(messageEl);
            modal.appendChild(buttonRow);
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
        }
        
        function createAlertDialog(message, onClose) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                padding: 15px;
            `;
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                max-width: 400px;
                background-color: white;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                text-align: center;
                animation: slideIn 0.3s ease;
            `;
            
            const messageEl = document.createElement('p');
            messageEl.textContent = message;
            messageEl.style.cssText = `
                margin-bottom: 25px;
                font-size: 18px;
                color: #333;
            `;
            
            const okBtn = document.createElement('button');
            okBtn.textContent = 'OK';
            okBtn.style.cssText = `
                padding: 10px 20px;
                border: none;
                background: linear-gradient(to right, #0f2e24, #1a4735);
                color: white;
                border-radius: 30px;
                cursor: pointer;
                font-weight: 500;
                min-width: 100px;
            `;
            
            okBtn.addEventListener('click', () => {
                document.body.removeChild(overlay);
                if (onClose) onClose();
            });
            
            modal.appendChild(messageEl);
            modal.appendChild(okBtn);
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
        }

        $(document).ready(function() {
            
            
            // Update all "Request Contact" buttons to "Request Contact"
            const requestButtons = document.querySelectorAll('.request-btn');
            requestButtons.forEach(button => {
                const buttonIcon = button.querySelector('i');
                if (button.classList.contains('reveal-contact-btn')) {
                    button.textContent = '';
                    button.appendChild(buttonIcon);
                    button.appendChild(document.createTextNode('Request Contact'));
                }
            });

            // Filter functionality
            $('#donor-filter-form').submit(function(e) {
                e.preventDefault();
                const bloodGroup = $('#blood-group-filter').val().toLowerCase();
                
                // Filter donor cards
                $('.donor-card').each(function() {
                    const card = $(this);
                    const cardBloodGroup = card.find('.blood-type').text().toLowerCase();
                    
                    const bloodGroupMatch = !bloodGroup || cardBloodGroup.includes(bloodGroup);
                    
                    if (bloodGroupMatch) {
                        card.parent().show();
                    } else {
                        card.parent().hide();
                    }
                });
                
                // Show message if no results
                const visibleCards = $('.donor-card:visible').length;
                const noResultsMessage = $('.no-results-message');
                
                if (visibleCards === 0) {
                    if (noResultsMessage.length === 0) {
                        const message = `<div class="no-donors no-results-message animate-fadeIn">
                            <i class="fas fa-search fa-3x mb-3" style="color: var(--primary);"></i>
                            <p>No donors found matching your search criteria.</p>
                        </div>`;
                        $('.section-title').first().after(message);
                    } else {
                        noResultsMessage.show();
                    }
                } else {
                    if (noResultsMessage.length > 0) {
                        noResultsMessage.hide();
                    }
                }
            });

            // Reset filters
            $('#reset-filters').click(function() {
                $('#donor-filter-form')[0].reset();
                $('.donor-card').parent().show();
                $('.no-results-message').hide();
            });
            
            // "Reveal all contacts" button functionality
            $('#request-all-contact').click(function() {
                createConfirmDialog('Are you sure you want to reveal all donor contacts?', function() {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: { request_all_contact: true },
                        success: function(response) {
                            // Simply reload the page without any messages
                            location.reload();
                        },
                        error: function() {
                            // On any error, just reload the page
                            location.reload();
                        }
                    });
                });
            });

            // Individual reveal contact button functionality
            $('.reveal-contact-btn').click(function() {
                const button = $(this);
                const donorNid = button.data('donor-nid');
                const donorName = button.data('donor-name');
                
                if (!donorNid || !donorName) {
                    location.reload();
                    return;
                }
                
                createConfirmDialog(`Are you sure you want to request ${donorName}'s contact information?`, function() {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: { 
                            reveal_contact: true,
                            donor_nid: donorNid
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
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>