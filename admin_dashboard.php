<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | E-Shongskar</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php
include 'database.php';
include 'admin_header.php';

// Fetch statistics
$pending_reports = 0;
$resolved_reports = 0;
$total_users = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM report_issues WHERE status != 'Solved'");
    $stmt->execute();
    $pending_reports = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) { $pending_reports = 'Err'; }

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM report_issues WHERE status = 'Solved'");
    $stmt->execute();
    $resolved_reports = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) { $resolved_reports = 'Err'; }

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) { $total_users = 'Err'; }

// Fetch daywise report stats for the graph
$labels = [];
$total_reports = [];
$pending_daywise = [];
$resolved_daywise = [];

try {
    $stmt = $pdo->prepare("
        SELECT DATE(date_reported) as day,
            COUNT(*) as total,
            SUM(status = 'Solved') as resolved,
            SUM(status != 'Solved') as pending
        FROM report_issues
        GROUP BY day
        ORDER BY day ASC
        LIMIT 15
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $labels[] = $row['day'];
        $total_reports[] = (int)$row['total'];
        $resolved_daywise[] = (int)$row['resolved'];
        $pending_daywise[] = (int)$row['pending'];
    }
} catch (PDOException $e) {
    // fallback: empty arrays
}

// Fetch top 3 recent reports
$recent_reports = [];
try {
    $stmt = $pdo->prepare("SELECT id, username, issue_type, status, date_reported FROM report_issues ORDER BY date_reported DESC LIMIT 3");
    $stmt->execute();
    $recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $recent_reports = []; }

// Fetch issue type distribution
$issue_types = [];
$issue_type_counts = [];
try {
    $stmt = $pdo->prepare("SELECT issue_type, COUNT(*) as count FROM report_issues GROUP BY issue_type");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $issue_types[] = $row['issue_type'];
        $issue_type_counts[] = (int)$row['count'];
    }
} catch (PDOException $e) { $issue_types = []; $issue_type_counts = []; }
?>
      <!-- Statistic Cards -->
      <main class="flex-1 p-6 md:p-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <a href="reports.php#unresolved-reports" class="bg-[#66BB6A] rounded-xl shadow-lg p-6 flex flex-col items-center transform hover:scale-105 transition-transform duration-300">
            <div class="bg-white/30 text-[#0f2e24] rounded-full p-3 mb-3 animate-bounce"><i class="fas fa-hourglass-half fa-lg"></i></div>
            <div class="text-4xl font-extrabold text-white drop-shadow"><?php echo $pending_reports; ?></div>
            <div class="text-green-100 mt-1 font-semibold">Pending Reports</div>
          </a>
          <a href="reports.php#resolved-reports" class="bg-[#4CAF50] rounded-xl shadow-lg p-6 flex flex-col items-center transform hover:scale-105 transition-transform duration-300">
            <div class="bg-white/30 text-[#0f2e24] rounded-full p-3 mb-3 animate-bounce"><i class="fas fa-check-circle fa-lg"></i></div>
            <div class="text-4xl font-extrabold text-white drop-shadow"><?php echo $resolved_reports; ?></div>
            <div class="text-green-100 mt-1 font-semibold">Resolved Reports</div>
          </a>
          <a href="user_data.php" class="bg-[#0f2e24] rounded-xl shadow-lg p-6 flex flex-col items-center transform hover:scale-105 transition-transform duration-300">
            <div class="bg-white/30 text-[#007bff] rounded-full p-3 mb-3 animate-bounce"><i class="fas fa-users fa-lg"></i></div>
            <div class="text-4xl font-extrabold text-white drop-shadow"><?php echo $total_users; ?></div>
            <div class="text-white mt-1 font-semibold">Total Users</div>
          </a>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Reports Overview (Line Graph) -->
          <div class="bg-white/90 rounded-xl shadow-lg p-6 flex flex-col">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-700">Reports Overview (Last 14 Days)</h2>
            </div>
            <div class="relative h-80 w-full">
              <canvas id="reportsChart" height="320" width="100%"></canvas>
            </div>
          </div>
          <!-- Issue Type Pie Chart -->
          <div class="bg-white/90 rounded-xl shadow-lg p-6 flex flex-col">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-700">Issue Type Distribution</h2>
            </div>
            <div class="relative h-80 w-full">
              <canvas id="issueTypeChart" height="320" width="100%"></canvas>
            </div>
          </div>
          <!-- Recent Reports -->
          <div class="bg-white/90 rounded-xl shadow-lg p-6 flex flex-col">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-700">Recent Reports</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                  <?php foreach ($recent_reports as $report): ?>
                  <tr class="hover:bg-blue-50 transition">
                    <td class="px-4 py-2 font-semibold text-blue-700"><?php echo htmlspecialchars($report['username']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($report['issue_type']); ?></td>
                    <td class="px-4 py-2">
                      <span class="inline-block px-2 py-1 rounded-full text-xs font-bold
                        <?php
                          if ($report['status'] === 'Solved') echo 'bg-green-200 text-green-800';
                          elseif ($report['status'] === 'Pending') echo 'bg-yellow-200 text-yellow-800';
                          elseif ($report['status'] === 'Denied') echo 'bg-red-200 text-red-800';
                          else echo 'bg-purple-200 text-purple-800';
                        ?>">
                        <?php echo htmlspecialchars($report['status']); ?>
                      </span>
                    </td>
                    <td class="px-4 py-2 text-gray-500"><?php echo htmlspecialchars(date('M d, Y', strtotime($report['date_reported']))); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script>
    // Chart.js graph
    const ctx = document.getElementById('reportsChart').getContext('2d');
    const chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [
          {
            label: 'Total Reports',
            data: <?php echo json_encode($total_reports); ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#6366f1',
          },
          {
            label: 'Unresolved Reports',
            data: <?php echo json_encode($pending_daywise); ?>,
            borderColor: '#f59e42',
            backgroundColor: 'rgba(245,158,66,0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#f59e42',
          },
          {
            label: 'Resolved Reports',
            data: <?php echo json_encode($resolved_daywise); ?>,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#22c55e',
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'top' },
          tooltip: { mode: 'index', intersect: false }
        },
        interaction: { mode: 'nearest', axis: 'x', intersect: false },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, grid: { color: '#f3f4f6' } }
        }
      }
    });
    // Issue Type Pie Chart
    const ctxPie = document.getElementById('issueTypeChart').getContext('2d');
    const pieChart = new Chart(ctxPie, {
      type: 'pie',
      data: {
        labels: <?php echo json_encode($issue_types); ?>,
        datasets: [{
          data: <?php echo json_encode($issue_type_counts); ?>,
          backgroundColor: [
            '#6366f1', '#f472b6', '#34d399', '#fbbf24', '#60a5fa', '#f87171', '#a78bfa', '#facc15', '#4ade80', '#f472b6'
          ],
          borderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'right' },
          tooltip: { enabled: true }
        }
      }
    });
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
      const aside = document.querySelector('aside');
      aside.classList.toggle('hidden');
    });
    // Profile dropdown
    const profileBtn = document.getElementById('profileDropdownBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileBtn && profileDropdown) {
      profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('hidden');
      });
      document.addEventListener('click', function() {
        profileDropdown.classList.add('hidden');
      });
    }
  </script>
</body>
</html>
