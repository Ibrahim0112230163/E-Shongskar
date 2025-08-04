<?php
// authenticate.php
include 'database.php'; // Database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        echo 'Please fill in all fields.';
        exit;
    }

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Password matches, log the user in
        $_SESSION['username'] = $username;

        // Insert or update the user in local_users_online
        $stmtOnline = $pdo->prepare("
            INSERT INTO local_users_online (username, login_time, location)
            VALUES (:username, NOW(), :location)
            ON DUPLICATE KEY UPDATE login_time = NOW()
        ");
        $stmtOnline->execute([
            'username' => $username,
            'location' => $user['location'] // Assuming location exists in users table
        ]);

        // Redirect to the dashboard
        header('Location: user_feed.php');
        exit;
    } else {
        // Invalid login
        echo 'Invalid username or password.';
    }
}
?>