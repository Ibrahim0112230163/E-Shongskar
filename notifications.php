<?php

include 'database.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Fetch user data (location)
$stmt = $pdo->prepare("SELECT location FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$userLocation = $user['location']; // Location from profile.php

// Fetch reports matching the user's location
$stmt = $pdo->prepare("SELECT id, description, status, location FROM report_issues WHERE location = :location");
$stmt->execute(['location' => $userLocation]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle voting and intense selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['vote'])) {
        // Save vote action to report_vote table
        $reportId = (int)$_POST['report_id'];
        $stmt = $pdo->prepare("INSERT INTO report_vote (report_id, username, vote_status) VALUES (:report_id, :username, 'voted')");
        $stmt->execute(['report_id' => $reportId, 'username' => $_SESSION['username']]);
    } elseif (isset($_POST['intense_option'])) {
        // Save intense selection to report_vote table
        $reportId = (int)$_POST['report_id'];
        $intenseOption = $_POST['intense_option'];
        $stmt = $pdo->prepare("UPDATE report_vote SET intense_option = :intense_option WHERE report_id = :report_id AND username = :username");
        $stmt->execute(['intense_option' => $intenseOption, 'report_id' => $reportId, 'username' => $_SESSION['username']]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 30px;
            max-width: 900px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            /* For positioning the button */
        }

        h1 {
            font-size: 2.5em;
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 1.8em;
            text-align: center;
            color: #34495e;
            margin-bottom: 30px;
        }

        .notification-info {
            margin-bottom: 20px;
        }

        .notification-info ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .notification-info ul li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #fafafa;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: background-color 0.3s ease;
        }

        .notification-info ul li:hover {
            background-color: #f1f1f1;
        }

        .notification-info .status {
            margin-left: auto;
            padding: 8px 15px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
        }

        .status-solved {
            background-color: #27ae60;
            /* Green */
        }

        .status-pending {
            background-color: #f39c12;
            /* Yellow */
        }

        .status-progress {
            background-color: #3498db;
            /* Blue */
        }

        .status-denied {
            background-color: #e74c3c;
            /* Red */
        }

        .vote-button {
            padding: 10px 15px;
            background-color: #2980b9;
            color: white;
            border: 2px solid #1c5987;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        .vote-button:hover {
            background-color: #1c5987;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .intense-buttons button {
            margin: 0 5px;
            padding: 8px 12px;
            background-color: #f39c12;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        .intense-buttons button:hover {
            background-color: #d17d0e;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
    </style>
</head>

<body>

    <div class="container">
        <!-- Back Button -->
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>


        <h1>Notifications</h1>

        <div class="notification-info">
            <h2>Your Reports and Status</h2>
            <?php if ($reports): ?>
                <ul>
                    <?php foreach ($reports as $report): ?>
                        <li>
                            <div>
                                <strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?><br>
                                <strong>Location:</strong> <?php echo htmlspecialchars($report['location']); ?>
                            </div>
                            <div class="status">
                                <?php
                                $status = htmlspecialchars($report['status']);
                                if ($status == 'Solved') {
                                    echo "<span class='status-solved'>$status</span>";
                                } elseif ($status == 'Pending') {
                                    echo "<span class='status-pending'>$status</span>";
                                } elseif ($status == 'In Progress') {
                                    echo "<span class='status-progress'>$status</span>";
                                } elseif ($status == 'Denied') {
                                    echo "<span class='status-denied'>$status</span>";
                                }
                                ?>
                            </div>
                            <form method="post">
                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                <?php
                                $voteCheckStmt = $pdo->prepare("SELECT vote_status, IFNULL(intense_option, '') AS intense_option FROM report_vote WHERE report_id = :report_id AND username = :username");
                                $voteCheckStmt->execute(['report_id' => $report['id'], 'username' => $_SESSION['username']]);
                                $voteData = $voteCheckStmt->fetch(PDO::FETCH_ASSOC);

                                if (!$voteData): ?>
                                    <button type="submit" name="vote" class="vote-button">Vote It</button>
                                <?php elseif ($voteData['vote_status'] === 'voted' && empty($voteData['intense_option'])): ?>
                                    <div class="intense-buttons">
                                        <button type="submit" name="intense_option" value="No">No</button>
                                        <button type="submit" name="intense_option" value="Yes">Yes</button>
                                        <button type="submit" name="intense_option" value="High">High</button>
                                    </div>
                                <?php else: ?>
                                    <span>Intense Option Selected: <?php echo htmlspecialchars($voteData['intense_option']); ?></span>
                                <?php endif; ?>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No reports found for your location.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>