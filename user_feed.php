<?php
session_start();

include('database.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$loggedInUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$loggedInUser) {
    header('Location: login.php');
    exit;
}

$userLocation = $loggedInUser['location'];

// Fetch events based on the logged-in user's location
$sqlEvents = "SELECT * FROM events WHERE location = :location";
$stmtEvents = $pdo->prepare($sqlEvents);
$stmtEvents->execute(['location' => $userLocation]);

$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

//verified representatives
$sql = "SELECT * FROM users WHERE verified IS NOT NULL AND location = :location";
$stmt = $pdo->prepare($sql);
$stmt->execute(['location' => $userLocation]);

// Fetch the results
$verifiedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$username = $_SESSION['username'];

$stmt = $pdo->prepare($sql);
$stmt->execute(['location' => $userLocation]);

// Fetch total report count
$stmtTotalReports = $pdo->prepare("SELECT COUNT(*) AS report_count FROM report_issues WHERE location = :location");
$stmtTotalReports->execute(['location' => $userLocation]);
$totalReports = $stmtTotalReports->fetch(PDO::FETCH_ASSOC);
$report_count = $totalReports['report_count']; // Assign the fetched count


// Fetch reports, ordered by votes and filtered by location
$stmtReports = $pdo->prepare(
    "SELECT ri.*, u.full_name, u.location AS user_location, u.street, u.contact_no 
FROM report_issues ri
JOIN users u ON ri.username = u.username
WHERE ri.location = :location
ORDER BY ri.username ASC
LIMIT 3
"
);
$stmtReports->execute(['location' => $userLocation]);
$reports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);

// Query to fetch the best citizen from best_user table based on location and total_score
$sqlBestCitizen = "
    SELECT u.username, u.location, bu.total_score
    FROM best_user bu
    JOIN users u ON bu.username = u.username
    WHERE u.location = :location
    ORDER BY bu.total_score DESC
    LIMIT 1
";
$stmtBestCitizen = $pdo->prepare($sqlBestCitizen);
$stmtBestCitizen->execute(['location' => $userLocation]);
$bestCitizen = $stmtBestCitizen->fetch(PDO::FETCH_ASSOC);

// Query to fetch Citizen of the Month from todays_best table with the described conditions
$sqlCitizenOfTheMonth = "
    SELECT username, COUNT(username) as count
    FROM todays_best
    GROUP BY username
    ORDER BY count DESC, username DESC
    LIMIT 4 OFFSET 0
";
$stmtCitizenOfTheMonth = $pdo->prepare($sqlCitizenOfTheMonth);
$stmtCitizenOfTheMonth->execute();
$citizens = $stmtCitizenOfTheMonth->fetchAll(PDO::FETCH_ASSOC);

// tie-breaking 
$chosenCitizen = '';
if (count($citizens) >= 4) {
    $chosenCitizen = $citizens[3]['username'];
} elseif (count($citizens) == 3) {
    $chosenCitizen = $citizens[0]['username'];
} elseif (count($citizens) == 2) {
    $chosenCitizen = $citizens[1]['username'];
} elseif (count($citizens) == 1) {
    $chosenCitizen = $citizens[0]['username'];
}

// Fetch the top 3 reports with photos and issue titles, ordered by date_reported
$stmtPhotos = $pdo->prepare(
    "SELECT photo, issue_title 
     FROM report_issues 
     WHERE location = :location AND photo IS NOT NULL 
     ORDER BY date_reported DESC 
     LIMIT 3"
);
$stmtPhotos->execute(['location' => $userLocation]);
$reportImages = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);


// Fetch top 3 alerts based on user's location and description priority
$sqlAlerts = "
    SELECT *
    FROM alerts
    WHERE location = :location
    ORDER BY 
        CASE 
            WHEN LOWER(description) LIKE '%emergency%' THEN 1
            WHEN LOWER(description) LIKE '%emergencies%' THEN 1
            WHEN LOWER(description) LIKE '%flood%' THEN 1
            WHEN LOWER(description) LIKE '%fire%' THEN 1
            ELSE 2
        END, 
        date DESC
    LIMIT 3
