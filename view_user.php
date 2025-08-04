<?php
// Set cache control headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'database.php';

if (!isset($_GET['username'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$username = $_GET['username'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: admin_dashboard.php");
    exit;
}

// Get user's reports
$stmt = $pdo->prepare("SELECT * FROM report_issues WHERE username = :username ORDER BY date_reported DESC");
$stmt->execute(['username' => $username]);
$userReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's events
$stmt = $pdo->prepare("SELECT * FROM events WHERE username = :username ORDER BY created_at DESC");
$stmt->execute(['username' => $username]);
$userEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's fund contributions
$stmt = $pdo->prepare("SELECT * FROM fund WHERE username = :username ORDER BY created_at DESC");
$stmt->execute(['username' => $username]);
$userFunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalReports = count($userReports);
$solvedReports = count(array_filter($userReports, function($r) { return $r['status'] === 'Solved'; }));
$totalEvents = count($userEvents);
$totalFunds = count($userFunds);

// Check if user is admin
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = :username");
$stmt->execute(['username' => $username]);
$isAdmin = $stmt->fetchColumn() > 0;

// Check verification status
$isVerified = $user['verified'] === 'verified';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php include 'admin_header.php'; ?>
<main class="flex-1 p-6 md:p-10">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-[#0f2e24] flex items-center">
                <i class="fas fa-user-circle mr-3 text-[#007bff]"></i>
                User Profile
            </h1>
            <button onclick="goBack()" class="inline-flex items-center px-4 py-2 bg-[#007bff] text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition-all duration-200">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </button>
        </div>
        <p class="text-gray-600">Detailed information about <?php echo htmlspecialchars($user['username']); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-[#007bff]">
                    <i class="fas fa-file-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Reports</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalReports; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-[#4CAF50]">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Solved Reports</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $solvedReports; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Events Created</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalEvents; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-hand-holding-usd text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Fund Contributions</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalFunds; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- User Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Basic Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center">
                <i class="fas fa-info-circle mr-2 text-[#007bff]"></i>
                Basic Information
            </h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700">Username:</span>
                    <span class="text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700">Full Name:</span>
                    <span class="text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700">Location:</span>
                    <span class="text-gray-900"><?php echo htmlspecialchars($user['location']); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700">Contact:</span>
                    <span class="text-gray-900"><?php echo htmlspecialchars($user['contact_no']); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700">Occupation:</span>
                    <span class="text-gray-900"><?php echo htmlspecialchars($user['occupation']); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700">Date of Birth:</span>
                    <span class="text-gray-900"><?php echo htmlspecialchars($user['dob']); ?></span>
                </div>
            </div>
        </div>

        <!-- Account Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center">
                <i class="fas fa-shield-alt mr-2 text-[#007bff]"></i>
                Account Status
            </h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-700">Verification Status:</span>
                    <?php if ($isVerified): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-bold">
                            <i class="fas fa-check-circle mr-1"></i> Verified
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-sm font-bold">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-700">Admin Status:</span>
                    <?php if ($isAdmin): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-bold">
                            <i class="fas fa-user-shield mr-1"></i> Admin
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-800 text-sm font-bold">
                            <i class="fas fa-user mr-1"></i> Regular User
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-700">Member Since:</span>
                    <span class="text-gray-900"><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- User Reports -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center">
            <i class="fas fa-file-alt mr-2 text-[#007bff]"></i>
            User Reports (<?php echo $totalReports; ?>)
        </h2>
        <?php if (empty($userReports)): ?>
            <p class="text-gray-500 text-center py-8">No reports submitted yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e6f4ef]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($userReports as $report): ?>
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 font-semibold text-blue-700"><?php echo htmlspecialchars($report['issue_title']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($report['issue_type']); ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-bold
                                    <?php
                                        if ($report['status'] === 'Solved') echo 'bg-green-100 text-green-800';
                                        elseif ($report['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-800';
                                        elseif ($report['status'] === 'Denied') echo 'bg-red-100 text-red-800';
                                        else echo 'bg-purple-100 text-purple-800';
                                    ?>">
                                    <?php echo htmlspecialchars($report['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-500"><?php echo date('M d, Y', strtotime($report['date_reported'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- User Events -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-[#007bff]"></i>
            User Events (<?php echo $totalEvents; ?>)
        </h2>
        <?php if (empty($userEvents)): ?>
            <p class="text-gray-500 text-center py-8">No events created yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e6f4ef]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($userEvents as $event): ?>
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 font-semibold text-blue-700"><?php echo htmlspecialchars(substr($event['event_description'], 0, 50)) . (strlen($event['event_description']) > 50 ? '...' : ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($event['category']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($event['location']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($event['contact']); ?></td>
                            <td class="px-4 py-2 text-gray-500"><?php echo date('M d, Y', strtotime($event['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- User Fund Contributions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center">
            <i class="fas fa-hand-holding-usd mr-2 text-[#007bff]"></i>
            Fund Contributions (<?php echo $totalFunds; ?>)
        </h2>
        <?php if (empty($userFunds)): ?>
            <p class="text-gray-500 text-center py-8">No fund contributions yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e6f4ef]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Purpose</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($userFunds as $fund): ?>
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 font-semibold text-blue-700"><?php echo htmlspecialchars($fund['purpose']); ?></td>
                            <td class="px-4 py-2 font-bold text-green-600">৳<?php echo htmlspecialchars($fund['amount']); ?></td>
                            <td class="px-4 py-2 text-gray-500"><?php echo date('M d, Y', strtotime($fund['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function goBack() {
    // Check if there's a previous page in the browser history
    if (document.referrer && document.referrer !== window.location.href) {
        // Go back to the previous page
        window.history.back();
    } else {
        // Fallback to user_data.php if no previous page
        window.location.href = 'user_data.php';
    }
}
</script>
</body>
</html>
