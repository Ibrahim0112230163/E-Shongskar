<?php
include 'database.php';
session_start();

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header('Location: admin_login.php');
    exit;
}

// Pagination parameters for unresolved reports
$unresolvedPage = isset($_GET['unresolved_page']) && is_numeric($_GET['unresolved_page']) && $_GET['unresolved_page'] > 0 ? (int)$_GET['unresolved_page'] : 1;
$unresolvedPerPage = 10;
$unresolvedOffset = ($unresolvedPage - 1) * $unresolvedPerPage;

// Pagination parameters for resolved reports
$resolvedPage = isset($_GET['resolved_page']) && is_numeric($_GET['resolved_page']) && $_GET['resolved_page'] > 0 ? (int)$_GET['resolved_page'] : 1;
$resolvedPerPage = 10;
$resolvedOffset = ($resolvedPage - 1) * $resolvedPerPage;

// Get total count for unresolved reports
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM report_issues ri
    WHERE ri.status != 'Solved'
");
$stmt->execute();
$totalUnresolved = $stmt->fetchColumn();
$totalUnresolvedPages = max(1, ceil($totalUnresolved / $unresolvedPerPage));

// Get total count for resolved reports
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM report_issues ri
    WHERE ri.status = 'Solved'
");
$stmt->execute();
$totalResolved = $stmt->fetchColumn();
$totalResolvedPages = max(1, ceil($totalResolved / $resolvedPerPage));

