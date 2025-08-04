<?php
include 'database.php';

//session_start();

// Get logged-in user's profile picture and notifications
$profilePicQuery = "SELECT username, dp as profile_picture FROM users WHERE username = :username";
$profileStmt = $pdo->prepare($profilePicQuery);
$profileStmt->execute(['username' => $_SESSION['username']]);
$userData = $profileStmt->fetch(PDO::FETCH_ASSOC);

// Get unread notifications count
$notificationCount = 0;
try {
    // Get user location first
    $userLocationQuery = "SELECT location FROM users WHERE username = :username";
    $userStmt = $pdo->prepare($userLocationQuery);
    $userStmt->execute(['username' => $_SESSION['username']]);
    $userLocationData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userLocationData) {
        $userLocation = $userLocationData['location'];
        
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
        $stmt->execute(['username' => $_SESSION['username']]);
        $contactCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Also check for unread notifications in the notifications table
        $notifyQuery = "SELECT COUNT(*) as count FROM notifications WHERE recipient = :username AND read_status = 0";
        $stmt = $pdo->prepare($notifyQuery);
        $stmt->execute(['username' => $_SESSION['username']]);
        $notifyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Sum all notification sources
        $notificationCount = $reportCount + $eventCount + $alertCount + $contactCount + $notifyCount;
    }
} catch (PDOException $e) {
    // If there's an error, keep count at 0
    $notificationCount = 0;
}

