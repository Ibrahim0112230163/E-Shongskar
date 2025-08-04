<?php
$host = 'localhost';
$dbname = 'user_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}

// Function to calculate the best users
function calculateBestUsers($pdo) {
    $pdo->query("TRUNCATE TABLE best_user");

    $query = "
        SELECT 
            u.username,
            COALESCE(COUNT(DISTINCT e.id) * 4, 0) AS event_score,
            COALESCE(COUNT(DISTINCT r.id) * 5, 0) AS report_score,
            COALESCE(COUNT(DISTINCT f.id) * 10, 0) AS fund_score,
            (COALESCE(COUNT(DISTINCT e.id) * 4, 0) +
             COALESCE(COUNT(DISTINCT r.id) * 5, 0) +
             COALESCE(COUNT(DISTINCT f.id) * 10, 0)) AS total_score
        FROM users u
        LEFT JOIN events e ON u.username = e.username
        LEFT JOIN report_issues r ON u.username = r.username
        LEFT JOIN fund f ON u.username = f.username
        GROUP BY u.username
        ORDER BY total_score DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertQuery = "
        INSERT INTO best_user (username, total_score) 
        VALUES (:username, :total_score)
    "; 
    $insertStmt = $pdo->prepare($insertQuery);

    foreach ($results as $row) {
        $insertStmt->execute([
            'username' => $row['username'],
            'total_score' => $row['total_score']
        ]);
    }
}

//fetch users descending order
function getBestUsersDesc($pdo) {
    $query = "SELECT username, total_score, created_at FROM best_user ORDER BY total_score DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_best_user'])) {
    calculateBestUsers($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['see_best_users_desc'])) {
    $bestUsersDesc = getBestUsersDesc($pdo);
}

// Ensure $bestUsersDesc is always defined
if (!isset($bestUsersDesc)) {
    $bestUsersDesc = getBestUsersDesc($pdo);
}
// Limit to top 3 users
$topThreeUsers = array_slice($bestUsersDesc, 0, 3);

//save the highest valued row to the todays_best 
function filterTodaysUser($pdo) {
    $query = "
        SELECT u.username,bu.created_at, MAX(total_score) as max_score
        FROM users u
        JOIN best_user bu ON u.username = bu.username
        WHERE bu.created_at >= NOW() - INTERVAL 1 DAY
        GROUP BY username
        ORDER BY max_score DESC
        LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $insertQuery = "
            INSERT INTO todays_best (username)
            SELECT :username
            WHERE NOT EXISTS (
                SELECT 1 FROM todays_best
                WHERE username = :username
                AND created_at > NOW() - INTERVAL 1 DAY
            )
        ";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute(['username' => $row['username']]);
    }
}

// today's best users
function getTodaysBestUsers($pdo) {
    $query = "SELECT username, created_at FROM todays_best ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_todays_user'])) {
    filterTodaysUser($pdo);
}

