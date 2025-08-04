<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | E-Shongskar</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php
include 'database.php';
include 'admin_header.php';

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header('Location: admin_login.php');
    exit;
}

// Fetch all unique locations for filter
$stmt = $pdo->prepare("SELECT DISTINCT location FROM users ORDER BY location ASC");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle filters and search
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$locationFilter = isset($_GET['location']) ? trim($_GET['location']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($searchQuery !== '') {
    $where[] = "(username LIKE :search OR full_name LIKE :search OR contact_no LIKE :search)";
    $params['search'] = "%$searchQuery%";
}
if ($locationFilter !== '' && $locationFilter !== 'All') {
    $where[] = "location = :location";
    $params['location'] = $locationFilter;
}
if ($statusFilter === 'Representative') {
    $where[] = "verified = 'verified'";
} elseif ($statusFilter === 'General') {
    $where[] = "(verified IS NULL OR verified != 'verified')";
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get total user count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = max(1, ceil($totalUsers / $perPage));

// Fetch paginated users
$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY location ASC, username ASC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $stmt->bindValue(is_int($key) ? $key+1 : $key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// User management actions (same as before)
if (isset($_GET['delete_username'])) {
    $usernameToDelete = $_GET['delete_username'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = :username");
        $stmt->execute(['username' => $usernameToDelete]);
        $pdo->commit();
        header('Location: user_data.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to delete user: " . $e->getMessage();
    }
}
if (isset($_POST['verify_username'])) {
    $usernameToVerify = $_POST['verify_username'];
    $stmt = $pdo->prepare("UPDATE users SET verified = 'verified' WHERE username = :username");
    $stmt->execute(['username' => $usernameToVerify]);
    header('Location: user_data.php');
    exit;
}
if (isset($_POST['reset_verification_username'])) {
    $usernameToReset = $_POST['reset_verification_username'];
    $stmt = $pdo->prepare("UPDATE users SET verified = NULL WHERE username = :username");
    $stmt->execute(['username' => $usernameToReset]);
    header('Location: user_data.php');
    exit;
}
if (isset($_POST['make_admin_username'])) {
    $usernameToMakeAdmin = $_POST['make_admin_username'];
    $stmt = $pdo->prepare("SELECT username, password FROM users WHERE username = :username");
    $stmt->execute(['username' => $usernameToMakeAdmin]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (:username, :password)");
        $stmt->execute([
            'username' => $user['username'],
            'password' => $user['password'],
        ]);
        header('Location: user_data.php');
        exit;
    }
}
function buildQueryString($params, $exclude = []) {
    $query = [];
    foreach ($params as $k => $v) {
        if (!in_array($k, $exclude)) {
            $query[] = urlencode($k) . '=' . urlencode($v);
        }
    }
    return implode('&', $query);
}
?>
      <main class="flex-1 p-6 md:p-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center"><i class="fas fa-users mr-3 text-blue-500"></i>User Management</h1>
        <!-- Filter & Search Form -->
        <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end bg-white/80 rounded-xl shadow px-6 py-4">
          <div class="flex flex-col">
            <label for="search" class="text-xs font-semibold text-gray-600 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Username, Name, Contact..." class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none w-48" />
          </div>
          <div class="flex flex-col">
            <label for="location" class="text-xs font-semibold text-gray-600 mb-1">Location</label>
            <select id="location" name="location" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none w-40">
              <option value="All" <?php if ($locationFilter === '' || $locationFilter === 'All') echo 'selected'; ?>>All</option>
              <?php foreach ($locations as $loc): ?>
                <option value="<?php echo htmlspecialchars($loc); ?>" <?php if ($locationFilter === $loc) echo 'selected'; ?>><?php echo htmlspecialchars($loc); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex flex-col">
            <label for="status" class="text-xs font-semibold text-gray-600 mb-1">Status</label>
            <select id="status" name="status" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:outline-none w-36">
              <option value="" <?php if ($statusFilter === '') echo 'selected'; ?>>All</option>
              <option value="Representative" <?php if ($statusFilter === 'Representative') echo 'selected'; ?>>Representative</option>
              <option value="General" <?php if ($statusFilter === 'General') echo 'selected'; ?>>General</option>
            </select>
          </div>
          <div class="flex flex-col justify-end">
            <button type="submit" class="inline-flex items-center px-5 py-2 bg-gradient-to-tr from-blue-500 to-purple-500 text-white font-semibold rounded-lg shadow hover:from-blue-600 hover:to-purple-600 transition-all duration-200">
              <i class="fas fa-search mr-2"></i> Filter/Search
            </button>
          </div>
        </form>
        <!-- End Filter & Search Form -->
        <div class="overflow-x-auto bg-white rounded-xl shadow">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-tr from-blue-100 via-purple-100 to-pink-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Username</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Full Name</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contact</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <?php foreach ($users as $user):
                $isVerified = $user['verified'] == 'verified';
                $isAdmin = false;
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = :username");
                $stmt->execute(['username' => $user['username']]);
                $isAdmin = $stmt->fetchColumn() > 0;
              ?>
              <tr class="<?php echo $isVerified ? 'bg-green-50' : ''; ?> hover:bg-blue-50 transition">
                <td class="px-4 py-2 font-semibold text-blue-700"><?php echo htmlspecialchars($user['nid_birth_certificate']); ?></td>
                <td class="px-4 py-2">
                  <a href="view_user.php?username=<?php echo urlencode($user['username']); ?>" class="text-blue-600 hover:underline font-semibold"><?php echo htmlspecialchars($user['username']); ?></a>
                </td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($user['contact_no']); ?></td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($user['location']); ?></td>
                <td class="px-4 py-2">
                  <?php if ($isVerified) { ?>
                    <span class="inline-block px-2 py-1 rounded-full bg-green-200 text-green-800 text-xs font-bold">Representative</span>
                  <?php } else { ?>
                    <span class="inline-block px-2 py-1 rounded-full bg-yellow-200 text-yellow-800 text-xs font-bold">General</span>
                  <?php } ?>
                  <?php if ($isAdmin) { ?>
                    <span class="inline-block px-2 py-1 rounded-full bg-blue-200 text-blue-800 text-xs font-bold ml-1">Admin</span>
                  <?php } ?>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex flex-wrap gap-2 justify-end">
                    <?php if ($isVerified) { ?>
                      <form method="POST" action="user_data.php" onsubmit="return confirm('Reset verification for this user?');">
                        <input type="hidden" name="reset_verification_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-xs font-bold shadow transition"><i class="fas fa-sync-alt mr-1"></i>Reset</button>
                      </form>
                    <?php } else { ?>
                      <form method="POST" action="user_data.php" onsubmit="return confirm('Make this user a representative?');">
                        <input type="hidden" name="verify_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs font-bold shadow transition"><i class="fas fa-check mr-1"></i>Make Representative</button>
                      </form>
                    <?php } ?>
                    <a href="user_data.php?delete_username=<?php echo $user['username']; ?>" class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-bold shadow transition" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash mr-1"></i>Delete</a>
                    <?php if (!$isAdmin) { ?>
                      <form method="POST" action="user_data.php" onsubmit="return confirm('Make this user an admin?');">
                        <input type="hidden" name="make_admin_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold shadow transition"><i class="fas fa-user-shield mr-1"></i>Make Admin</button>
                      </form>
                    <?php } ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div class="flex justify-center items-center gap-2 mt-6">
          <?php
            $queryParams = $_GET;
            for ($i = 1; $i <= $totalPages; $i++):
              $queryParams['page'] = $i;
              $queryString = buildQueryString($queryParams);
          ?>
            <a href="?<?php echo $queryString; ?>"
               class="px-3 py-1 rounded-lg font-semibold shadow transition-all <?php echo $i == $page ? 'bg-gradient-to-tr from-blue-500 to-purple-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-100'; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
        </div>
      </main>
    </div>
  </div>
</body>
</html>