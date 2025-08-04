<?php
// Set cache control headers to prevent caching on all admin pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) session_start();
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
$adminProfile = isset($_SESSION['admin_profile']) ? $_SESSION['admin_profile'] : 'https://randomuser.me/api/portraits/women/44.jpg';

// Get current page name for highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Force cache refresh
$cacheBuster = '?v=' . time();
?>
<div class="flex min-h-screen">
  <style>
    /* Force hover color for sidebar navigation */
    aside nav ul li a:hover {
      background-color: #0f2e24 !important;
    }
  </style>
  <!-- Sidebar -->
  <aside class="w-64 bg-[#1a4a3a] shadow-xl hidden md:flex flex-col py-8 px-4 sticky top-0 h-screen overflow-y-auto">
    <div class="flex items-center mb-10">
      <img src="logo.png" alt="Logo" class="h-10 w-10 mr-2">
      <span class="font-bold text-xl text-white">E–Shongskar</span>
    </div>
    <nav class="flex-1">
      <style>
        .sidebar-nav a:hover {
          background-color: #0f2e24 !important;
        }
      </style>
      <ul class="space-y-2 sidebar-nav">
        <li><a href="admin_dashboard.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'admin_dashboard.php' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-home mr-3"></i>Dashboard</a></li>
        <li><a href="user_data.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'user_data.php' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-users mr-3"></i>User Data</a></li>
        <li><a href="admin_alert.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'admin_alert.php' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-triangle-exclamation mr-3"></i>Make Alert</a></li>
        <li><a href="reports.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'reports.php' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-file-alt mr-3"></i>Reports</a></li>
        <li><a href="best_users.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'best_users.php' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-medal mr-3"></i>Impactful Users</a></li>
        <li><a href="admin_emergency_contact.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'admin_emergency_contact.php' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-phone-volume mr-3"></i>Emergency Contacts</a></li>
        <li><a href="admins.php" class="flex items-center px-3 py-2 rounded-lg <?php echo $currentPage === 'admin-management.html' ? 'bg-[#0f2e24] text-white' : 'hover:bg-[#0f2e24] text-white'; ?> transition-all duration-200"><i class="fas fa-user-gear mr-3"></i>Admins</a></li>
      </ul>
    </nav>
  </aside>
  <!-- Main Content -->
  <div class="flex-1 flex flex-col">
    <!-- Top Navbar -->
    <header class="bg-[#0f2e24] shadow flex items-center justify-between px-6 py-4 sticky top-0 z-[9999]">
      <div class="flex items-center md:hidden">
        <button id="sidebarToggle" class="text-white focus:outline-none mr-2"><i class="fas fa-bars fa-lg"></i></button>
        <img src="logo.png" alt="Logo" class="h-8 w-8 mr-2">
        <span class="font-bold text-lg text-white">E–Shongskar</span>
      </div>
      <div class="flex-1 text-center md:text-left">
        <span class="font-semibold text-xl text-white">Admin Panel</span>
      </div>
      <div class="relative flex items-center space-x-4">
        <button id="profileDropdownBtn" class="flex items-center space-x-2 focus:outline-none">
          <span class="text-white font-medium"><?php echo htmlspecialchars($adminName); ?></span>
          <img src="<?php echo htmlspecialchars($adminProfile); ?>" alt="Profile" class="h-9 w-9 rounded-full border-2 border-gray-200">
          <i class="fas fa-chevron-down text-gray-200"></i>
        </button>
        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-2 z-50">
          <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
          <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
        </div>
        <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 bg-[#4CAF50] text-white font-semibold rounded-lg shadow hover:bg-green-700 transition-all duration-200">
          <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
      </div>
    </header>
    <!-- Page Content Start --> 