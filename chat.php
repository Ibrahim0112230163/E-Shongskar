<?php
include('database.php');
session_start();

$logged_in_username = $_SESSION['username'] ?? null;
if (!$logged_in_username) {
    die("User not logged in.");
}

$user_query = "SELECT location, verified FROM users WHERE username = :username";
$stmt = $pdo->prepare($user_query);
$stmt->execute(['username' => $logged_in_username]);
$user_result = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user_result) {
    die("User not found.");
}
$logged_in_location = $user_result['location'];
$logged_in_verified = $user_result['verified'];

// sending messages with pic (save as file, not blob)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['to_username'], $_POST['message'])) {
    $to_username = $_POST['to_username'];
    $message = $_POST['message'];

    $pic_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        if (in_array($_FILES['attachment']['type'], $allowed_types) && $_FILES['attachment']['size'] <= $max_size) {
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('chatimg_') . '.' . $ext;
            $upload_dir = 'uploads/chat_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $pic_path = $upload_dir . $unique_name;
            move_uploaded_file($_FILES['attachment']['tmp_name'], $pic_path);
        }
    }

    $recipient_query = "SELECT username, verified, location FROM users WHERE username = :to_username AND location = :location";
    $stmt = $pdo->prepare($recipient_query);
    $stmt->execute(['to_username' => $to_username, 'location' => $logged_in_location]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        die("Recipient not found or location does not match.");
    }

    if ($logged_in_verified !== null || $recipient['verified'] !== null) {
        $chat_insert_query = "INSERT INTO chat (from_username, to_username, created_at, history, pic) 
                              VALUES (:from_username, :to_username, NOW(), :history, :pic)";
        $stmt = $pdo->prepare($chat_insert_query);
        $stmt->execute([
            'from_username' => $logged_in_username,
            'to_username' => $to_username,
            'history' => $message,
            'pic' => $pic_path
        ]);

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?to_username=" . urlencode($to_username));
        exit;
    } else {
        die("You cannot send messages to unverified users.");
    }
}

