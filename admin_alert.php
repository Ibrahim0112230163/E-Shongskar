<?php
include 'database.php'; 

// Automatically delete expired alerts
$deleteExpiredAlertsQuery = "
    DELETE FROM alerts 
    WHERE DATE_ADD(date, INTERVAL duration DAY) < CURDATE()
";
$pdo->exec($deleteExpiredAlertsQuery);

// form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_alert'])) {
        $alert_id = $_POST['alert_id'];
        $location = $_POST['location'];
        $description = $_POST['description'];
        $date = $_POST['date'];
        $duration = $_POST['duration'];

        // Insert alert into the database
        $stmt = $pdo->prepare("INSERT INTO alerts (alert_id, location, description, date, duration) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$alert_id, $location, $description, $date, $duration]);
    } elseif (isset($_POST['delete_alert'])) {
        $alert_id = $_POST['alert_id'];

        // Delete alert from the database
        $stmt = $pdo->prepare("DELETE FROM alerts WHERE alert_id = ?");
        $stmt->execute([$alert_id]);
    }
}

// Fetch all active alerts
$stmt = $pdo->query("SELECT * FROM alerts");
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Management | Admin Panel</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php include 'admin_header.php'; ?>
<main class="flex-1 p-6 md:p-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#0f2e24] flex items-center"><i class="fas fa-bell mr-3"></i>Public Awareness Alerts</h1>
            <p class="text-gray-500 mb-6">Create and manage public awareness alerts to inform users about important issues.</p>
        </div>
        <button onclick="toggleForm()" class="inline-flex items-center px-5 py-2 bg-gradient-to-tr from-blue-500 to-[#1a4a3a] text-white font-semibold rounded-lg shadow hover:from-blue-600 hover:to-[#0f2e24] transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> Create New Alert
        </button>
    </div>
    <!-- Alert Form (collapsible) -->
    <div id="alert-form" class="mb-8 bg-white/90 rounded-xl shadow-lg px-8 py-6 max-w-2xl mx-auto hidden">
        <h3 class="text-lg font-bold mb-4 flex items-center"><i class="fas fa-bell mr-2"></i>New Alert Details</h3>
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="alert_id" class="block text-xs font-semibold text-gray-600 mb-1">Alert ID</label>
                    <input type="number" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none" id="alert_id" name="alert_id" placeholder="Enter alert ID" required>
                </div>
                <div>
                    <label for="location" class="block text-xs font-semibold text-gray-600 mb-1">Location</label>
                    <input type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none" id="location" name="location" placeholder="Enter location" required>
                </div>
            </div>
            <div class="mt-4">
                <label for="description" class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                <textarea class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none" id="description" name="description" rows="3" placeholder="Enter alert description" required></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="date" class="block text-xs font-semibold text-gray-600 mb-1">Date</label>
                    <input type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none" id="date" name="date" required>
                </div>
                <div>
                    <label for="duration" class="block text-xs font-semibold text-gray-600 mb-1">Duration (days)</label>
                    <input type="number" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none" id="duration" name="duration" placeholder="Enter duration in days" required>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="toggleForm()" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-green-500 text-white font-semibold hover:bg-green-600 transition" name="save_alert"><i class="fas fa-save mr-2"></i>Save Alert</button>
            </div>
        </form>
    </div>
    <!-- Alerts Table -->
    <div class="bg-white/90 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <span class="text-lg font-semibold text-gray-700 flex items-center"><i class="fas fa-list mr-2"></i>Active Alerts</span>
            <span class="inline-block px-3 py-1 rounded-full bg-blue-100 text-[#0f2e24] font-bold text-xs"><?php echo count($alerts); ?> Alerts</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-tr from-blue-100 via-purple-100 to-pink-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Alert ID</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($alerts as $alert): 
                        $expiryDate = date('Y-m-d', strtotime($alert['date'] . " + {$alert['duration']} days"));
                        $isActive = strtotime($expiryDate) >= strtotime(date('Y-m-d'));
                    ?>
                    <tr class="hover:bg-blue-50 transition">
                        <td class="px-4 py-2 font-semibold text-[#0f2e24]"><?php echo htmlspecialchars($alert['alert_id']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($alert['location']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($alert['description']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($alert['date']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($alert['duration']); ?> days</td>
                        <td class="px-4 py-2">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-bold <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $isActive ? 'Active' : 'Expired'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['alert_id']; ?>">
                                <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-bold shadow transition" name="delete_alert" onclick="return confirm('Are you sure you want to delete this alert?');">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($alerts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-400">
                            <i class="fas fa-info-circle mr-2"></i>No active alerts found
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
    function toggleForm() {
        const form = document.getElementById('alert-form');
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }
    // Set today's date as default for the date field
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const dateInput = document.getElementById('date');
        if (dateInput) dateInput.value = today;
    });
</script>
</body>
</html>
