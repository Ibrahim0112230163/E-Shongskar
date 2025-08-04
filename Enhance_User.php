<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

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

$stmt = $pdo->prepare("SELECT username, location FROM users WHERE username = :username");
$stmt->execute([':username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profilePicture = null;

    // (profile picture)
    if (!empty($_FILES['profile_picture']['name'])) {
        $imageData = file_get_contents($_FILES['profile_picture']['tmp_name']);

        $stmt = $pdo->prepare("UPDATE enhanced_profiles SET profile_picture = :profile_picture WHERE username = :username");
        $stmt->execute([':profile_picture' => $imageData, ':username' => $user['username']]);
    }

    // Handle social media links
    if (!empty($_POST['social_links']) && is_array($_POST['social_links'])) {
        foreach ($_POST['social_links'] as $platform => $link) {
            if (!empty($link)) {
                $stmt = $pdo->prepare("
                    INSERT INTO social_media (username, platform, link) 
                    VALUES (:username, :platform, :link)
                    ON DUPLICATE KEY UPDATE link = :link
                ");
                $stmt->execute([
                    ':username' => $user['username'],
                    ':platform' => $platform,
                    ':link' => $link
                ]);
            }
        }
    }

    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhance Profile</title>
</head>
<body>
    <h1>Enhance Profile</h1>
    <form method="POST" enctype="multipart/form-data">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($user['location']); ?></p>

        <!-- Profile Picture -->
        <label for="profile_picture">Profile Picture:</label>
        <input type="file" name="profile_picture" id="profile_picture" accept="image/*">

        <!-- Social Media Links -->
        <div id="social_links_container">
            <label for="social_media">Social Media:</label>
            <select id="social_media" onchange="addSocialMediaField()">
                <option value="">Select a Platform</option>
                <option value="facebook">Facebook</option>
                <option value="linkedin">LinkedIn</option>
                <option value="instagram">Instagram</option>
                <option value="x">X (Twitter)</option>
            </select>
        </div>

        <div id="social_links_inputs"></div>

        <button type="submit">Save</button>
    </form>

    <script>
        function addSocialMediaField() {
            const platform = document.getElementById('social_media').value;
            if (platform) {
                const container = document.getElementById('social_links_inputs');
                if (!document.getElementById(`${platform}_link`)) {
                    const div = document.createElement('div');
                    div.innerHTML = `
                        <label for="${platform}_link">${platform} Link:</label>
                        <input type="url" id="${platform}_link" name="social_links[${platform}]" placeholder="Enter ${platform} link">
                    `;
                    container.appendChild(div);
                }
            }
        }
    </script>
</body>
</html>
