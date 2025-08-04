<?php
session_start();

include 'database.php';

if (!isset($_SESSION['username'])) {
    echo "Please log in to access the funding page.";
    exit();
}

$loggedInUsername = $_SESSION['username'];

// user verified check
$stmt = $pdo->prepare("SELECT verified FROM users WHERE username = :username");
$stmt->execute(['username' => $loggedInUsername]);
$user = $stmt->fetch();

if ($user['verified'] === null) {
    $stmt = $pdo->prepare("SELECT event_id, target_amount FROM fund");
    $stmt->execute();
    $funds = $stmt->fetchAll();
}

//  donations form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $eventId = $_POST['event_id'];
    $username = $_POST['username'];
    $amount = $_POST['amount'];

    if ($amount <= 0) {
        echo "<p style='color: red;'>Donation amount must be greater than zero.</p>";
        exit();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM fund WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    $result = $stmt->fetch();

    if ($result['count'] == 0) {
        echo "<p style='color: red;'>Invalid Event ID. Donation is not possible for this Event ID.</p>";
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO donate (event_id, username, amount) 
        VALUES (:event_id, :username, :amount)
    ");
    $stmt->execute([
        'event_id' => $eventId,
        'username' => $username,
        'amount' => $amount,
    ]);

    echo "<p style='color: green;'>Donation submitted successfully!</p>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_amount'])) {
    $eventId = $_POST['event_id'];
    $username = $_POST['username'];
    $targetAmount = $_POST['target_amount'];
    $verifiedBy = $_POST['verified_by'];

    if ($targetAmount <= 0) {
        echo "<p style='color: red;'>Target amount must be greater than zero.</p>";
        exit();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM fund WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        echo "<p style='color: red;'>Event ID already exists. Please use a unique Event ID.</p>";
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO fund (event_id, username, target_amount, verified_by) 
        VALUES (:event_id, :username, :target_amount, :verified_by)
    ");
    $stmt->execute([
        'event_id' => $eventId,
        'username' => $username,
        'target_amount' => $targetAmount,
        'verified_by' => $verifiedBy,
    ]);

    echo "<p style='color: green;'>Funding created successfully!</p>";
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Management | E-Shongskar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                            500: '#0b493e',
                            /* Main primary color */
                            600: '#094236',
                            700: '#07362d',
                            800: '#052b24',
                            900: '#021d18',
                        },
                        secondary: {
                            50: '#f0f9fa',
                            100: '#d0eff1',
                            200: '#a8dadc',
                            /* accent color */
                            300: '#86c5c8',
                            400: '#5aacaf',
                            500: '#418e91',
                            600: '#377779',
                            700: '#2c6062',
                            800: '#224a4c',
                            900: '#182f30',
                        },
                        gradient: {
                            start: '#e0f7fa',
                            /* Start color of background gradient */
                            end: '#c8e6c9',
                            /* End color of background gradient */
                        },
                        funding: {
                            50: '#f2fcf3',
                            100: '#e3f9e4',
                            200: '#c7f2ca',
                            300: '#92e398',
                            400: '#66ce71',
                            500: '#43b14f',
                            /* Funding success color */
                            600: '#308c3d',
                            700: '#2b6e35',
                            800: '#26582d',
                            900: '#1b3d20',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.01)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    },
                    maxWidth: {
                        container: '1280px',
                    }
                },
            },
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .animate-float {
            animation: float 5s ease-in-out infinite;
        }

        /* Scrollbar customization */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #0b493e;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #094236;
        }

        /* Background styles */
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 140px;
            /* Adjust this value based on your header height */
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
        }

        html {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            height: 100%;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        /* Make sure all sections are transparent to show the background */
        section {
            background: transparent !important;
        }

        /* Animated background pattern with circles */
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
            background: #0b493e;
            /* primary color */
            top: -300px;
            right: -100px;
        }

        .bg-pattern::after {
            background: #a8dadc;
            /* accent color */
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

        /* Fade-in animation */
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

        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        /* Coin animation */
        @keyframes coinDrop {
            0% {
                transform: translateY(-50px) rotate(0deg);
                opacity: 0;
            }

            50% {
                opacity: 1;
            }

            100% {
                transform: translateY(0) rotate(360deg);
                opacity: 1;
            }
        }

        @keyframes coinGlint {

            0%,
            100% {
                opacity: 0;
                transform: translate(-100%, -100%);
            }

            50% {
                opacity: 0.8;
                transform: translate(100%, 100%);
            }
        }

        .coin-animation {
            animation: coinDrop 0.5s ease-in-out forwards;
        }

        .coin-glint {
            animation: coinGlint 3s infinite ease-in-out;
        }

        /* Form validation wiggle animation */
        @keyframes wiggle {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            50% {
                transform: translateX(5px);
            }

            75% {
                transform: translateX(-5px);
            }
        }

        .animate-wiggle {
            animation: wiggle 0.5s ease-in-out;
        }

        /* Loading spinner */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loader {
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 3px solid #0b493e;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        /* Glass morphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        /* Form styling */
        .form-input {
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            padding: 12px 16px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #0b493e;
            box-shadow: 0 0 0 3px rgba(11, 73, 62, 0.1);
            outline: none;
        }

        /* Button styles */
        .btn-primary {
            background: linear-gradient(135deg, #0b493e 0%, #052b24 100%);
            color: white;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 73, 62, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: #0b493e;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Popup styles */
        .popup-form {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 450px;
            max-width: 90vw;
            padding: 0;
            background: transparent;
            z-index: 1000;
            transition: all 0.3s ease-in-out;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 999;
            transition: opacity 0.3s ease-in-out;
        }

        /* Form transition and animation */
        .popup-form.hidden {
            opacity: 0;
            transform: translate(-50%, -48%) scale(0.95);
            pointer-events: none;
        }

        .popup-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        /* Table styles */
        .fund-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        .fund-table th {
            background-color: rgba(11, 73, 62, 0.8);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 16px;
            text-align: left;
        }

        .fund-table th:first-child {
            border-top-left-radius: 12px;
        }

        .fund-table th:last-child {
            border-top-right-radius: 12px;
        }

        .fund-table td {
            padding: 16px;
            background-color: rgba(255, 255, 255, 0.5);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .fund-table tr:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }

        .fund-table tr:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }

        .fund-table tr:hover td {
            background-color: rgba(255, 255, 255, 0.8);
        }

        /* Success message */
        .success-message {
            background: rgba(67, 177, 79, 0.1);
            border-left: 4px solid #43b14f;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
            color: #2b6e35;
        }

        /* Error message */
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
            color: #991b1b;
        }
    </style>
</head>

<body>
    <!-- Background pattern -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>

    <!-- Coin decorations for funding theme -->
    <div class="coin coin-animation" style="top: 20%; right: 15%;"></div>
    <div class="coin coin-animation" style="top: 65%; left: 10%; animation-delay: 0.2s;"></div>
    <div class="coin coin-animation" style="top: 40%; right: 25%; animation-delay: 0.4s;"></div>
    <div class="coin coin-animation" style="bottom: 10%; right: 8%; animation-delay: 0.6s;"></div>

    <?php include 'header.php'; ?>

    <div class="max-w-container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="flex items-center mb-8 animate-fadeIn">
            <a href="response_events.php" class="bg-white/70 backdrop-blur-md rounded-full p-3 mr-4 transition hover:bg-white/90">
                <i class="fas fa-arrow-left text-primary-500"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-primary-500">Community Fundraising</h1>
                <p class="text-gray-600">Support community events and initiatives through donations</p>
            </div>
        </div>

        <?php if ($user['verified'] === null): ?>
            <!-- Section One for Unverified Users -->
            <div class="glass-card p-6 mb-8 animate-fadeIn delay-100">
                <div class="flex items-center mb-4">
                    <i class="fas fa-info-circle text-secondary-400 mr-3 text-xl"></i>
                    <h2 class="text-xl font-semibold text-primary-500">Fund Events</h2>
                </div>
                <div class="bg-yellow-50/70 border-l-4 border-yellow-400 p-4 mb-6 rounded">
                    <p class="text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        You are not verified yet. You can browse available fund events but cannot create new ones.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="fund-table w-full">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Target Amount</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($funds) > 0): ?>
                                <?php foreach ($funds as $fund): ?>
                                    <tr class="hover:bg-white/50 transition">
                                        <td class="font-medium"><?php echo htmlspecialchars($fund['event_id']); ?></td>
                                        <td>
                                            <span class="bg-primary-100/70 text-primary-700 py-1 px-3 rounded-full text-sm font-medium">
                                                ৳<?php echo number_format($fund['target_amount'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fund-progress-bar">
                                                <div class="fund-progress" style="width: 30%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-money-bill-wave text-3xl mb-2 opacity-30"></i>
                                        <p>No fund events available at the moment</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button class="btn-primary flex items-center disabled:opacity-60 disabled:cursor-not-allowed" disabled>
                        <i class="fas fa-hand-holding-usd mr-2"></i>
                        Create Funding
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Section One for Verified Users -->
            <div class="glass-card p-6 mb-8 animate-fadeIn delay-100">
                <div class="flex items-center mb-4">
                    <i class="fas fa-hand-holding-usd text-secondary-400 mr-3 text-xl animate-float"></i>
                    <h2 class="text-xl font-semibold text-primary-500">Create Funding</h2>
                </div>

                <p class="text-gray-600 mb-6">Create funding for community events to gather resources and support from your community members. Set a target amount and manage contributions.</p>

                <div class="flex flex-wrap gap-6">
                    <div class="bg-white/50 backdrop-blur-sm p-5 rounded-lg border border-gray-100 flex-1 min-w-[250px]">
                        <div class="text-primary-500 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>
                            Benefits of Funding
                        </div>
                        <ul class="text-gray-600 text-sm space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-circle text-[6px] mt-1.5 mr-2 text-primary-400"></i>
                                Support local initiatives and events
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-[6px] mt-1.5 mr-2 text-primary-400"></i>
                                Track progress towards funding goals
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-[6px] mt-1.5 mr-2 text-primary-400"></i>
                                Transparent financial management
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white/50 backdrop-blur-sm p-5 rounded-lg border border-gray-100 flex-1 min-w-[250px]">
                        <div class="text-primary-500 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>
                            How it Works
                        </div>
                        <ol class="text-gray-600 text-sm space-y-2">
                            <li class="flex items-start">
                                <span class="bg-primary-100 text-primary-700 h-5 w-5 rounded-full flex items-center justify-center text-xs mr-2 flex-shrink-0">1</span>
                                <span>Create an event funding campaign</span>
                            </li>
                            <li class="flex items-start">
                                <span class="bg-primary-100 text-primary-700 h-5 w-5 rounded-full flex items-center justify-center text-xs mr-2 flex-shrink-0">2</span>
                                <span>Set your funding target amount</span>
                            </li>
                            <li class="flex items-start">
                                <span class="bg-primary-100 text-primary-700 h-5 w-5 rounded-full flex items-center justify-center text-xs mr-2 flex-shrink-0">3</span>
                                <span>Share with your community</span>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="mt-6">
                    <button class="btn-primary flex items-center" id="createFundingBtn">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Create New Funding
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section Two (Donate) -->
        <div class="glass-card p-6 animate-fadeIn delay-200">
            <div class="flex items-center mb-4">
                <i class="fas fa-donate text-secondary-400 mr-3 text-xl animate-float"></i>
                <h2 class="text-xl font-semibold text-primary-500">Make a Donation</h2>
            </div>

            <p class="text-gray-600 mb-6">Show your support by donating to events in your community. Every contribution helps make a difference!</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-gradient-to-br from-gold-100 to-gold-50 rounded-lg p-4 flex flex-col items-center justify-center animate-fadeIn delay-300">
                    <div class="h-14 w-14 rounded-full bg-gold-500/20 flex items-center justify-center mb-3">
                        <i class="fas fa-coins text-gold-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gold-700">Small Gift</h3>
                    <p class="text-center text-sm text-gold-600 mt-1">Every small amount helps the community</p>
                </div>

                <div class="bg-gradient-to-br from-primary-100 to-primary-50 rounded-lg p-4 flex flex-col items-center justify-center animate-fadeIn delay-400">
                    <div class="h-14 w-14 rounded-full bg-primary-500/20 flex items-center justify-center mb-3">
                        <i class="fas fa-hand-holding-heart text-primary-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-primary-700">Medium Support</h3>
                    <p class="text-center text-sm text-primary-600 mt-1">Provide meaningful support to initiatives</p>
                </div>

                <div class="bg-gradient-to-br from-secondary-100 to-secondary-50 rounded-lg p-4 flex flex-col items-center justify-center animate-fadeIn delay-500">
                    <div class="h-14 w-14 rounded-full bg-secondary-500/20 flex items-center justify-center mb-3">
                        <i class="fas fa-gem text-secondary-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-secondary-700">Major Contributor</h3>
                    <p class="text-center text-sm text-secondary-600 mt-1">Make a significant impact with a larger gift</p>
                </div>
            </div>

            <div class="flex justify-center">
                <button class="btn-primary flex items-center" id="donateBtn">
                    <i class="fas fa-heart mr-2"></i>
                    Donate Now
                </button>
            </div>
        </div>



        <!-- Success/Error Messages Section -->
        <div id="messageContainer" class="fixed top-24 right-4 w-80 z-50"></div>

        <!-- Popup Form for Donation -->
        <div class="popup-overlay hidden" id="donatePopupOverlay"></div>
        <div class="popup-form hidden" id="donatePopupForm">
            <div class="glass-card p-6 border border-white/20">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-primary-600 flex items-center">
                        <i class="fas fa-hand-holding-heart mr-3 text-primary-500"></i>
                        Make a Donation
                    </h2>
                    <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none" id="closeDonatePopupBtn">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="donate_event_id" class="block text-sm font-medium text-gray-700 mb-1">Event ID</label>
                        <input type="number" id="donate_event_id" name="event_id" class="form-input bg-white/70 text-primary-800" readonly>
                    </div>

                    <div>
                        <label for="donate_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="donate_username" name="username" value="<?php echo htmlspecialchars($loggedInUsername); ?>" class="form-input bg-white/70 text-primary-800" readonly>
                    </div>

                    <div>
                        <label for="donate_amount" class="block text-sm font-medium text-gray-700 mb-1">Donation Amount (৳)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <span class="text-gray-500">৳</span>
                            </div>
                            <input type="number" id="donate_amount" name="amount" step="0.01" required
                                class="form-input pl-8 focus:ring-2 focus:ring-primary-500" placeholder="0.00">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" class="btn-secondary" id="closeDonatePopupBtnAlt">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-heart mr-2"></i>
                            Donate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Popup Form for Creating Funding -->
        <div class="popup-overlay hidden" id="popupOverlay"></div>
        <div class="popup-form hidden" id="popupForm">
            <div class="glass-card p-6 border border-white/20">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-primary-600 flex items-center">
                        <i class="fas fa-hand-holding-usd mr-3 text-primary-500"></i>
                        Create Funding
                    </h2>
                    <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none" id="closePopupBtn">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="event_id" class="block text-sm font-medium text-gray-700 mb-1">Event ID</label>
                        <input type="number" id="event_id" name="event_id" class="form-input bg-white/70 text-primary-800" readonly>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" name="username" class="form-input bg-white/70 text-primary-800" readonly>
                    </div>

                    <div>
                        <label for="target_amount" class="block text-sm font-medium text-gray-700 mb-1">Target Amount (৳)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <span class="text-gray-500">৳</span>
                            </div>
                            <input type="number" id="target_amount" name="target_amount" step="0.01" required
                                class="form-input pl-8 focus:ring-2 focus:ring-primary-500" placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label for="verified_by" class="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                        <input type="text" id="verified_by" name="verified_by" value="<?php echo htmlspecialchars($loggedInUsername); ?>" class="form-input bg-white/70 text-primary-800" readonly>
                    </div>

                    <div class="pt-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" class="btn-secondary" id="closePopupBtnAlt">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-check-circle mr-2"></i>
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div>
        <?php include 'footer.php'; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const createFundingBtn = document.getElementById('createFundingBtn');
            const popupForm = document.getElementById('popupForm');
            const popupOverlay = document.getElementById('popupOverlay');
            const closePopupBtn = document.getElementById('closePopupBtn');
            const donateBtn = document.getElementById('donateBtn');
            const donatePopupForm = document.getElementById('donatePopupForm');
            const donatePopupOverlay = document.getElementById('donatePopupOverlay');
            const closeDonatePopupBtn = document.getElementById('closeDonatePopupBtn');

            // Create custom elegant popup for event ID input
            function showEventIdPopup(title, callback) {
                // Create the overlay
                const customOverlay = document.createElement('div');
                customOverlay.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center opacity-0 transition-opacity duration-300';
                document.body.appendChild(customOverlay);

                // Create popup content with elegant design
                const popupContent = document.createElement('div');
                popupContent.className = 'glass-card border border-white/30 p-6 w-full max-w-md transform scale-95 opacity-0 transition-all duration-500 animate-fadeIn';
                popupContent.innerHTML = `
            <div class="relative">
                <div class="absolute -top-12 -left-12 w-24 h-24 rounded-full bg-secondary-100/30 blur-xl"></div>
                <div class="absolute -bottom-16 -right-8 w-32 h-32 rounded-full bg-primary-100/30 blur-xl"></div>
                
                <div class="flex justify-between items-center mb-6 relative">
                    <h2 class="text-2xl font-semibold text-primary-600 flex items-center">
                        <i class="fas fa-${title.includes('Donate') ? 'hand-holding-heart' : 'hand-holding-usd'} mr-3 text-primary-500"></i>
                        ${title}
                    </h2>
                    <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none close-popup-btn">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="mb-6 relative">
                    <label for="custom-event-id" class="block text-sm font-medium text-gray-700 mb-2">Event ID</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar-check text-primary-400"></i>
                        </div>
                        <input type="number" id="custom-event-id" 
                               class="form-input pl-10 pr-4 py-3 rounded-lg border-2 border-primary-100 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 bg-white/90 w-full" 
                               placeholder="Enter event ID number">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Please enter the ID of the event you want to ${title.includes('Donate') ? 'donate to' : 'create funding for'}</p>
                </div>
                
                <div class="flex justify-end space-x-3 relative">
                    <button type="button" class="btn-secondary cancel-btn px-5 py-2.5 rounded-lg flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn-primary confirm-btn px-5 py-2.5 rounded-lg flex items-center">
                        <i class="fas fa-check mr-2"></i>
                        Continue
                    </button>
                </div>
            </div>
        `;

                customOverlay.appendChild(popupContent);

                // Show with animation
                setTimeout(() => {
                    customOverlay.classList.add('opacity-100');
                    popupContent.classList.remove('scale-95', 'opacity-0');
                    popupContent.classList.add('scale-100', 'opacity-100');
                    document.getElementById('custom-event-id').focus();
                }, 10);

                // Handle close
                function closePopup() {
                    popupContent.classList.add('scale-95', 'opacity-0');
                    customOverlay.classList.remove('opacity-100');
                    setTimeout(() => {
                        document.body.removeChild(customOverlay);
                    }, 300);
                }

                // Event handlers
                const closeBtn = popupContent.querySelector('.close-popup-btn');
                const cancelBtn = popupContent.querySelector('.cancel-btn');
                const confirmBtn = popupContent.querySelector('.confirm-btn');
                const input = popupContent.querySelector('#custom-event-id');

                closeBtn.addEventListener('click', closePopup);
                cancelBtn.addEventListener('click', closePopup);

                confirmBtn.addEventListener('click', () => {
                    const value = input.value.trim();
                    if (value) {
                        closePopup();
                        callback(value);
                    } else {
                        input.classList.add('border-red-500', 'animate-wiggle');
                        setTimeout(() => input.classList.remove('border-red-500', 'animate-wiggle'), 800);
                    }
                });

                // Handle Enter key
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const value = input.value.trim();
                        if (value) {
                            closePopup();
                            callback(value);
                        } else {
                            input.classList.add('border-red-500', 'animate-wiggle');
                            setTimeout(() => input.classList.remove('border-red-500', 'animate-wiggle'), 800);
                        }
                    }
                });

                // Close on overlay click
                customOverlay.addEventListener('click', (e) => {
                    if (e.target === customOverlay) {
                        closePopup();
                    }
                });
            }

            // Handle Create Funding Button click
            createFundingBtn.addEventListener('click', () => {
                showEventIdPopup('Create Funding', async (eventId) => {
                    try {
                        const response = await fetch('validate_event.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                event_id: eventId
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            document.getElementById('event_id').value = eventId;
                            document.getElementById('username').value = result.username;
                            popupForm.classList.remove('hidden');
                            popupOverlay.classList.remove('hidden');
                        } else {
                            showNotification('Invalid Event ID. Please try again.', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('An error occurred while processing your request.', 'error');
                    }
                });
            });

            // Close the Create Funding Popup
            closePopupBtn.addEventListener('click', () => {
                popupForm.classList.add('hidden');
                popupOverlay.classList.add('hidden');
            });

            // Close with the Alt button if it exists
            const closePopupBtnAlt = document.getElementById('closePopupBtnAlt');
            if (closePopupBtnAlt) {
                closePopupBtnAlt.addEventListener('click', () => {
                    popupForm.classList.add('hidden');
                    popupOverlay.classList.add('hidden');
                });
            }

            popupOverlay.addEventListener('click', () => {
                popupForm.classList.add('hidden');
                popupOverlay.classList.add('hidden');
            });

            // Handle Donation Button click
            donateBtn.addEventListener('click', () => {
                showEventIdPopup('Make a Donation', async (eventId) => {
                    try {
                        const response = await fetch('validate_event.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                event_id: eventId
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            document.getElementById('donate_event_id').value = eventId;
                            document.getElementById('donate_username').value = '<?php echo htmlspecialchars($loggedInUsername); ?>';
                            donatePopupForm.classList.remove('hidden');
                            donatePopupOverlay.classList.remove('hidden');
                        } else {
                            showNotification('Invalid Event ID. Please try again.', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('An error occurred while processing your request.', 'error');
                    }
                });
            });

            // Close donation popup
            closeDonatePopupBtn.addEventListener('click', () => {
                donatePopupForm.classList.add('hidden');
                donatePopupOverlay.classList.add('hidden');
            });

            // Close with the Alt button if it exists
            const closeDonatePopupBtnAlt = document.getElementById('closeDonatePopupBtnAlt');
            if (closeDonatePopupBtnAlt) {
                closeDonatePopupBtnAlt.addEventListener('click', () => {
                    donatePopupForm.classList.add('hidden');
                    donatePopupOverlay.classList.add('hidden');
                });
            }

            donatePopupOverlay.addEventListener('click', () => {
                donatePopupForm.classList.add('hidden');
                donatePopupOverlay.classList.add('hidden');
            });

            // Notification function
            function showNotification(message, type = 'success') {
                const notificationContainer = document.getElementById('messageContainer') || document.body;

                const notification = document.createElement('div');
                notification.className = `glass-card p-4 mb-3 rounded-lg border-l-4 ${type === 'success' ? 'border-green-500 bg-green-50/80' : 'border-red-500 bg-red-50/80'} transform translate-x-full opacity-0 transition-all duration-500 flex items-center`;

                notification.innerHTML = `
            <div class="flex-shrink-0 mr-3">
                ${type === 'success' 
                    ? '<div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center"><i class="fas fa-check text-green-600"></i></div>' 
                    : '<div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-600"></i></div>'}
            </div>
            <div class="flex-1">
                <p class="font-medium ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

                notificationContainer.appendChild(notification);

                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full', 'opacity-0');
                }, 10);

                // Add click handler to close button
                const closeButton = notification.querySelector('button');
                closeButton.addEventListener('click', () => {
                    notification.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => {
                        notificationContainer.removeChild(notification);
                    }, 500);
                });

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.classList.add('translate-x-full', 'opacity-0');
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notificationContainer.removeChild(notification);
                            }
                        }, 500);
                    }
                }, 5000);
            }

            // Handle form submission for creating funding
            const fundingForm = popupForm.querySelector('form');
            fundingForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent traditional form submission

                // Validate target amount input
                const targetAmount = document.getElementById('target_amount').value;

                if (!targetAmount || targetAmount <= 0) {
                    showNotification('Target amount must be greater than zero.', 'error');
                    document.getElementById('target_amount').classList.add('border-red-500', 'animate-wiggle');
                    setTimeout(() => document.getElementById('target_amount').classList.remove('border-red-500', 'animate-wiggle'), 800);
                    return;
                }

                // If validation passes, submit the form data
                popupForm.classList.add('hidden');
                popupOverlay.classList.add('hidden');

                // Show loading state
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'fixed inset-0 bg-black/30 backdrop-blur-sm z-[100] flex items-center justify-center';
                loadingOverlay.innerHTML = `
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-lg flex items-center">
                <div class="loader mr-3"></div>
                <p class="font-medium">Processing your request...</p>
            </div>
        `;
                document.body.appendChild(loadingOverlay);

                // Submit the form after delay for better UX
                setTimeout(() => {
                    this.submit();
                    document.body.removeChild(loadingOverlay);
                    showNotification('Funding created successfully!');
                }, 800);
            });

            // Handle form submission for donation
            const donationForm = donatePopupForm.querySelector('form');
            donationForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent traditional form submission

                // Validate donation amount
                const donationAmount = document.getElementById('donate_amount').value;

                if (donationAmount <= 0) {
                    showNotification('Donation amount must be greater than zero.', 'error');
                    document.getElementById('donate_amount').classList.add('border-red-500', 'animate-wiggle');
                    setTimeout(() => document.getElementById('donate_amount').classList.remove('border-red-500', 'animate-wiggle'), 800);
                    return;
                }

                // If validation passes, submit the form
                donatePopupForm.classList.add('hidden');
                donatePopupOverlay.classList.add('hidden');

                // Show loading state
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'fixed inset-0 bg-black/30 backdrop-blur-sm z-[100] flex items-center justify-center';
                loadingOverlay.innerHTML = `
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-lg flex items-center">
                <div class="loader mr-3"></div>
                <p class="font-medium">Processing your donation...</p>
            </div>
        `;
                document.body.appendChild(loadingOverlay);

                // Submit the form after delay for better UX
                setTimeout(() => {
                    this.submit();
                    document.body.removeChild(loadingOverlay);
                    showNotification('Donation submitted successfully!');
                }, 800);
            });
        });

        // Add decorative animations and additional circles
        document.addEventListener('DOMContentLoaded', function() {
            // Create additional decorative circles for more pattern variety
            const body = document.body;

            const extraCircles = [{
                    size: '100px',
                    top: '35%',
                    left: '15%',
                    color: 'rgba(168, 218, 220, 0.07)',
                    delay: 0
                },
                {
                    size: '140px',
                    top: '18%',
                    left: '75%',
                    color: 'rgba(11, 73, 62, 0.05)',
                    delay: 0.5
                },
                {
                    size: '80px',
                    top: '65%',
                    left: '65%',
                    color: 'rgba(200, 230, 201, 0.08)',
                    delay: 1
                },
                {
                    size: '120px',
                    top: '80%',
                    left: '25%',
                    color: 'rgba(11, 73, 62, 0.06)',
                    delay: 1.5
                },
                {
                    size: '60px',
                    top: '50%',
                    left: '90%',
                    color: 'rgba(168, 218, 220, 0.08)',
                    delay: 2
                },
            ];

            extraCircles.forEach((circle, index) => {
                const div = document.createElement('div');
                div.className = `decorative-circle-extra-${index + 1} fixed rounded-full z-[-1]`;
                div.style.width = circle.size;
                div.style.height = circle.size;
                div.style.top = circle.top;
                div.style.left = circle.left;
                div.style.background = circle.color;
                div.style.animationDelay = `${circle.delay}s`;
                body.appendChild(div);
            });

            // Enhanced animation for all circles
            const allCircles = document.querySelectorAll('[class*="decorative-circle"]');
            allCircles.forEach((circle, i) => {
                // Random gentle movement
                let x = 0,
                    y = 0,
                    scale = 1;

                function moveCircle() {
                    x += (Math.random() - 0.5) * 4;
                    y += (Math.random() - 0.5) * 4;
                    scale += (Math.random() - 0.5) * 0.04;

                    // Limit the movement
                    x = Math.max(-20, Math.min(20, x));
                    y = Math.max(-20, Math.min(20, y));
                    scale = Math.max(0.9, Math.min(1.1, scale));

                    circle.style.transform = `translate(${x}px, ${y}px) scale(${scale})`;

                    // Make movement smoother by using requestAnimationFrame instead of setTimeout
                    setTimeout(moveCircle, 2000 + Math.random() * 1000);
                }

                setTimeout(() => moveCircle(), i * 200);
            });

            // Animate coins if available
            const coins = document.querySelectorAll('.coin');
            coins.forEach((coin, i) => {
                coin.style.animationDelay = (i * 0.2) + 's';

                // Add glint effect to coins
                const glint = document.createElement('div');
                glint.className = 'absolute inset-0 rounded-full bg-gradient-to-br from-white/40 to-transparent z-10 coin-glint';
                coin.appendChild(glint);
            });
        });
    </script>
</body>

</html>

<?php
