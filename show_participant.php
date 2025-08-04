<?php
$host = 'localhost';
$dbname = 'user_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $eventId = $_GET['event_id'];

    // Get participants for the event
    $stmt = $pdo->prepare("SELECT users.full_name, users.username FROM participants
                           JOIN users ON participants.username = users.username
                           WHERE participants.event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    
    // Fetch all participants as an associative array
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Participants</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #1d3557;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .header h2 {
            margin: 0;
            font-size: 1.8rem;
        }

        .content {
            padding: 20px;
        }

        .participant {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .participant:last-child {
            border-bottom: none;
        }

        .participant h3 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
        }

        .participant p {
            margin: 0;
            color: #555;
        }

        .no-participants {
            text-align: center;
            padding: 20px;
            color: #888;
        }

        @media (max-width: 768px) {
            .header h2 {
                font-size: 1.5rem;
            }

            .participant h3 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Participants for Event ID: <?php echo htmlspecialchars($eventId); ?></h2>
        </div>
        <div class="content">
            <?php if (!empty($participants)): ?>
                <?php foreach ($participants as $participant): ?>
                    <div class="participant">
                        <h3><?php echo htmlspecialchars($participant['full_name']); ?></h3>
                        <p>Username: <?php echo htmlspecialchars($participant['username']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-participants">
                    <p>No participants found for this event.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