// Fetch unresolved reports with pagination
$stmt = $pdo->prepare("
    SELECT 
    ri.id, ri.username, ri.location, ri.description, ri.contact, ri.date_reported, ri.status, ri.issue_title, ri.issue_type, ri.photo, 
    COUNT(rv.id) AS total_vote
FROM report_issues ri
LEFT JOIN report_vote rv ON rv.report_id = ri.id AND rv.vote_status = 'voted' AND rv.intense_option != 'No'
WHERE ri.status != 'Solved'
GROUP BY ri.id
ORDER BY total_vote DESC
LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $unresolvedPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $unresolvedOffset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle the delete operation or status update
if (isset($_POST['action']) && isset($_POST['report_id'])) {
    $action = $_POST['action'];
    $reportId = $_POST['report_id'];

    if ($action === "Delete") {
        $deleteStmt = $pdo->prepare("DELETE FROM report_issues WHERE id = :id");
        $deleteStmt->execute(['id' => $reportId]);
    } elseif (in_array($action, ["Solved", "Pending", "In Progress", "Denied"])) {
        $updateStmt = $pdo->prepare("UPDATE report_issues SET status = :status WHERE id = :id");
        $updateStmt->execute(['status' => $action, 'id' => $reportId]);
    }

    header('Location: reports.php');
    exit;
}

// Fetch top 3 reports with the highest votes
$stmtTopReports = $pdo->prepare("
    SELECT 
        ri.id, ri.username, ri.location, ri.description, ri.contact, ri.date_reported, ri.status, ri.issue_title, ri.issue_type, ri.photo, 
        (SELECT COUNT(*) 
         FROM report_vote rv 
         WHERE rv.report_id = ri.id AND rv.vote_status = 'voted' AND rv.intense_option != 'No') AS total_vote 
    FROM report_issues ri 
    WHERE ri.status != 'Solved'
    ORDER BY total_vote DESC 
    LIMIT 3
");
$stmtTopReports->execute();
$topReports = $stmtTopReports->fetchAll(PDO::FETCH_ASSOC);

// Fetch resolved reports with pagination
$stmtSolved = $pdo->prepare("
    SELECT 
        ri.id, ri.username, ri.location, ri.description, ri.contact, ri.date_reported, ri.status, ri.issue_title, ri.issue_type, ri.photo, 
        (SELECT COUNT(*) 
         FROM report_vote rv 
         WHERE rv.report_id = ri.id AND rv.vote_status = 'voted' AND rv.intense_option != 'No') AS total_vote 
    FROM report_issues ri
    WHERE ri.status = 'Solved'
    ORDER BY ri.date_reported DESC
    LIMIT :limit OFFSET :offset
");
$stmtSolved->bindValue(':limit', $resolvedPerPage, PDO::PARAM_INT);
$stmtSolved->bindValue(':offset', $resolvedOffset, PDO::PARAM_INT);
$stmtSolved->execute();
$solvedReports = $stmtSolved->fetchAll(PDO::FETCH_ASSOC);

// Helper function to build pagination URLs
function buildPaginationUrl($params, $exclude = []) {
    $query = [];
    foreach ($params as $k => $v) {
        if (!in_array($k, $exclude)) {
            $query[] = urlencode($k) . '=' . urlencode($v);
        }
    }
    return implode('&', $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php include 'admin_header.php'; ?>
<main class="flex-1 p-6 md:p-10">
  <div class="mb-10">
    <h1 class="text-3xl font-bold text-[#0f2e24] flex items-center mb-2"><i class="fas fa-file-alt mr-3"></i>Reports Dashboard</h1>
    <p class="text-gray-500">Manage, review, and resolve all reported issues from users. Prioritize based on votes and urgency.</p>
  </div>
  <!-- Top Reports Section -->
  <section class="mb-10">
    <h2 class="text-xl font-bold text-purple-700 mb-4 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Top Priority Reports</h2>
    <div class="bg-white/90 rounded-xl shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <span class="font-semibold text-gray-700">Most Voted Issues</span>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-pink-100 text-pink-700 font-bold text-xs"><i class="fas fa-bolt mr-1"></i> Immediate Attention</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-tr from-blue-100 via-purple-100 to-pink-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">User</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Votes</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Actions</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Image</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach ($topReports as $report): ?>
            <tr class="<?php echo $report['total_vote'] > 10 ? 'bg-red-100 animate-pulse' : ''; ?> hover:bg-blue-50 transition">
              <td class="px-4 py-2 font-semibold text-[#0f2e24]">
                <a href="view_user.php?username=<?php echo urlencode($report['username']); ?>" class="hover:text-blue-900 hover:underline transition-colors">
                  <?php echo htmlspecialchars($report['username']); ?>
                </a>
              </td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['location']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['issue_title']); ?></td>
              <td class="px-4 py-2"><?php echo $report['total_vote']; ?></td>
              <td class="px-4 py-2">
                <span class="inline-block px-2 py-1 rounded-full text-xs font-bold
                  <?php
                    if ($report['status'] === 'Solved') echo 'bg-green-100 text-green-800';
                    elseif ($report['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-800';
                    elseif ($report['status'] === 'Denied') echo 'bg-red-100 text-red-800';
                    else echo 'bg-purple-100 text-purple-800';
                  ?>"><?php echo htmlspecialchars($report['status']); ?></span>
              </td>
              <td class="px-4 py-2">
                <form method="POST" action="reports.php" class="flex gap-2">
                  <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                  <select name="action" class="px-2 py-1 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none text-xs">
                    <option value="Solved" <?php if ($report['status'] === "Solved") echo "selected"; ?>>Solved</option>
                    <option value="Delete">Delete</option>
                  </select>
                  <button type="submit" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold shadow transition">Update</button>
                </form>
              </td>
              <td class="px-4 py-2">
                <button type="button" class="view-report-btn text-blue-600 hover:underline flex items-center gap-1 text-xs" 
                  data-id="<?php echo $report['id']; ?>"
                  data-username="<?php echo htmlspecialchars($report['username']); ?>"
                  data-location="<?php echo htmlspecialchars($report['location']); ?>"
                  data-title="<?php echo htmlspecialchars($report['issue_title']); ?>"
                  data-type="<?php echo htmlspecialchars($report['issue_type'] ?? ''); ?>"
                  data-description="<?php echo htmlspecialchars($report['description']); ?>"
                  data-contact="<?php echo htmlspecialchars($report['contact']); ?>"
                  data-date="<?php echo htmlspecialchars($report['date_reported']); ?>"
                  data-status="<?php echo htmlspecialchars($report['status']); ?>"
                  data-votes="<?php echo $report['total_vote'] ?? 0; ?>"
                  data-image="<?php echo !empty($report['photo']) ? 'data:image/jpeg;base64,'.base64_encode($report['photo']) : ''; ?>">
                  View <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <!-- Unresolved Reports Section -->
  <section id="unresolved-reports" class="mb-10">
    <h2 class="text-xl font-bold text-purple-700 mb-4 flex items-center"><i class="fas fa-tasks mr-2"></i>Unresolved Reports</h2>
    <div class="bg-white/90 rounded-xl shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <span class="font-semibold text-gray-700">All Pending Issues</span>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-bold text-xs"><i class="fas fa-hourglass-half mr-1"></i> Requires Action</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-tr from-blue-100 via-purple-100 to-pink-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">User</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Votes</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Actions</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Image</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach ($reports as $report): ?>
            <tr class="hover:bg-blue-50 transition">
              <td class="px-4 py-2 font-semibold text-[#0f2e24]">
                <a href="view_user.php?username=<?php echo urlencode($report['username']); ?>" class="hover:text-blue-900 hover:underline transition-colors">
                  <?php echo htmlspecialchars($report['username']); ?>
                </a>
              </td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['location']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['issue_title']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['issue_type']); ?></td>
              <td class="px-4 py-2"><?php echo $report['total_vote']; ?></td>
              <td class="px-4 py-2">
                <span class="inline-block px-2 py-1 rounded-full text-xs font-bold
                  <?php
                    if ($report['status'] === 'Solved') echo 'bg-green-100 text-green-800';
                    elseif ($report['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-800';
                    elseif ($report['status'] === 'Denied') echo 'bg-red-100 text-red-800';
                    elseif ($report['status'] === 'In Progress') echo 'bg-purple-100 text-purple-800';
                    else echo 'bg-gray-100 text-gray-800';
                  ?>"><?php echo htmlspecialchars($report['status']); ?></span>
              </td>
              <td class="px-4 py-2">
                <form method="POST" action="reports.php" class="flex gap-2">
                  <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                  <select name="action" class="px-2 py-1 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none text-xs">
                    <option value="Solved" <?php if ($report['status'] === "Solved") echo "selected"; ?>>Solved</option>
                    <option value="Pending" <?php if ($report['status'] === "Pending") echo "selected"; ?>>Pending</option>
                    <option value="In Progress" <?php if ($report['status'] === "In Progress") echo "selected"; ?>>In Progress</option>
                    <option value="Denied" <?php if ($report['status'] === "Denied") echo "selected"; ?>>Denied</option>
                    <option value="Delete">Delete</option>
                  </select>
                  <button type="submit" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold shadow transition">Update</button>
                </form>
              </td>
              <td class="px-4 py-2">
                <button type="button" class="view-report-btn text-blue-600 hover:underline flex items-center gap-1 text-xs" 
                  data-id="<?php echo $report['id']; ?>"
                  data-username="<?php echo htmlspecialchars($report['username']); ?>"
                  data-location="<?php echo htmlspecialchars($report['location']); ?>"
                  data-title="<?php echo htmlspecialchars($report['issue_title']); ?>"
                  data-type="<?php echo htmlspecialchars($report['issue_type'] ?? ''); ?>"
                  data-description="<?php echo htmlspecialchars($report['description']); ?>"
                  data-contact="<?php echo htmlspecialchars($report['contact']); ?>"
                  data-date="<?php echo htmlspecialchars($report['date_reported']); ?>"
                  data-status="<?php echo htmlspecialchars($report['status']); ?>"
                  data-votes="<?php echo $report['total_vote'] ?? 0; ?>"
                  data-image="<?php echo !empty($report['photo']) ? 'data:image/jpeg;base64,'.base64_encode($report['photo']) : ''; ?>">
                  View <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Unresolved Reports Pagination -->
      <?php if ($totalUnresolvedPages > 1): ?>
      <div class="flex flex-col items-center gap-2 mt-6">
        <div class="text-sm text-gray-600 mb-2">
          Showing page <?php echo $unresolvedPage; ?> of <?php echo $totalUnresolvedPages; ?> 
          (<?php echo $totalUnresolved; ?> total unresolved reports)
        </div>
        <div class="flex justify-center items-center gap-2">
          <?php
            $queryParams = $_GET;
            for ($i = 1; $i <= $totalUnresolvedPages; $i++):
              $queryParams['unresolved_page'] = $i;
              $queryString = buildPaginationUrl($queryParams);
          ?>
            <a href="?<?php echo $queryString; ?>"
               class="px-3 py-1 rounded-lg font-semibold shadow transition-all <?php echo $i == $unresolvedPage ? 'bg-gradient-to-tr from-blue-500 to-purple-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-100'; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <!-- Solved Reports Section -->
  <section id="resolved-reports">
    <h2 class="text-xl font-bold text-green-700 mb-4 flex items-center"><i class="fas fa-check-circle mr-2"></i>Resolved Reports</h2>
    <div class="bg-white/90 rounded-xl shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <span class="font-semibold text-gray-700">Completed Issues</span>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs"><i class="fas fa-check mr-1"></i> Successfully Resolved</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-tr from-blue-100 via-purple-100 to-pink-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">User</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date Reported</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Votes</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Image</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach ($solvedReports as $report): ?>
            <tr class="hover:bg-blue-50 transition">
              <td class="px-4 py-2 font-semibold text-[#0f2e24]">
                <a href="view_user.php?username=<?php echo urlencode($report['username']); ?>" class="hover:text-blue-900 hover:underline transition-colors">
                  <?php echo htmlspecialchars($report['username']); ?>
                </a>
              </td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['location']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['issue_title']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($report['date_reported']); ?></td>
              <td class="px-4 py-2"><?php echo $report['total_vote']; ?></td>
              <td class="px-4 py-2">
                <span class="inline-block px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                  <?php echo htmlspecialchars($report['status']); ?>
                </span>
              </td>
              <td class="px-4 py-2">
                <button type="button" class="view-report-btn text-blue-600 hover:underline flex items-center gap-1 text-xs" 
                  data-id="<?php echo $report['id']; ?>"
                  data-username="<?php echo htmlspecialchars($report['username']); ?>"
                  data-location="<?php echo htmlspecialchars($report['location']); ?>"
                  data-title="<?php echo htmlspecialchars($report['issue_title']); ?>"
                  data-type="<?php echo htmlspecialchars($report['issue_type'] ?? ''); ?>"
                  data-description="<?php echo htmlspecialchars($report['description']); ?>"
                  data-contact="<?php echo htmlspecialchars($report['contact']); ?>"
                  data-date="<?php echo htmlspecialchars($report['date_reported']); ?>"
                  data-status="<?php echo htmlspecialchars($report['status']); ?>"
                  data-votes="<?php echo $report['total_vote'] ?? 0; ?>"
                  data-image="<?php echo !empty($report['photo']) ? 'data:image/jpeg;base64,'.base64_encode($report['photo']) : ''; ?>">
                  View <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Resolved Reports Pagination -->
      <?php if ($totalResolvedPages > 1): ?>
      <div class="flex flex-col items-center gap-2 mt-6">
        <div class="text-sm text-gray-600 mb-2">
          Showing page <?php echo $resolvedPage; ?> of <?php echo $totalResolvedPages; ?> 
          (<?php echo $totalResolved; ?> total resolved reports)
        </div>
        <div class="flex justify-center items-center gap-2">
          <?php
            $queryParams = $_GET;
            for ($i = 1; $i <= $totalResolvedPages; $i++):
              $queryParams['resolved_page'] = $i;
              $queryString = buildPaginationUrl($queryParams);
          ?>
            <a href="?<?php echo $queryString; ?>"
               class="px-3 py-1 rounded-lg font-semibold shadow transition-all <?php echo $i == $resolvedPage ? 'bg-gradient-to-tr from-green-500 to-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-green-100'; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<!-- Modal HTML at end of file -->
<div id="reportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white rounded-xl shadow-lg max-w-lg w-full p-6 relative">
    <button id="closeModalBtn" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
    <h2 class="text-xl font-bold mb-2 text-[#0f2e24]" id="modalTitle"></h2>
    <div class="mb-2 text-gray-700 text-sm" id="modalMeta"></div>
    <div class="mb-4 text-gray-800" id="modalDescription"></div>
    <img id="modalImage" src="" alt="Report Image" class="rounded-lg mb-4 max-h-64 w-full object-contain hidden" />
    <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
      <div><span class="font-bold">User:</span> <span id="modalUsername"></span></div>
      <div><span class="font-bold">Location:</span> <span id="modalLocation"></span></div>
      <div><span class="font-bold">Type:</span> <span id="modalType"></span></div>
      <div><span class="font-bold">Contact:</span> <span id="modalContact"></span></div>
      <div><span class="font-bold">Date:</span> <span id="modalDate"></span></div>
      <div><span class="font-bold">Status:</span> <span id="modalStatus"></span></div>
      <div><span class="font-bold">Votes:</span> <span id="modalVotes"></span></div>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.view-report-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('modalTitle').textContent = this.dataset.title;
    document.getElementById('modalMeta').textContent = `Report ID: ${this.dataset.id}`;
    document.getElementById('modalDescription').textContent = this.dataset.description;
    document.getElementById('modalUsername').textContent = this.dataset.username;
    document.getElementById('modalLocation').textContent = this.dataset.location;
    document.getElementById('modalType').textContent = this.dataset.type;
    document.getElementById('modalContact').textContent = this.dataset.contact;
    document.getElementById('modalDate').textContent = this.dataset.date;
    document.getElementById('modalStatus').textContent = this.dataset.status;
    document.getElementById('modalVotes').textContent = this.dataset.votes;
    const img = document.getElementById('modalImage');
    if (this.dataset.image) {
      img.src = this.dataset.image;
      img.classList.remove('hidden');
    } else {
      img.src = '';
      img.classList.add('hidden');
    }
    document.getElementById('reportModal').classList.remove('hidden');
  });
});
document.getElementById('closeModalBtn').onclick = function() {
  document.getElementById('reportModal').classList.add('hidden');
};
window.onclick = function(event) {
  const modal = document.getElementById('reportModal');
  if (event.target === modal) {
    modal.classList.add('hidden');
  }
};
</script>
</body>
</html>