// conversation history with distinct users - modified to include last message
$conversations_query = "
    SELECT 
        other_user, 
        MAX(created_at) as last_message_time,
        (
            SELECT history 
            FROM chat 
            WHERE (from_username = :username AND to_username = other_user) 
               OR (from_username = other_user AND to_username = :username)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT from_username 
            FROM chat 
            WHERE (from_username = :username AND to_username = other_user) 
               OR (from_username = other_user AND to_username = :username)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_sender,
        (
            SELECT COUNT(*) 
            FROM chat 
            WHERE from_username = other_user 
            AND to_username = :username 
            AND (read_status = 0 OR read_status IS NULL)
        ) as unread_count
    FROM (
        SELECT 
            CASE 
                WHEN from_username = :username THEN to_username 
                ELSE from_username 
            END AS other_user, created_at
        FROM chat
        WHERE from_username = :username OR to_username = :username
    ) as sub
    GROUP BY other_user
    ORDER BY last_message_time DESC
";
$stmt = $pdo->prepare($conversations_query);
$stmt->execute(['username' => $logged_in_username]);
$conversation_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// chat history for a specific conversation
if (isset($_GET['to_username'])) {
    $to_username = $_GET['to_username'];

    $chat_history_query = "SELECT * FROM chat 
                           WHERE (from_username = :logged_in_username AND to_username = :to_username) 
                           OR (from_username = :to_username AND to_username = :logged_in_username) 
                           ORDER BY created_at ASC";
    $stmt = $pdo->prepare($chat_history_query);
    $stmt->execute(['logged_in_username' => $logged_in_username, 'to_username' => $to_username]);
    $chat_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read when conversation is opened
    $markAsReadQuery = "UPDATE chat SET read_status = 1 
                       WHERE from_username = :from_username AND to_username = :to_username 
                       AND (read_status = 0 OR read_status IS NULL)";
    $stmt = $pdo->prepare($markAsReadQuery);
    $stmt->execute([
        'from_username' => $to_username,
        'to_username' => $logged_in_username
    ]);
}

$location_users = [];
if ($logged_in_verified) {
    $location_query = "SELECT username FROM users WHERE location = :location AND username != :username ORDER BY username ASC";
    $stmt = $pdo->prepare($location_query);
    $stmt->execute(['location' => $logged_in_location, 'username' => $logged_in_username]);
    $location_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mark all messages as read when chat page is loaded
try {
    $markAllAsReadQuery = "UPDATE chat SET read_status = 1 
                          WHERE to_username = :username AND (read_status = 0 OR read_status IS NULL)";
    $stmt = $pdo->prepare($markAllAsReadQuery);
    $stmt->execute(['username' => $logged_in_username]);
} catch (PDOException $e) {
    // If there's an error, continue without marking as read
}
?>

<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Connect | Messaging</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            margin: 0;
            padding-top: 70px; /* Account for fixed navbar */
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow-x: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center the chat container */
            justify-content: flex-start;
        }
        /* Decorative Circles */
        .circle-bg {
            position: fixed;
            z-index: 0;
            pointer-events: none;
        }
        .circle1 { left: -120px; top: -120px; width: 320px; height: 320px; background: radial-gradient(circle, #a5b4fc 60%, #6366f1 100%); opacity: 0.18; border-radius: 50%; }
        .circle2 { right: -100px; top: 40vh; width: 220px; height: 220px; background: radial-gradient(circle, #818cf8 60%, #6366f1 100%); opacity: 0.13; border-radius: 50%; }
        .circle3 { left: 10vw; bottom: -120px; width: 180px; height: 180px; background: radial-gradient(circle, #c7d2fe 60%, #818cf8 100%); opacity: 0.12; border-radius: 50%; }
        .circle4 { right: 5vw; bottom: -80px; width: 120px; height: 120px; background: radial-gradient(circle, #6366f1 60%, #818cf8 100%); opacity: 0.10; border-radius: 50%; }
        
        /* Main container styles */
        .chat-container {
            display: flex;
            height: calc(100vh - 70px); /* Full height minus header */
            width: 70%; /* 70% of full screen width */
            margin: 0 auto;
            overflow: hidden;
            position: relative;
            z-index: 1;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        
        /* Sidebar styles */
        .chat-sidebar {
            width: 30%;
            min-width: 250px;
            max-width: 350px;
            background: rgba(255,255,255,0.95);
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #1a4735;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin-right: 15px;
        }
        
        .user-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 5px;
            color: #1a4735;
        }
        
        .user-info p {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0;
        }
        
        .search-container {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            font-size: 0.95rem;
        }
        
        .conversation-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .conversation-item {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .conversation-item:hover {
            background-color: rgba(243, 244, 246, 0.8);
        }
        
        .conversation-item.active {
            background-color: rgba(209, 250, 229, 0.4);
            border-left: 3px solid #1a4735;
        }
        
        .conversation-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #4b5563;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .conversation-info {
            flex: 1;
            overflow: hidden;
        }
        
        .conversation-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 3px;
            color: #2d3748;
            display: flex;
            justify-content: space-between;
        }
        
        .conversation-info p {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .unread-badge {
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
            }
            70% {
                box-shadow: 0 0 0 4px rgba(255, 71, 87, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0);
            }
        }
        
        /* Chat area styles */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            background: rgba(255,255,255,0.9);
            position: relative;
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
        }
        
        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
           
            background: #1a4735;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .chat-header-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 3px;
            color: #1a4735;
        }
        
        .chat-header-info p {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
        }
        
        .chat-messages-container {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        .chat-messages {
            height: 100%;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 70%;
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: 16px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message.sent {
            align-self: flex-end;
            background: #1a4735;
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        
        .message.received {
            align-self: flex-start;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            margin-top: 5px;
            opacity: 0.7;
            text-align: right;
        }
        
        .message-image {
            margin-top: 10px;
        }
        
        .message-image img {
            max-width: 100%;
            border-radius: 8px;
        }
        
        /* Fixed input area */
        .chat-input-container {
            padding: 15px;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10;
        }
        
        .chat-input-container textarea {
            flex: 1;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            resize: none;
            font-size: 0.95rem;
            min-height: 45px;
            max-height: 120px;
            background: #f8fafc;
        }
        
        .file-input {
            display: none;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: #1a4735;
            cursor: pointer;
            background: #f4f6fb;
            transition: all 0.2s;
        }
        
        .file-input-label.selected {
            background: #d1fae5;
            color: #059669;
        }
        
        .send-button {
            background: #1a4735;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .send-button:hover {
            background: #0f372a;
        }
        
        .image-preview {
            display: flex;
            align-items: center;
        }
        
        .image-preview img {
            max-width: 50px;
            max-height: 50px;
            border-radius: 6px;
            margin-right: 8px;
        }
        
        .image-preview .remove-preview {
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #c7d2fe;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2d3748;
        }
        
        .empty-state p {
            font-size: 1rem;
            color: #6b7280;
            max-width: 400px;
            margin-bottom: 20px;
        }
        
        .empty-state button {
            background: #1a4735;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Scroll indicators */
        .scroll-indicator {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-size: 0.85rem;
            color: #4b5563;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .scroll-indicator.visible {
            opacity: 1;
        }
        
        .scroll-to-bottom {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: #1a4735;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
            z-index: 5;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }
        
        .scroll-to-bottom.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .scroll-to-bottom:hover {
            background: #0b493e;
        }
        
        /* User list item styling */
        .user-list-item {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            background: #f9fafb;
            border: 1px solid #f3f4f6;
        }
        
        .user-list-item:hover {
            background-color: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        /* Responsive design */
        @media (max-width: 1200px) {
            .chat-container {
                width: 85%;
            }
        }
        
        @media (max-width: 992px) {
            .chat-container {
                width: 95%;
            }
        }
        
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                width: 95%;
                margin: 10px auto;
            }
            
            .chat-sidebar {
                width: 100%;
                height: 40vh;
                flex-shrink: 0;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .chat-area {
                height: 60vh;
            }
        }
        
        @media (max-width: 480px) {
            .chat-container {
                width: 100%;
                border-radius: 0;
                margin: 0;
                height: calc(100vh - 70px);
            }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<!-- Decorative background elements -->
<div class="circle-bg circle1"></div>
<div class="circle-bg circle2"></div>
<div class="circle-bg circle3"></div>
<div class="circle-bg circle4"></div>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <h2>Messages</h2>
            <?php if ($logged_in_verified): ?>
                <button onclick="openLocationModal()" style="background: none; border: none; color: #1a4735; cursor: pointer; font-size: 1.1rem; font-weight: 600;">
                    <i class="fas fa-users"></i> Local Users
                </button>
            <?php endif; ?>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr($logged_in_username, 0, 1)) ?>
            </div>
            <div class="user-info">
                <h3><?= htmlspecialchars($logged_in_username) ?></h3>
                <p><?= htmlspecialchars($logged_in_location) ?></p>
            </div>
        </div>
        
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="conversation-search" placeholder="Search conversations..." style="width: 100%; padding: 10px 10px 10px 35px; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 0.95rem;">
            </div>
        </div>
        
        <div class="conversation-list">
            <?php if (empty($conversation_users)): ?>
                <div style="padding: 20px; text-align: center; color: #a0aec0;">
                    <p>No conversations yet. Start a new chat!</p>
                </div>
            <?php else: ?>
                <div id="conversations-list">
                <?php foreach ($conversation_users as $user): ?>
                    <a href="?to_username=<?= urlencode($user['other_user']) ?>" style="text-decoration:none; color:inherit;">
                        <div class="conversation-item <?= isset($to_username) && $to_username == $user['other_user'] ? 'active' : '' ?>" data-username="<?= strtolower(htmlspecialchars($user['other_user'])) ?>">
                            <div class="conversation-avatar">
                                <?= strtoupper(substr($user['other_user'], 0, 1)) ?>
                            </div>
                            <div class="conversation-info">
                                <h3>
                                    <?= htmlspecialchars($user['other_user']) ?>
                                    <span class="conversation-time">
                                        <?= date('h:i A', strtotime($user['last_message_time'])) ?>
                                    </span>
                                </h3>
                                <p><?= mb_strimwidth(htmlspecialchars($user['last_message'] ?? ''), 0, 30, '...') ?></p>
                            </div>
                            <?php if ($user['unread_count'] > 0): ?>
                                <div class="unread-badge">
                                    <?= $user['unread_count'] > 9 ? '9+' : $user['unread_count'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
                </div>
                <div id="no-conversations-found" style="display:none; text-align:center; padding:20px; color:#a0aec0;">
                    <i class="fas fa-search" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p>No conversations match your search</p>
                    <?php if ($logged_in_verified): ?>
                        <button onclick="openLocationModal()" style="margin-top:10px; background:#1a4735; color:white; border:none; border-radius:8px; padding:8px 16px; font-size:0.9rem; cursor:pointer;">
                            Start New Chat
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chat Area -->
    <div class="chat-area">
        <?php if (isset($chat_history)): ?>
            <div class="chat-header">
                <div class="chat-header-avatar">
                    <?= strtoupper(substr($to_username, 0, 1)) ?>
                </div>
                <div class="chat-header-info">
                    <h3><?= htmlspecialchars($to_username) ?></h3>
                    <p><?= $logged_in_verified ? 'Verified user' : 'User' ?></p>
                </div>
            </div>
            
            <div class="chat-messages-container">
                <div class="scroll-indicator" id="scrollIndicator">
                    <i class="fas fa-arrow-up"></i> Scroll up for previous messages
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($chat_history as $chat): ?>
                        <div class="message <?= $chat['from_username'] == $logged_in_username ? 'sent' : 'received' ?>">
                            <div class="message-content">
                                <?= htmlspecialchars($chat['history']) ?>
                                <div class="message-time">
                                    <?= date('h:i A', strtotime($chat['created_at'])) ?>
                                </div>
                            </div>
                            <?php if ($chat['pic']): ?>
                                <div class="message-image">
                                    <img src="<?= htmlspecialchars($chat['pic']) ?>" alt="Image">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="scroll-to-bottom" id="scrollToBottomBtn">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" action="" class="chat-input-container" id="chatForm">
                <label for="attachment" class="file-input-label" id="fileInputLabel">
                    <i class="fas fa-paperclip"></i>
                </label>
                <input type="file" id="attachment" name="attachment" accept="image/*" class="file-input">
                <textarea name="message" placeholder="Type your message here..." required></textarea>
                <input type="hidden" name="to_username" value="<?= htmlspecialchars($to_username) ?>">
                <button type="submit" class="send-button">Send</button>
                <div class="image-preview" id="imagePreview" style="display:none;"></div>
            </form>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-comment-dots"></i>
                <h3>No conversation selected</h3>
                <p>Select a conversation from the sidebar or start a new chat with someone in your community.</p>
                <?php if ($logged_in_verified): ?>
                    <button onclick="openLocationModal()">Find Local Users</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Location Users Modal -->
<div id="location-users-modal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#fff; border-radius:12px; width:100%; max-width:500px; max-height:80vh; overflow-y:auto; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <h3>Users in <?= htmlspecialchars($logged_in_location) ?></h3>
            <button class="modal-close" onclick="closeLocationModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#6b7280;">&times;</button>
        </div>
        <!-- Search bar for users -->
        <div style="padding:15px 20px; border-bottom:1px solid #e5e7eb;">
            <div style="position:relative;">
                <input type="text" id="user-search" placeholder="Search users..." style="width:100%; padding:10px 15px 10px 40px; border-radius:8px; border:1px solid #e5e7eb; font-size:0.95rem;">
                <i class="fas fa-search" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#a0aec0;"></i>
            </div>
        </div>
        <div class="modal-body" style="padding:20px;">
            <?php if (empty($location_users)): ?>
                <p style="text-align:center; color:#a0aec0;">No other users in your location</p>
            <?php else: ?>
                <div id="location-users-list">
                <?php foreach ($location_users as $user): ?>
                    <div class="user-list-item" data-username="<?= strtolower(htmlspecialchars($user['username'])) ?>">
                        <form method="GET" action="" style="display:contents;">
                            <input type="hidden" name="to_username" value="<?= htmlspecialchars($user['username']) ?>">
                            <button type="submit" style="background:none; border:none; display:contents; cursor:pointer; width:100%; text-align:left;">
                                <div class="user-list-avatar" style="width:40px; height:40px; border-radius:50%; background:#c7d2fe; color:#3730a3; display:flex; align-items:center; justify-content:center; font-weight:600; margin-right:12px;">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                                <div class="user-list-name" style="font-weight:500;">
                                    <?= htmlspecialchars($user['username']) ?>
                                </div>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
                </div>
                <div id="no-users-found" style="display:none; text-align:center; padding:20px; color:#a0aec0;">
                    <i class="fas fa-search" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p>No users match your search</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
// Location modal functions
function openLocationModal() {
    document.getElementById('location-users-modal').style.display = 'flex';
    // Focus on search input when modal opens
    setTimeout(() => {
        const searchInput = document.getElementById('user-search');
        if (searchInput) searchInput.focus();
    }, 100);
}

function closeLocationModal() {
    document.getElementById('location-users-modal').style.display = 'none';
    // Clear search when closing
    const searchInput = document.getElementById('user-search');
    if (searchInput) searchInput.value = '';
    filterUsers('');
}

window.onclick = function(event) {
    if (event.target == document.getElementById('location-users-modal')) {
        closeLocationModal();
    }
}

// Function to filter users in the modal
function filterUsers(searchTerm) {
    const userItems = document.querySelectorAll('#location-users-list .user-list-item');
    const noResults = document.getElementById('no-users-found');
    let hasResults = false;
    
    searchTerm = searchTerm.toLowerCase();
    
    userItems.forEach(item => {
        const username = item.getAttribute('data-username');
        if (username.includes(searchTerm)) {
            item.style.display = 'flex';
            hasResults = true;
        } else {
            item.style.display = 'none';
        }
    });
    
    if (noResults) {
        noResults.style.display = hasResults ? 'none' : 'block';
    }
}

// Function to filter conversations in the sidebar
function filterConversations(searchTerm) {
    const conversationItems = document.querySelectorAll('#conversations-list .conversation-item');
    const noResults = document.getElementById('no-conversations-found');
    let hasResults = false;
    
    searchTerm = searchTerm.toLowerCase();
    
    conversationItems.forEach(item => {
        const username = item.getAttribute('data-username');
        if (username.includes(searchTerm)) {
            item.style.display = 'flex';
            hasResults = true;
        } else {
            item.style.display = 'none';
        }
    });
    
    if (noResults) {
        noResults.style.display = hasResults ? 'none' : 'block';
    }
}

// Auto-scroll to bottom of messages
function scrollToBottomOfChat() {
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTo({
            top: chatMessages.scrollHeight,
            behavior: 'auto'
        });
    }
}

// Document ready functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initial scroll to bottom
    scrollToBottomOfChat();
    
    // Set up scroll indicators and buttons
    const chatMessages = document.getElementById('chatMessages');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');
    
    if (chatMessages && scrollIndicator && scrollToBottomBtn) {
        // Show indicator briefly when page loads
        if (chatMessages.scrollHeight > chatMessages.clientHeight) {
            scrollIndicator.classList.add('visible');
            setTimeout(() => {
                scrollIndicator.classList.remove('visible');
            }, 3000);
        }
        
        // Scroll to bottom button functionality
        scrollToBottomBtn.addEventListener('click', function() {
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        });
        
        // Show/hide indicators based on scroll position
        chatMessages.addEventListener('scroll', function() {
            const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight < 50;
            
            // Toggle scroll button visibility
            if (isAtBottom) {
                scrollToBottomBtn.classList.remove('visible');
            } else {
                scrollToBottomBtn.classList.add('visible');
            }
            
            // Show scroll indicator when not at the top
            if (chatMessages.scrollTop > 20) {
                scrollIndicator.classList.remove('visible');
            } else {
                scrollIndicator.classList.add('visible');
                
                // Auto-hide after a few seconds
                clearTimeout(window.scrollTimer);
                window.scrollTimer = setTimeout(() => {
                    scrollIndicator.classList.remove('visible');
                }, 2000);
            }
        });
    }
    
    // User search in modal
    const userSearchInput = document.getElementById('user-search');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', function() {
            filterUsers(this.value);
        });
    }
    
    // Conversation search in sidebar
    const conversationSearchInput = document.getElementById('conversation-search');
    if (conversationSearchInput) {
        conversationSearchInput.addEventListener('input', function() {
            filterConversations(this.value);
        });
    }
    
    // Handle textarea auto-resize
    const messageTextarea = document.querySelector('.chat-input-container textarea');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight < 120) ? (this.scrollHeight + 'px') : '120px';
        });
    }
    
    // File input and preview
    const fileInput = document.getElementById('attachment');
    const fileInputLabel = document.getElementById('fileInputLabel');
    const imagePreview = document.getElementById('imagePreview');
    
    if (fileInput && fileInputLabel && imagePreview) {
        fileInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Only image files are allowed!');
                    fileInput.value = '';
                    fileInputLabel.classList.remove('selected');
                    imagePreview.style.display = 'none';
                    return;
                }
                
                // Check file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB!');
                    fileInput.value = '';
                    fileInputLabel.classList.remove('selected');
                    imagePreview.style.display = 'none';
                    return;
                }
                
                // Show preview
                fileInputLabel.classList.add('selected');
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview"><span class='remove-preview' title='Remove'>&times;</span>`;
                    imagePreview.style.display = 'flex';
                    
                    // Remove preview functionality
                    imagePreview.querySelector('.remove-preview').onclick = function() {
                        fileInput.value = '';
                        fileInputLabel.classList.remove('selected');
                        imagePreview.style.display = 'none';
                    };
                };
                reader.readAsDataURL(file);
            } else {
                fileInputLabel.classList.remove('selected');
                imagePreview.style.display = 'none';
            }
        });
    }
});
</script>
</body>
</html>