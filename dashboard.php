<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #c3cfe2 0%,  #f5f7fa 100%);
            color: #333;
        }

        .navbar {
            background-color: #007bff;
        }

        .navbar-brand {
            font-weight: bold;
            color: #fff;
        }

        .navbar-brand:hover {
            color: #ddd;
        }

        .navbar-nav .nav-link {
            color: #fff;
        }

        .navbar-nav .nav-link:hover {
            color: #ddd;
        }

        .hero-section {
            background: linear-gradient(to right, #007bff, #6c757d);
            color: white;
            padding: 50px 15px;
            text-align: center;
            height: 180px;
            margin-top: 70px; /* Account for fixed header */
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .hero-section p {
            font-size: 1.2rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
        }

        .notification-banner {
            display: none;
            position: fixed;
            top: 0;
            width: 100%;
            background: #f39c12;
            color: #fff;
            text-align: center;
            padding: 10px;
            z-index: 1000;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <!-- <?php
    include 'database.php';
    session_start();

    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $eventStmt = $pdo->prepare("
        SELECT events.*, users.full_name 
        FROM events 
        JOIN users ON events.username = users.username 
        WHERE events.location = :location 
        AND events.username != :username
        AND events.created_at >= NOW() - INTERVAL 1 DAY
    ");
    $eventStmt->execute([
        'location' => $user['location'],
        'username' => $user['username'],
    ]);
    $matchingEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);


    // Count users online in the logged-in user's location
    $userCountStmt = $pdo->prepare("
    SELECT COUNT(*) AS online_count
    FROM local_users_online
    WHERE location = :location AND username != :username
    ");
    $userCountStmt->execute([
        'location' => $user['location'],
        'username' => $_SESSION['username'],
    ]);
    $userCount = $userCountStmt->fetch(PDO::FETCH_ASSOC)['online_count'];
    ?> -->

    <!-- Hero Section -->
    <div class="hero-section" >
        <div class="container" >
            <!-- <h1><?php echo htmlspecialchars($user['username']); ?></h1> -->
            <h3 style="padding-top: 20px;">Explore your dashboard and take actions today!</h3>
        </div>
    </div>

    <!-- Notification Banner -->
    <?php if (!empty($matchingEvents)): ?>
        <div class="notification-banner" id="notificationBanner"></div>
        <script>
            const notifications = <?php echo json_encode(array_map(function ($event) {

                                        return "An event is created by {$event['full_name']} at {$event['location']} on " . date('Y-m-d', strtotime($event['created_at'])) . ".";
                                    }, $matchingEvents)); ?>;

            let currentIndex = 0;
            const banner = document.getElementById('notificationBanner');

            function showNotification() {
                if (currentIndex < notifications.length) {
                    banner.textContent = notifications[currentIndex];
                    banner.style.display = 'block';
                    currentIndex++;
                    setTimeout(hideNotification, 1000);
                }
            }

            function hideNotification() {
                banner.style.display = 'none';
                setTimeout(showNotification, 1000);
            }

            showNotification();
        </script>
    <?php endif; ?>

    <!-- Options Section -->
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-md-4">
                <a href="report_issue.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3" style="color: #0b493e;"></i>
                            <h5 class="card-title">Report Issues</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="alert.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                        <i class="fas fa-bell fa-2x mb-3" style="color: #339c6a;"></i>

                            <h5 class="card-title">Alert</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="event.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-2x text-primary mb-3"></i>
                            <h5 class="card-title">Event</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="Emer_contact.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                        <i class="fas fa-phone fa-2x mb-3" style="color: green;"></i>
                            <h5 class="card-title">Emergency Contact</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="blood_donate.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="fas fa-tint fa-2x text-danger mb-3"></i>
                            <h5 class="card-title">Blood Donor Near You</h5>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="response_events.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                        <i class="fas fa-handshake fa-2x mb-3" style="color: #1E3A8A;"></i>
                            <h5 class="card-title">Response In Event</h5>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>