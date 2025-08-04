<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Management | E-Shongskar</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php
include 'database.php';
include 'admin_header.php';

// Fetch admin usernames
$admin_usernames = [];
$total_admins = 0;

try {
    $stmt = $pdo->prepare("SELECT username FROM admin ORDER BY username ASC");
    $stmt->execute();
    $admin_usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $total_admins = count($admin_usernames);
} catch (PDOException $e) { 
    $admin_usernames = [];
    $total_admins = 0;
}
?>

<main class="flex-1 p-6 md:p-10">
  <!-- Page Header -->
  <div class="mb-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Management</h1>
        <p class="text-gray-600">View all administrator accounts</p>
      </div>
      <div class="bg-slate-700 rounded-xl shadow-lg p-4 flex items-center">
        <div class="bg-white/30 text-white rounded-full p-2 mr-3">
          <i class="fas fa-user-shield fa-lg"></i>
        </div>
        <div class="text-white">
          <div class="text-2xl font-bold"><?php echo $total_admins; ?></div>
          <div class="text-xs text-slate-200">Total Admins</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Admin List -->
  <div class="bg-white/90 rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-slate-700 to-slate-800 p-6">
      <h2 class="text-xl font-semibold text-white mb-2">
        <i class="fas fa-users-cog mr-2"></i>Administrator Accounts
      </h2>
      <p class="text-slate-200">List of all admin usernames from the admin table</p>
    </div>
    
    <div class="p-6">
      <?php if (empty($admin_usernames)): ?>
      <!-- Empty State -->
      <div class="text-center py-12">
        <i class="fas fa-user-shield text-gray-300 text-6xl mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Administrators Found</h3>
        <p class="text-gray-500">There are currently no administrator accounts in the system.</p>
      </div>
      <?php else: ?>
      
      <!-- Admin Username Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <?php foreach ($admin_usernames as $index => $username): ?>
        <div class="bg-gradient-to-br from-slate-50 to-blue-50 rounded-lg p-4 border border-slate-300 hover:border-blue-400 hover:shadow-md transition-all duration-200">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-gradient-to-br from-slate-600 to-slate-700 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
              <i class="fas fa-user-shield text-white text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs text-slate-600 font-medium uppercase tracking-wide">Admin #<?php echo $index + 1; ?></div>
              <div class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($username); ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Summary -->
      <div class="mt-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <i class="fas fa-info-circle text-slate-600 mr-2"></i>
            <span class="text-slate-800 font-medium">Summary</span>
          </div>
          <span class="text-slate-700">Total: <?php echo $total_admins; ?> administrator<?php echo $total_admins !== 1 ? 's' : ''; ?></span>
        </div>
      </div>
      
      <?php endif; ?>
    </div>
  </div>
</main>

</div>
</div>

</body>
</html>
