<?php

include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT location FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$activeUsersStmt = $pdo->prepare("
    SELECT lou.username, u.contact_no 
    FROM local_users_online lou
    JOIN users u ON lou.username = u.username
    WHERE lou.location = :location AND lou.username != :username
");
$activeUsersStmt->execute([
    'location' => $user['location'],
    'username' => $_SESSION['username']
]);
$activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Users Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f5f7;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #34495e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background-color: #3498db;
            color: #fff;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            font-weight: 700;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .back-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            text-align: center;
        }
        .back-button {
            display: inline-block;
            padding: 10px;
            background-color: rgb(165, 170, 171);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background-color: rgb(74, 142, 187);
        }

        .back-button i {
            margin-right: 5px;
        }
        .back-btn:hover {
            background: #27ae60;
        }
    </style>
</head>

<body>
    <div class="container">
    <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>

        <h1>Active Users in Your Location</h1>
        <table>
            <thead>
                <tr>
                    <th style="text-align:center">Username</th>
                    <!-- <th>Contact Number</th> -->
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($activeUsers)): ?>
                    <?php foreach ($activeUsers as $user): ?>
                        <tr>
                            <td style="text-align:center"><?php echo htmlspecialchars($user['username']); ?></td>
                            <!-- <td><?php echo htmlspecialchars($user['contact_no']); ?></td> -->
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align: center;">No active users found in your location.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

      
    </div>
</body>

</html>