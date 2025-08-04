<?php
include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$location = $user['location'];
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get location-specific contacts (filtered if search is active)
$contactSQL = "SELECT * FROM emergency_contacts WHERE address = :location";
if ($searchQuery) {
    $contactSQL .= " AND (address LIKE :search OR location LIKE :search OR name LIKE :search OR contact LIKE :search)";
}
$contactStmt = $pdo->prepare($contactSQL);
$params = ['location' => $location];
if ($searchQuery) {
    $params['search'] = "%$searchQuery%";
}
$contactStmt->execute($params);
$emergencyContacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all contacts (filtered if search is active)
$allContactSQL = "SELECT * FROM emergency_contacts";
if ($searchQuery) {
    $allContactSQL .= " WHERE address LIKE :search OR location LIKE :search OR name LIKE :search OR contact LIKE :search";
}
$allContactStmt = $pdo->prepare($allContactSQL);
if ($searchQuery) {
    $allContactStmt->execute(['search' => "%$searchQuery%"]);
} else {
    $allContactStmt->execute();
}
$allEmergencyContacts = $allContactStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize contacts by category
$contactsByCategory = [];
foreach ($allEmergencyContacts as $contact) {
    $category = $contact['category'] ?? 'General';
    $contactsByCategory[$category][] = $contact;
}

$localContactsByCategory = [];
foreach ($emergencyContacts as $contact) {
    $category = $contact['category'] ?? 'General';
    $localContactsByCategory[$category][] = $contact;
}

// Function to split array into chunks of 2 for two-column layout
function chunkCategories($categories) {
    $result = [];
    $temp = [];
    foreach ($categories as $category => $contacts) {
        $temp[$category] = $contacts;
        if (count($temp) == 2) {
            $result[] = $temp;
            $temp = [];
        }
    }
    if (!empty($temp)) {
        $result[] = $temp;
    }
    return $result;
}

$localChunks = chunkCategories($localContactsByCategory);
$allChunks = chunkCategories($contactsByCategory);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emergency Contacts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #c3cfe2 0%,  #f5f7fa 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .section-title {
      font-weight: bold;
      font-size: 1.5rem;
      color: #333;
      margin: 40px 0 20px;
      text-align: center;
    }
    .card {
      border: 2px solid #b9f3fc;
      background-color: #f8fdff;
      border-radius: 12px;
      margin-bottom: 20px;
      height: 100%;
      width: 200%;
      margin-left: auto;
      margin-right: auto;
    }
  
    .contact-entry {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-top: 1px solid #e9ecef;
      flex-direction: column;
      align-items: flex-start;
    }
    .contact-entry span {
      align-self: flex-end;
    }
    .contact-entry:first-child {
      border-top: none;
    }
    .copy-btn {
      background-color: #caf0f8;
      border: 1px solid #90e0ef;
      padding: 5px 10px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .search-box {
      text-align: center;
      padding: 20px;
    }
    .search-box input {
      border-radius: 20px;
      border: 1px solid #ced4da;
      padding: 8px 20px;
      width: 60%;
      max-width: 500px;
    }
    .text-muted {
      font-size: 1rem;
      font-weight: 400;
    }
    .back-button {
      display: inline-block;
      padding: 10px 15px;
      background-color: rgb(157, 165, 161);
      color: white;
      text-decoration: none;
      border-radius: 5px;
      margin: 20px;
    }
    .back-button:hover {
      background-color: #27ae60;
      color: white;
    }
    .back-button i {
      margin-right: 5px;
    }
    .two-column-row {
      display: flex;
      flex-wrap: wrap;
      margin: 0 auto;
      max-width: 1200px;
    }
    .two-column-col {
      flex: 0 0 50%;
      max-width: 50%;
      padding: 0 15px;
      margin-bottom: 20px;
    }
    .section-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }
    @media (max-width: 768px) {
      .two-column-col {
        flex: 0 0 100%;
        max-width: 100%;
      }
      .card {
        width: 95%;
      }
      .search-box input {
        width: 80%;
      }
    }
  </style>
</head>
<body>

<nav>
    <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
</nav>
<?php include 'header.php'; ?>

<div class="container">
  <div class="search-box">
    <form method="GET" action="">
      <input type="text" id="searchInput" name="search" placeholder="Search Emergency Services" 
             value="<?php echo htmlspecialchars($searchQuery); ?>">
      <button type="submit" style="display:none"></button>
    </form>
  </div>

  <div class="section-container">
    <h2 class="section-title">Emergency contact numbers in <?php echo htmlspecialchars($location); ?></h2>
    <?php if (!empty($localChunks)): ?>
      <?php foreach ($localChunks as $chunk): ?>
        <div class="two-column-row">
          <?php foreach ($chunk as $category => $contacts): ?>
            <div class="two-column-col">
              <div class="card p-3">
                
                <?php foreach ($contacts as $contact): ?>
                  <div class="contact-entry">
                    <?php echo htmlspecialchars($contact['name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($contact['contact']); ?></small>
                    <span><button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($contact['contact']); ?>')">Copy number</button></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="text-center">No emergency contacts found <?php echo $searchQuery ? 'matching your search' : 'for your location'; ?>.</div>
    <?php endif; ?>
  </div>

  <div class="section-container">
    <h2 class="section-title">General Emergency Contacts (All Locations)</h2>
    <?php if (!empty($allChunks)): ?>
      <?php foreach ($allChunks as $chunk): ?>
        <div class="two-column-row">
          <?php foreach ($chunk as $category => $contacts): ?>
            <div class="two-column-col">
              <div class="card p-3">
                
                <?php foreach ($contacts as $contact): ?>
                  <div class="contact-entry">
                    <?php echo htmlspecialchars($contact['name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($contact['contact']); ?></small>
                    <span><button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($contact['contact']); ?>')">Copy number</button></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="text-center">No emergency contacts found <?php echo $searchQuery ? 'matching your search' : 'available'; ?>.</div>
    <?php endif; ?>
  </div>
</div>

<script>
  function copyText(text) {
    navigator.clipboard.writeText(text);
    alert("Copied: " + text);
  }

  // Auto-submit form when user stops typing (after 500ms delay)
  let searchTimer;
  document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
      document.querySelector('form').submit();
    }, 500);
  });
</script>

</body>
</html>