";

$stmtAlerts = $pdo->prepare($sqlAlerts);
$stmtAlerts->execute(['location' => $userLocation]);
$alerts = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feed</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #2c3e50;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1px 30px;
            /* background-color: #ffffff; */
            /* box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); */
        }

        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .header-buttons button {
            padding: 10px 20px;
            background-color: #3498db;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .header-buttons button:hover {
            background-color: #2980b9;
        }

        .dots-button {
            font-size: 24px;
            cursor: pointer;
            border: none;
            background: none;
            text-decoration: none;
            color: #2c3e50;
        }

        .hero {
            text-align: center;
            padding: 50px 20px;
            background: linear-gradient(120deg, #3498db, #2ecc71);
            color: #ffffff;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .hero h1 {
            font-size: 3em;
            margin: 0;
        }

        .hero p {
            font-size: 1.5em;
            margin-top: 10px;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            /* box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); */
            border: 0.5px #e9e9e9 solid;
        }

        .section h2 {
            margin-top: 0;
            font-size: 2em;
            color: #34495e;
        }

        .section p {
            font-size: 1.2em;
            line-height: 1.6;
            margin: 15px 0;
        }

        /* Custom styles for the modal popup */
        .popup-box {
            display: none;
            width: 50%;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-box-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Custom styles for the "See Representative" section */
        .representative-button {
            padding: 8px 22px;
            font-size: 15px;
            background-color: #2980b9;
            color: white;
            border: 2px solid #2980b9;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            width: 100px;
            margin: 30px auto;
            text-align: center;
        }

        /* Additional styles for the like and comment section */
        .like-comment-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .like-button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .like-button:hover {
            background-color: #2980b9;
        }

        .like-count {
            background-color: black;
            border-radius: 5px;
           
            color:white;

            font-weight: bold;
            font-size: 1.2em;
            height: 32px;
            width: 25px;
            margin-left: 2px;

        }

        .comment-form {
            margin-top: 10px;
        }

        .comments-section {
            margin-top: 20px;
        }

        .representative-button:hover {
            background-color: #2980b9;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }

        /* For the background blur effect */
        #popupRepresentative:target~.container {
            filter: blur(4px);
        }

        #popupRepresentative:target {
            display: block;
        }

        /* Custom modal styles for the "See Representative" section */
        .popup-box-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .popup-box-content table th,
        .popup-box-content table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .popup-box-content table th {
            background-color: #f2f2f2;
        }

        .popup-box-content table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Report Issue Section */
        .report-issue {

            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 80px;

        }

        .report-issue h2 {
            font-size: 2em;
            margin-top: 0;
            color: #e74c3c;
        }

        /* Alert Section */
        .alert-section {
            margin-top: 30px;
            margin-bottom: 80px;
            padding: 20px;
            background-color: #f9e5e5;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #e74c3c;
        }

        .alert-section p {
            font-size: 1.2em;
        }

        /* Event Section */
        .event-section {
            margin-top: 30px;
            margin-bottom: 80px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .event-section h2 {
            font-size: 2em;
            color: #2ecc71;
        }

        .event-section p {
            font-size: 1.2em;
            margin-top: 10px;
        }

        /* Event Table */
        .event-table {
            width: 100%;
            border-collapse: collapse;
        }

        .event-table th,
        .event-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .event-table th {
            background-color: #f2f2f2;
        }

        .event-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .moving-text p {
            animation: moveText 10s linear infinite;
            white-space: nowrap;
        }

        @keyframes moveText {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .best_user {
            margin-top: 30px;
            margin-bottom: 50px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px 10px;
            margin-bottom: 80px;
        }

        .ReportedPics {
            padding-top: 100px;
            position: relative;
            width: 60%;
            height: 300px;
            /* Adjust as needed */
            overflow: hidden;
        }

        .slider-image {
            position: absolute;
            top: 0;
            left: 100%;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 1s ease-in-out;
            opacity: 0;
            z-index: 1;
        }

        .slider-image.active {
            left: 0;
            opacity: 1;
            z-index: 2;
        }

        .slider-image.previous {
            left: -100%;
            z-index: 1;
            opacity: 0;
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

    <div class="" style=" background-color: #f8f9fa;">
        <div class="header">
        <div class="logodiv" style="width:100%">
             <a href="user_feed.php">   <img src="logo.png" alt="logo" style="height: 72px; cursor:pointer; "></a>
            </div>
          <div style="display: flex; justify-content:center; margin-right:10px"><a href="chat.php" class="dots-button" style="font-size:20px; display:flex; gap:4px; font-weight:bold; color:#34495e;margin-right: 10px;">Chat<img height="30px" src="messenger.png" alt="dashboard_image"></a></div>  
   
            <a href="dashboard.php" class="dots-button" style="font-size:20px; display:flex; gap:4px; font-weight:bold; color:#34495e">Dashboard<img src="DashboardIconBlack.png" alt="dashboard_image"></a>
        </div>
        <div class="hero" style="background: linear-gradient(to right, #007bff, #6c757d);">
            <h1>Welcome to E-Shongskar, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Driving Local Infrastructure Reformation for a Better Bangladesh</p>

        </div>


        <!--Section_profiles-->
        <div class="section" style="overflow: hidden; background-color:#ebf5ff ">
            <div class="best_user" style="overflow: hidden; background-color:white;">
                <div class="moving-text" style="font-weight:bold; ">
                    <?php if ($bestCitizen && $bestCitizen['total_score'] > 0): ?>
                        <p style="">Best citizen in <?php echo htmlspecialchars($userLocation); ?> is <?php echo htmlspecialchars($bestCitizen['username']); ?>!</p>
                    <?php else: ?>
                        <p>Response more and become the best citizen!</p>
                    <?php endif; ?>
                </div>
                <?php if ($bestCitizen && $bestCitizen['total_score'] > 0): ?>
                    <p style="text-align:center">Best citizen in <?php echo htmlspecialchars($userLocation); ?> is <?php echo htmlspecialchars($bestCitizen['username']); ?>!</p>
                <?php else: ?>
                    <p>Response more and become the best citizen!</p>
                <?php endif; ?>
                <div class="citizen-of-the-month">
                    <p style="text-align:center">Citizen of the Month is <?php echo htmlspecialchars($chosenCitizen); ?>!</p>
                </div>
                <div class="notifications" style="overflow: hidden; background-color:white; text-align:center; padding:20px;">
                    <?php
                    // Fetch notifications for the logged-in user with today's date
                    $currentDate = date('Y-m-d');
                    $stmtNotifications = $pdo->prepare(
                        "SELECT message 
             FROM notifications 
             WHERE username = :username AND date = :date 
             GROUP BY username, message, date"
                    );
                    $stmtNotifications->execute(['username' => $username, 'date' => $currentDate]);
                    $notifications = $stmtNotifications->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (!empty($notifications)): ?>
                        <div id="notification-container">
                            <?php foreach ($notifications as $notification): ?>
                                <p class="notification-message" style="display:none;">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No notifications for today.</p>
                    <?php endif; ?>
                </div>
            </div>
            <script>
                // JavaScript for cycling through notifications
                document.addEventListener('DOMContentLoaded', function() {
                    const messages = document.querySelectorAll('.notification-message');
                    let index = 0;

                    if (messages.length > 0) {
                        const showMessage = () => {
                            messages.forEach((msg, i) => {
                                msg.style.display = i === index ? 'block' : 'none';
                            });
                            index = (index + 1) % messages.length;
                        };

                        showMessage();

                        setInterval(showMessage, 1050);
                    }
                });
            </script>
            <!-- </div>
            <p>Our goal is to empower communities in Bangladesh with improved local infrastructure, ensuring sustainable growth and a better quality of life.</p>
        </div> -->


            <h1 style="text-align: center; color:#2980b9">Total Report Count: <?php echo htmlspecialchars($report_count); ?></h1>


            <!--See Report Issue Section -->
            <div class="report-issue">
                <div class="holdingblock" style="display: flex;">
                    <div class="top3Report" style="width: 100%;">
                        <?php if ($reports): ?>
                            <h3 style="font-size: 21px;">Top 3 Reports in Your Location</h3>
                            <ul>
                                <?php foreach ($reports as $report): ?>
                                    <li>
                                        <strong>Report Title:</strong> <?php echo htmlspecialchars($report['issue_title']); ?><br>
                                        <strong>Report Description:</strong> <?php echo htmlspecialchars($report['description']); ?><br>

                                        <!--    <strong>Votes:</strong> <?php echo htmlspecialchars($report['votes']); ?><br>   -->
                                        <strong> Location:</strong> <?php echo htmlspecialchars($report['location']); ?><br>

                                        <strong style="text-decoration: underline;">User Details:</strong><br>
                                        <strong>Full Name:</strong> <?php echo htmlspecialchars($report['full_name']); ?><br>
                                        <!-- <strong>Location:</strong> <?php echo htmlspecialchars($report['user_location']); ?> -->
                                        <?php if (!empty($report['street'])): ?>
                                            <strong>Street:</strong> <?php echo htmlspecialchars($report['street']); ?><br><br>
                                        <?php endif; ?>
                                        <!-- <strong>Contact Number:</strong> <?php echo htmlspecialchars($report['contact_no']); ?><br> <br> -->
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No reports found for your location.</p>
                        <?php endif; ?>
                    </div>
                    <!-- Reported Pictures Section -->
                    <div class="ReportedPics" style="width: 100%; margin-top: 30px;">
                        <?php if ($reportImages): ?>
                            <?php foreach ($reportImages as $image): ?>
                                <!-- <div><p style="text-align: up; font-weight: bold;"><?php echo htmlspecialchars($image['issue_title']); ?></p>    </div> -->
                                <div style="margin-bottom: 20px; margin-top:20px">
                                
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($image['photo']); ?>" alt="Report Image" class="slider-image" style="width: 100%; height: 500px;">
                                     
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No images available for recent reports.</p>
                        <?php endif; ?>
                    </div>

                </div>
                <a href="Notification_report_issue.php" style="display: flex; justify-content:center;"><button style="background-color:#2980b9;  color:white; font-weight:bold;cursor: pointer; height:20px">See all reports and responses</button></a>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const images = document.querySelectorAll('.ReportedPics .slider-image');
                    let currentIndex = 0;

                    function showNextImage() {
                        // Remove active and previous classes
                        images.forEach((img, index) => {
                            img.classList.remove('active', 'previous');
                            if (index === currentIndex) {
                                img.classList.add('previous'); // Mark the current as previous
                            }
                        });

                        // Move to the next image
                        currentIndex = (currentIndex + 1) % images.length;
                        images[currentIndex].classList.add('active'); // Mark the next image as active
                    }

                    // Initialize the first image
                    images[currentIndex].classList.add('active');

                    // Start the slider
                    setInterval(showNextImage, 3000); // Change image every 3 seconds
                });
            </script>


            <!-- EVENT Section -->
            <h1 style="text-align: center; color:#2980b9;  font-size:35px ;" >Events in Your Area</h1>
            <div class="event-section" style="height:450px; overflow:scroll;">

                <p style="text-align:center; font-weight:bold;">Explore events in <?php echo htmlspecialchars($userLocation); ?> that match your interests!</p>

                <?php if ($events): ?>
                    <!-- <h3>Events Relevant to Your Voluntary Categories</h3> -->
                    <p>These events match the categories you are interested in:</p>

                    <?php
                    // Group events by category
                    $eventsByCategory = [];
                    foreach ($events as $event) {
                        $eventsByCategory[$event['category']][] = $event;
                    }

                    // Display each category and its events
                    foreach ($eventsByCategory as $category => $categoryEvents):
                    ?>
                        <div class="category-box" style=" margin-bottom: 40px; background-color:#f9f9f9; padding:2px 2px 2px 2px; ">
                            <h3 style="font-size: 25px;  text-decoration:underline;margin-top:8px; padding:10px"><?php echo htmlspecialchars($category); ?></h3>
                            <ul>
                                <?php foreach ($categoryEvents as $event): ?>
                                    <li style="margin-top: 40px; padding-bottom:10px">
                                      <div style="display: flex; justify-content:space-between">  
                                      <strong style="width: 90%; font-size:18px; ;">Description:<?php echo htmlspecialchars($event['event_description']); ?></strong> 
                                        <strong>Created At:<?php echo htmlspecialchars($event['created_at']); ?></strong><br>
                                        </div>
                                        <!-- Like Button and Count -->
                                        <div class="like-comment-section" style="display:flex; justify-content:left; margin-top:10px;">
                                            <button class="like-button" data-event-id="<?php echo $event['id']; ?>">Like</button>
                                           <div style="display: flex; text-align:center"> 
                                           <span style="display:flex; justify-content:center;" class="like-count" id="like-count-<?php echo $event['id']; ?>">
                                                <?php
                                                // Fetch like count for the event
                                                $stmtLikeCount = $pdo->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE event_id = :event_id");
                                                $stmtLikeCount->execute(['event_id' => $event['id']]);
                                                $likeCount = $stmtLikeCount->fetch(PDO::FETCH_ASSOC);
                                                echo $likeCount['like_count'];
                                                ?>
                                            </span>
                                            </div>
                                        </div>

                                        <!-- Comment Form -->
                                        <form style="display:flex; justify-content:left"
                                            class="comment-form" data-event-id="<?php echo $event['id']; ?>" method="post">
                                            <textarea name="comment_text" placeholder="Add your comment here..." required></textarea>
                                            <button type="submit" style="height:40px">Post Comment</button>
                                        </form>

                                        <div style="margin-top:2px; margin-bottom:20px" class="comments-section" id="comments-<?php echo $event['id']; ?>">
                                            <!-- Comments for the event will be displayed here -->
                                            <?php
                                            // Fetch comments for the current event
                                            $stmtComments = $pdo->prepare(
                                                "SELECT u.full_name, c.comment_text, c.commented_at
                                         FROM comments c 
                                         JOIN users u ON c.username = u.username
                                         WHERE c.event_id = :event_id
                                         ORDER BY c.commented_at DESC"
                                            );
                                            $stmtComments->execute(['event_id' => $event['id']]);
                                            $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

                                            if ($comments):
                                                foreach ($comments as $comment):
                                            ?>
                                                    <p style="font-size: 15px;"><strong ><?php echo htmlspecialchars($comment['full_name']); ?>:</strong> <?php echo htmlspecialchars($comment['comment_text']); ?> <em>(<?php echo htmlspecialchars($comment['commented_at']); ?>)</em></p>
                                                <?php endforeach;
                                            else: ?>
                                                <p>No comments yet.</p>
                                            <?php endif; ?>
                                        </div>

                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No events found based on your voluntary categories.</p>
                <?php endif; ?>
            </div>

            <!-- JavaScript to handle Like and Comment actions -->
            <script>
                // Like Button functionality
                document.querySelectorAll('.like-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const eventId = this.getAttribute('data-event-id');

                        // AJAX request to add like to the event
                        fetch('like_event.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `event_id=${eventId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update like count after successful like
                                    const likeCountElement = document.getElementById('like-count-' + eventId);
                                    likeCountElement.textContent = parseInt(likeCountElement.textContent) + 1;
                                    // alert("You liked the event!");
                                } else {
                                    alert(data.message); // Display error message
                                }
                            });
                    });
                });

                // Comment form submission
                document.querySelectorAll('.comment-form').forEach(form => {
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        const eventId = this.getAttribute('data-event-id');
                        const commentText = this.querySelector('textarea').value;

                        // AJAX request to post the comment
                        fetch('comment_event.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `event_id=${eventId}&comment_text=${commentText}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert("Your comment has been posted!");
                                    this.querySelector('textarea').value = ''; // Clear the comment input
                                    loadComments(eventId); // Reload comments
                                } else {
                                    alert("Failed to post comment.");
                                }
                            });
                    });
                });

                // Function to load comments dynamically
                function loadComments(eventId) {
                    fetch('get_comments.php?event_id=' + eventId)
                        .then(response => response.json())
                        .then(data => {
                            let commentsSection = document.getElementById('comments-' + eventId);
                            commentsSection.innerHTML = ''; // Clear existing comments
                            data.comments.forEach(comment => {
                                commentsSection.innerHTML += `<p><strong>${comment.full_name}:</strong> ${comment.comment_text} <em>(${comment.commented_at})</em></p>`;
                            });
                        });
                }
            </script>



            <!-- Alert Section -->
            <h1 style="text-align: center; color:#2980b9;  font-size:35px ">Alerts & Announcements</h1>
            <div class="alert-section" style="margin: 40px 0;background-color:white">
               <div style="background-color: #c5fcd2; margin-bottom:20px"> <?php if (!empty($alerts)): ?>
                    <?php foreach ($alerts as $alert): ?>
                        <div style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <h3 style="color: black;"><?= htmlspecialchars($alert['description']) ?></h3>
                            <p style="color: black";>Date: <?= htmlspecialchars($alert['date']) ?></p>
                            <p style="color: black";>Duration: <?= htmlspecialchars($alert['duration']) ?></p>
                            
                        </div>
                    <?php endforeach; ?>
                    <a href="alert.php" style="display: flex; justify-content: center; margin-top: 10px; ">
                                <button style="background-color: #2980b9; color: white; font-weight: bold; cursor: pointer; text-decoration:none; margin-bottom: 10px;">View Alerts</button>
                            </a>
                <?php else: ?>
                    <p style="text-align: center;">No alerts available for your location.</p>
                <?php endif; ?>
                </div>
            </div>


            <h1 style="text-align: center; color:#2980b9"> Connect with Area Representatives</h1>
            <div class="section" style="padding-top: 30px; padding-bottom: 15px">
                <!-- <h2 style="text-align: center;">Wanna see who is the representative in your area?</h2> -->
                <p style="text-align: center;">If you are interested in viewing the representatives in your area, click below.</p>
                <a href="#popupRepresentative" class="representative-button" style="height:35px">See Representatives</a>
            </div>

            <!-- Popup for viewing representative -->
            <div id="popupRepresentative" class="popup-box">
                <div class="popup-box-content">
                    <a href="user_feed.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2>Verified Representatives</h2>
                    <p>Showing representatives for <?php echo htmlspecialchars($username); ?> in your location: <?php echo htmlspecialchars($userLocation); ?></p>
                    <?php if ($verifiedUsers): ?>
                        <table>
                            <tr>
                                <th>Name</th>
                                <th>Contact No</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                            <?php foreach ($verifiedUsers as $user): ?>
                                <tr>
                                    <td><?php echo isset($user['full_name']) ? htmlspecialchars($user['username']) : 'N/A'; ?></td>
                                    <td><?php echo isset($user['contact_no']) ? htmlspecialchars($user['contact_no']) : 'N/A'; ?></td>
                                    <td><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'N/A'; ?></td>
                                    <td><?php echo isset($user['verified']) ? htmlspecialchars($user['verified']) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>No verified representatives found in your location.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</body>

</html>