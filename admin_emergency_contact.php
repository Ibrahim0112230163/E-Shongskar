<?php 
include 'database.php';

// Create table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS emergency_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        contact VARCHAR(50) NOT NULL,
        location VARCHAR(255) NOT NULL
    )");
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error creating table: " . $e->getMessage() . "</div>";
}

// Handle form submission for adding contact
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['address'], $_POST['contact'], $_POST['location'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $location = $_POST['location'];

    if ($location === 'Others' && isset($_POST['otherLocation'])) {
        $location = $_POST['otherLocation'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO emergency_contacts (name, address, contact, location) VALUES (:name, :address, :contact, :location)");
        $stmt->execute([
            ':name' => $name,
            ':address' => $address,
            ':contact' => $contact,
            ':location' => $location
        ]);
        $success = "Contact added successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM emergency_contacts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: ".$_SERVER['PHP_SELF']); // Refresh page silently
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting contact: " . $e->getMessage();
    }
}

// Fetch all contacts
try {
    $stmt = $pdo->query("SELECT * FROM emergency_contacts ORDER BY location ASC, name ASC");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Group contacts by location
    $contactsByLocation = [];
    foreach ($contacts as $contact) {
        $loc = $contact['location'];
        if (!isset($contactsByLocation[$loc])) $contactsByLocation[$loc] = [];
        $contactsByLocation[$loc][] = $contact;
    }
    ksort($contactsByLocation, SORT_NATURAL | SORT_FLAG_CASE); // Sort locations alphabetically
    // Sort contacts within each location by address
    foreach ($contactsByLocation as &$locContacts) {
        usort($locContacts, function($a, $b) {
            return strcasecmp($a['address'], $b['address']);
        });
    }
    unset($locContacts); // break reference
} catch (PDOException $e) {
    $error = "Error fetching contacts: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contacts | Admin Panel</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#e6f4ef] to-[#cfe3db] min-h-screen text-[#1a1a1a]">
<?php include 'admin_header.php'; ?>
<main class="flex-1 p-6 md:p-10">
  <div class="mb-10">
    <h1 class="text-3xl font-bold text-[#0f2e24] flex items-center mb-2"><i class="fas fa-phone-alt mr-3"></i>Emergency Contacts</h1>
    <p class="text-gray-500">Manage emergency contact information for your organization.</p>
  </div>
  <!-- Alerts -->
  <?php if (isset($success) && !isset($_POST['delete_id'])): ?>
    <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-800 flex items-center gap-2">
      <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
  <?php endif; ?>
  <?php if (isset($error)): ?>
    <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-800 flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
  <?php endif; ?>
  <!-- Tabs -->
  <div class="bg-white/90 rounded-xl shadow-lg p-6">
    <div class="flex gap-4 border-b mb-6">
      <button id="tab-view" class="py-2 px-4 font-semibold text-[#0f2e24] border-b-2 border-[#0f2e24] focus:outline-none" onclick="showTab('view')"><i class="fas fa-list mr-2"></i>View Contacts</button>
      <button id="tab-add" class="py-2 px-4 font-semibold text-gray-500 hover:text-[#0f2e24] border-b-2 border-transparent focus:outline-none" onclick="showTab('add')"><i class="fas fa-plus-circle mr-2"></i>Add Contact</button>
    </div>
    <!-- View Contacts Tab -->
    <div id="tab-content-view">
      <?php if (empty($contacts)): ?>
        <div class="text-center py-12 text-gray-400">
          <i class="fas fa-address-book text-4xl mb-2"></i>
          <h4 class="text-lg font-bold mb-2">No Contacts Found</h4>
          <p class="mb-4">Add your first emergency contact to get started</p>
          <button onclick="showTab('add')" class="inline-flex items-center px-5 py-2 bg-gradient-to-tr from-[#0f2e24] to-purple-500 text-white font-semibold rounded-lg shadow hover:from-[#1a4a3a] hover:to-purple-600 transition-all duration-200"><i class="fas fa-plus mr-2"></i>Add Contact</button>
        </div>
      <?php else: ?>
        <?php foreach ($contactsByLocation as $location => $locContacts): ?>
        <div class="mb-10">
          <h2 class="text-xl font-bold text-[#0f2e24] mb-4 flex items-center"><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($location); ?></h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-tr from-[#0f2e24]/20 via-purple-100 to-pink-100">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Name</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Address</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contact</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <?php foreach ($locContacts as $contact): ?>
                <tr class="hover:bg-green-50 transition">
                  <td class="px-4 py-2 font-semibold text-[#0f2e24]"><?php echo htmlspecialchars($contact['name']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($contact['address']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($contact['contact']); ?></td>
                  <td class="px-4 py-2">
                    <form method="POST" action="" class="inline">
                      <input type="hidden" name="delete_id" value="<?php echo $contact['id']; ?>">
                      <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-bold shadow transition"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <!-- Add Contact Tab -->
    <div id="tab-content-add" class="hidden">
      <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="name" class="block text-xs font-semibold text-gray-600 mb-1">Name</label>
            <input type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0f2e24] focus:outline-none" id="name" name="name" required>
          </div>
          <div>
            <label for="contact" class="block text-xs font-semibold text-gray-600 mb-1">Contact Number</label>
            <input type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0f2e24] focus:outline-none" id="contact" name="contact" required>
          </div>
        </div>
        <div class="mb-4">
          <label for="address" class="block text-xs font-semibold text-gray-600 mb-1">Address</label>
          <textarea class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0f2e24] focus:outline-none" id="address" name="address" rows="3" required></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="location" class="block text-xs font-semibold text-gray-600 mb-1">Location</label>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0f2e24] focus:outline-none" id="location" name="location" required>
              <option value="Dhaka">Dhaka</option>
              <option value="Rajshahi">Rajshahi</option>
              <option value="Khulna">Khulna</option>
              <option value="Others">Others</option>
            </select>
          </div>
          <div id="otherLocationContainer" class="hidden">
            <label for="otherLocation" class="block text-xs font-semibold text-gray-600 mb-1">Other Location</label>
            <input type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0f2e24] focus:outline-none" id="otherLocation" name="otherLocation">
          </div>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="inline-flex items-center px-5 py-2 bg-gradient-to-tr from-green-500 to-[#1a4a3a] text-white font-semibold rounded-lg shadow hover:from-green-600 hover:to-[#0f2e24] transition-all duration-200">
            <i class="fas fa-save mr-2"></i>Save Contact
          </button>
        </div>
      </form>
    </div>
  </div>
</main>
<script>
  function showTab(tab) {
    document.getElementById('tab-content-view').classList.toggle('hidden', tab !== 'view');
    document.getElementById('tab-content-add').classList.toggle('hidden', tab !== 'add');
    document.getElementById('tab-view').classList.toggle('text-[#0f2e24]', tab === 'view');
    document.getElementById('tab-view').classList.toggle('border-[#0f2e24]', tab === 'view');
    document.getElementById('tab-add').classList.toggle('text-[#0f2e24]', tab === 'add');
    document.getElementById('tab-add').classList.toggle('border-[#0f2e24]', tab === 'add');
  }
  // Show/hide other location field
  document.getElementById('location').addEventListener('change', function() {
    const otherContainer = document.getElementById('otherLocationContainer');
    otherContainer.classList.toggle('hidden', this.value !== 'Others');
    document.getElementById('otherLocation').required = this.value === 'Others';
  });
  // Set default tab
  showTab('view');
</script>
</body>
</html>