$todaysBestUsers = getTodaysBestUsers($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Users Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php include 'admin_header.php'; ?>
<main class="flex-1 p-6 md:p-10">
  <div class="mb-10">
    <h1 class="text-3xl font-bold text-[#0f2e24] flex items-center mb-2"><i class="fas fa-medal mr-3"></i>User Performance Dashboard</h1>
    <p class="text-gray-500">Recognize and reward the most impactful users based on their contributions.</p>
  </div>
  <!-- Action Buttons -->
  <div class="flex flex-wrap gap-4 justify-center mb-8">
    <form method="post">
      <button type="submit" name="find_best_user" class="inline-flex items-center px-5 py-2 bg-[#007bff] text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition-all duration-200">
        <i class="fas fa-search mr-2"></i> Calculate Best Users
      </button>
    </form>
    <form method="post">
      <button type="submit" name="see_best_users_desc" class="inline-flex items-center px-5 py-2 bg-[#4CAF50] text-white font-semibold rounded-lg shadow hover:bg-green-700 transition-all duration-200">
        <i class="fas fa-list-ol mr-2"></i> View Full Ranking
      </button>
    </form>
  </div>
  <!-- Best Users Section -->
  <section class="mb-10">
    <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center"><i class="fas fa-trophy mr-2"></i>Top Performing Users</h2>
    <div class="bg-white/90 rounded-xl shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <span class="font-semibold text-gray-700">User Rankings</span>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-bold text-xs"><i class="fas fa-crown mr-1"></i> Top 3</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-[#e6f4ef]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rank</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Username</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Total Score</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Last Updated</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php if (empty($topThreeUsers)): ?>
            <tr><td colspan="4" class="text-center py-4 text-gray-400">No user ranking data available.</td></tr>
            <?php else: ?>
            <?php foreach ($topThreeUsers as $index => $user): ?>
            <tr class="<?php echo $index === 0 ? 'bg-yellow-100 font-bold' : ''; ?> hover:bg-blue-50 transition">
              <td class="px-4 py-2"><?php echo $index + 1; ?></td>
              <td class="px-4 py-2 font-semibold text-[#0f2e24]">
                <a href="view_user.php?username=<?php echo urlencode($user['username']); ?>" class="hover:text-blue-900 hover:underline transition-colors">
                  <?php echo htmlspecialchars($user['username']); ?>
                </a>
              </td>
              <td class="px-4 py-2"><span class="inline-block px-2 py-1 rounded-full bg-blue-100 text-blue-800 font-bold text-xs"><?php echo htmlspecialchars($user['total_score']); ?></span></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <!-- Today's Best User Section -->
  <section>
    <h2 class="text-xl font-bold text-green-700 mb-4 flex items-center"><i class="fas fa-star mr-2"></i>Today's Top Performer</h2>
    <div class="bg-white/90 rounded-xl shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <span class="font-semibold text-gray-700">Daily Recognition</span>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs"><i class="fas fa-check mr-1"></i> Selected Today</span>
      </div>
      <div class="text-center mb-4">
        <form method="post">
          <button type="submit" name="filter_todays_user" class="inline-flex items-center px-5 py-2 bg-[#4CAF50] text-white font-semibold rounded-lg shadow hover:bg-green-700 transition-all duration-200">
            <i class="fas fa-filter mr-2"></i> Select Today's Best User
          </button>
        </form>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-[#e6f4ef]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Username</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Recognized At</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach ($todaysBestUsers as $user): ?>
            <tr class="hover:bg-blue-50 transition">
              <td class="px-4 py-2 font-semibold text-[#0f2e24]">
                <a href="view_user.php?username=<?php echo urlencode($user['username']); ?>" class="hover:text-blue-900 hover:underline transition-colors">
                  <?php echo htmlspecialchars($user['username']); ?>
                </a>
              </td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>
<!-- Full Ranking Modal (hidden by default) -->
<div id="fullRankingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full p-6 relative">
    <button type="button" id="closeFullRankingBtn" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4 text-[#0f2e24]">Full User Ranking</h2>
    <div class="overflow-x-auto max-h-96 overflow-y-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-[#e6f4ef]">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rank</th>
            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Username</th>
            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Total Score</th>
            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Last Updated</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php foreach ($bestUsersDesc as $index => $user): ?>
          <tr class="hover:bg-blue-50 transition">
            <td class="px-4 py-2"><?php echo $index + 1; ?></td>
            <td class="px-4 py-2 font-semibold text-[#0f2e24]">
              <a href="view_user.php?username=<?php echo urlencode($user['username']); ?>" class="hover:text-blue-900 hover:underline transition-colors">
                <?php echo htmlspecialchars($user['username']); ?>
              </a>
            </td>
            <td class="px-4 py-2"><span class="inline-block px-2 py-1 rounded-full bg-blue-100 text-blue-800 font-bold text-xs"><?php echo htmlspecialchars($user['total_score']); ?></span></td>
            <td class="px-4 py-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
// Show modal when button is clicked
const fullRankingBtn = document.querySelector('button[name=see_best_users_desc]');
const fullRankingModal = document.getElementById('fullRankingModal');
const closeFullRankingBtn = document.getElementById('closeFullRankingBtn');
if (fullRankingBtn && fullRankingModal && closeFullRankingBtn) {
  fullRankingBtn.addEventListener('click', function(e) {
    e.preventDefault();
    fullRankingModal.classList.remove('hidden');
  });
  closeFullRankingBtn.addEventListener('click', function() {
    fullRankingModal.classList.add('hidden');
  });
  window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      fullRankingModal.classList.add('hidden');
    }
  });
  window.addEventListener('click', function(e) {
    if (e.target === fullRankingModal) {
      fullRankingModal.classList.add('hidden');
    }
  });
}
</script>
</body>
</html>