// Get unread messages count
$unreadMessageCount = 0;
try {
    $messageCountQuery = "SELECT COUNT(*) as count FROM chat 
                         WHERE to_username = :username AND (read_status = 0 OR read_status IS NULL)";
    $messageStmt = $pdo->prepare($messageCountQuery);
    $messageStmt->execute(['username' => $_SESSION['username']]);
    $messageData = $messageStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($messageData) {
        $unreadMessageCount = $messageData['count'];
    }
} catch (PDOException $e) {
    // If the table doesn't exist or other error, keep count at 0
    $unreadMessageCount = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <title>Header Component</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(180deg, #f6f9fc 0%, #e7ebf0 100%);
            color: #1a1a1a;
        }

        /* Header Container */
        .header-container {
            width: 100%;
            display: flex;
            justify-content: center;
            background-color: #0f2e24;
            position: fixed;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Header Styles */
        header {
            width: 70%;
            color: white;
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logoName {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .logoPic {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 6px;
            transition: transform 0.3s;
        }
        
        .logoPic:hover {
            transform: scale(1.1);
        }

        nav {
            display: flex;
            align-items: center;
        }

        nav a {
            margin: 0 0.8rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            white-space: nowrap;
            position: relative;
            padding: 0.5rem 0.3rem;
            font-size: 0.95rem;
        }
        
        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #4CAF50;
            transition: width 0.3s;
        }
        
        nav a:hover {
            color: #4CAF50;
        }
        
        nav a:hover::after {
            width: 100%;
        }
        
        /* Active nav link */
        nav a.active {
            color: #4CAF50;
            font-weight: 600;
        }
        
        nav a.active::after {
            width: 100%;
            height: 3px;
            background-color: #4CAF50;
        }

        .headerIcons {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .icon-btn {
            background-color: transparent;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            transition: all 0.3s;
            position: relative;
        }

        .icon-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .icon-btn.active {
            background-color: rgba(76, 175, 80, 0.2);
        }

        .icons {
            height: 28px;
            width: 28px;
            object-fit: contain;
            color: white;
        }
        
        /* Profile dropdown styling */
        .profile-container {
            position: relative;
        }
        
        .profile-pic {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .profile-pic:hover {
            border-color: #1a4735;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.3);
        }
        
        .profile-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            background: rgba(15, 46, 36, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            width: 200px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1001;
            overflow: hidden;
        }
        
        .profile-container:hover .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #1a4735;
        }
        
        .dropdown-item i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 5px 0;
        }
        
        /* Badge for notification indicator */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid #0f2e24;
            animation: pulse 2s infinite;
        }
        
        /* Badge for message indicator */
        .message-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #2196F3;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid #0f2e24;
            animation: pulse-blue 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
            }
            70% {
                box-shadow: 0 0 0 5px rgba(255, 71, 87, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0);
            }
        }
        
        @keyframes pulse-blue {
            0% {
                box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7);
            }
            70% {
                box-shadow: 0 0 0 5px rgba(33, 150, 243, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(33, 150, 243, 0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1280px) {
            header {
                width: 80%;
            }
            
            nav a {
                margin: 0 0.6rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 1024px) {
            header {
                width: 85%;
            }
            
            .headerIcons {
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            header {
                width: 90%;
                flex-direction: column;
                gap: 1rem;
                padding: 0.8rem 0;
            }

            nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.3rem;
            }

            nav a {
                margin: 0 0.4rem;
                font-size: 0.85rem;
                padding: 0.3rem 0.2rem;
            }
            
            .headerIcons {
                gap: 12px;
            }
            
            .icon-btn {
                width: 40px;
                height: 40px;
            }
            
            .icons {
                height: 24px;
                width: 24px;
            }
            
            .profile-pic {
                width: 40px;
                height: 40px;
            }
            
            .profile-dropdown {
                right: -70px;
            }
        }

        @media (max-width: 480px) {
            header {
                width: 95%;
            }
            
            nav a {
                font-size: 0.8rem;
                margin: 0 0.2rem;
                padding: 0.3rem 0.1rem;
            }
            
            .headerIcons {
                gap: 8px;
            }
            
            .icon-btn {
                width: 36px;
                height: 36px;
            }
            
            .profile-pic {
                width: 36px;
                height: 36px;
            }
        }

        .saved-modal-content {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px) saturate(180%);
            border-radius: 22px;
            max-width: 420px;
            width: 92vw;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(60,72,100,0.18);
            padding: 38px 28px 28px 28px;
            position: relative;
            animation: fadeInModal 0.4s cubic-bezier(.4,2,.6,1) both;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(40px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .saved-modal-close {
            position: absolute;
            top: 18px;
            right: 18px;
            background: none;
            border: none;
            font-size: 2.2rem;
            color: #0f2e24;
            cursor: pointer;
            transition: color 0.2s;
            z-index: 2;
        }
        .saved-modal-close:hover {
            color: #4CAF50;
        }
        .saved-modal-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f2e24;
            margin-bottom: 12px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .saved-modal-count {
            text-align: center;
            color: #333;
            margin-bottom: 18px;
            font-size: 1.08em;
        }
        .saved-modal-empty {
            text-align: center;
            color: #888;
            font-size: 1.08em;
            margin-top: 18px;
        }
        .saved-modal-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .saved-modal-list li {
            text-align: center;
        }
        .saved-report-link {
            color: #2980b9;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.18em;
            background: rgba(76,175,80,0.08);
            border-radius: 12px;
            padding: 10px 0;
            display: inline-block;
            width: 100%;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(76,175,80,0.04);
        }
        .saved-report-link:hover {
            background: #4CAF50;
            color: #fff;
            box-shadow: 0 4px 16px rgba(76,175,80,0.10);
            text-decoration: none;
        }
        .saved-report-number {
            font-size: 1.15em;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <header>
            <div class="logoName" onclick="window.location.reload()" style="cursor:pointer;" title="Refresh Page">
                <img class="logoPic" src="EShongskarIcon.png" alt="E-Shongskar Logo" />
                <span style="font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight:bold;">E-Shongskar</span>
            </div>
            <nav>
                <?php
                // Get the current page filename
                $currentPage = basename($_SERVER['PHP_SELF']);
                
                // Define navigation items
                $navItems = [
                    'landingPageAfterLogin.php' => 'Home',
                    'Notification_report_issue.php' => 'Reports',
                    'response_events.php' => 'Events',
                    'blood_donate.php' => 'Blood',
                    'alert.php' => 'Alert',
                    'sos.php' => 'SOS',
                    'about_us.php' => 'About us',
                    '#contact' => 'Contact us'
                ];
                
                // Output navigation links
                foreach ($navItems as $url => $label) {
                    $isActive = ($currentPage == $url) ? 'active' : '';
                    echo '<a href="' . $url . '" class="' . $isActive . '">' . $label . '</a>';
                }
                ?>
            </nav>
            <div class="headerIcons">
                <!-- Messenger Icon Button -->
                <a href="chat.php" class="icon-btn <?php echo $currentPage == 'chat.php' ? 'active' : ''; ?>" title="Messenger">
                    <i class="bi bi-chat-dots-fill icons"></i>
                    <?php
                    // Display message badge only if there are unread messages
                    if ($unreadMessageCount > 0) {
                        echo '<span class="message-badge">' . ($unreadMessageCount > 9 ? '9+' : $unreadMessageCount) . '</span>';
                    }
                    ?>
                </a>

                <!-- Notification Icon Button -->
                <a href="notify.php" class="icon-btn <?php echo $currentPage == 'notify.php' ? 'active' : ''; ?>" title="Notifications">
                    <i class="bi bi-bell-fill icons"></i>
                    <?php
                    // Display notification badge only if there are unread notifications
                    if ($notificationCount > 0) {
                        echo '<span class="notification-badge">' . ($notificationCount > 9 ? '9+' : $notificationCount) . '</span>';
                    }
                    ?>
                </a>

                <!-- Profile Picture with Dropdown -->
                <div class="profile-container">
                    <?php if (!empty($userData['profile_picture']) && file_exists($userData['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" alt="profile" class="profile-pic">
                    <?php else: ?>
                        <div class="profile-pic" style="display:flex;align-items:center;justify-content:center;background:#e5e7eb;color:#4CAF50;font-size:2rem;">
                            <i class="bi bi-person-circle"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Profile Dropdown Menu -->
                    <div class="profile-dropdown">
                        <a href="profile.php" class="dropdown-item">
                            <i class="bi bi-person-circle"></i> Profile
                        </a>
                        <a href="#" class="dropdown-item">
                            <i class="bi bi-people"></i> Connect
                        </a>
                        <a href="#" class="dropdown-item" id="representatorBtn">
                            <i class="bi bi-person-vcard"></i> Representator
                        </a>
                        <a href="#" class="dropdown-item">
                            <i class="bi bi-activity"></i> Activity
                        </a>
                        <a href="#" class="dropdown-item" id="savedBtn">
                            <i class="bi bi-bookmark"></i> Saved
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <a href="logout.php" class="dropdown-item">
                            <i class="bi bi-box-arrow-right"></i> Log Out
                        </a>
                    </div>
                </div>
            </div>
        </header>
    </div>
    
    <?php
    // Fetch representators for modal (if session exists)
    $representators = [];
    if (isset($_SESSION['username'])) {
        $stmt = $pdo->prepare("SELECT username, full_name, contact_no, email, verified FROM users WHERE location = (SELECT location FROM users WHERE username = :username) AND verified IS NOT NULL");
        $stmt->execute(['username' => $_SESSION['username']]);
        $representators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>
    <!-- Representator Modal -->
    <div id="representatorModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,46,36,0.85); z-index:2000; justify-content:center; align-items:center;">
        <div style="background:rgba(255,255,255,0.97); border-radius:18px; max-width:500px; width:90vw; max-height:80vh; overflow-y:auto; box-shadow:0 8px 32px rgba(60,72,100,0.18); padding:32px 24px; position:relative;">
            <button onclick="closeRepresentatorModal()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:2rem; color:#0f2e24; cursor:pointer;">&times;</button>
            <h2 style="font-size:1.4rem; font-weight:700; color:#0f2e24; margin-bottom:18px; text-align:center;">Area Representators</h2>
            <?php if (empty($representators)): ?>
                <p style="text-align:center; color:#888;">No representators found for your location.</p>
            <?php else: ?>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f4f6fb; color:#3730a3;">
                            <th style="padding:8px 4px; border-radius:8px 0 0 8px;">Username</th>
                            <th style="padding:8px 4px;">Name</th>
                            <th style="padding:8px 4px;">Contact</th>
                            <th style="padding:8px 4px;">Email</th>
                            <th style="padding:8px 4px; border-radius:0 8px 8px 0;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($representators as $rep): ?>
                            <tr style="background:#fff; border-bottom:1px solid #e5e7eb;">
                                <td style="padding:8px 4px; font-weight:600; color:#2980b9;">
                                    <a href="chat.php?to_username=<?= urlencode($rep['username']) ?>" class="rep-chat-link" style="color:#2980b9; text-decoration:underline; cursor:pointer; font-weight:700;">
                                        <?= htmlspecialchars($rep['username']) ?>
                                    </a>
                                </td>
                                <td style="padding:8px 4px;"><?= htmlspecialchars($rep['full_name']) ?></td>
                                <td style="padding:8px 4px;"><?= htmlspecialchars($rep['contact_no']) ?></td>
                                <td style="padding:8px 4px;"><?= htmlspecialchars($rep['email']) ?></td>
                                <td style="padding:8px 4px; color:#10b981; font-weight:600;"><?= htmlspecialchars($rep['verified']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <!-- Saved Reports Modal -->
    <?php
    $savedReports = [];
    $savedCount = 0;
    if (isset($_SESSION['username'])) {
        $stmt = $pdo->prepare("SELECT saves FROM saved WHERE username = :username");
        $stmt->execute(['username' => $_SESSION['username']]);
        $savedReports = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $savedCount = count($savedReports);
    }
    ?>
    <div id="savedModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,46,36,0.45); z-index:2100; justify-content:center; align-items:center;">
        <div class="saved-modal-content">
            <button onclick="closeSavedModal()" class="saved-modal-close">&times;</button>
            <h2 class="saved-modal-title">Your Saved Reports</h2>
            <p class="saved-modal-count">Your saved report no: <span class="font-bold"><?php echo $savedCount; ?></span></p>
            <?php if ($savedCount === 0): ?>
                <p class="saved-modal-empty">No saved reports found.</p>
            <?php else: ?>
                <ul class="saved-modal-list">
                    <?php foreach ($savedReports as $rid): ?>
                        <li>
                            <a href="Notification_report_issue.php?report_id=<?php echo urlencode($rid); ?>" class="saved-report-link">
                                <span class="saved-report-number">Report no: <?php echo htmlspecialchars($rid); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to add active class based on current URL
        function setActiveNav() {
            const currentPath = window.location.pathname;
            const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
            
            // Set active class for navigation links
            document.querySelectorAll('nav a').forEach(link => {
                const href = link.getAttribute('href');
                if (href === filename || 
                    (filename === '' && href === 'landingPageAfterLogin.php') || 
                    (href.includes(filename) && filename !== '')) {
                    link.classList.add('active');
                }
            });
            
            // Set active class for icon buttons
            document.querySelectorAll('.icon-btn').forEach(btn => {
                const href = btn.getAttribute('href');
                if (href && href.includes(filename) && filename !== '') {
                    btn.classList.add('active');
                }
            });
        }
        
        setActiveNav();
    });
    document.getElementById('representatorBtn').onclick = function(e) {
        e.preventDefault();
        document.getElementById('representatorModal').style.display = 'flex';
    };
    function closeRepresentatorModal() {
        document.getElementById('representatorModal').style.display = 'none';
    }
    window.onclick = function(event) {
        var modal = document.getElementById('representatorModal');
        if (event.target === modal) {
            closeRepresentatorModal();
        }
    };
    document.querySelectorAll('.rep-chat-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Allow normal navigation, but close modal for smoothness
            closeRepresentatorModal();
        });
    });
    document.getElementById('savedBtn').onclick = function(e) {
        e.preventDefault();
        document.getElementById('savedModal').style.display = 'flex';
    };
    function closeSavedModal() {
        document.getElementById('savedModal').style.display = 'none';
    }
    window.onclick = function(event) {
        var modal = document.getElementById('savedModal');
        if (event.target === modal) {
            closeSavedModal();
        }
    };
    document.querySelectorAll('.saved-report-link').forEach(function(link) {
        link.addEventListener('click', function() {
            closeSavedModal();
        });
    });
    </script>
</body>
</html>