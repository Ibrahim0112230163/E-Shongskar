<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM saved WHERE username = :username ORDER BY created_at DESC");
$stmt->execute(['username' => $_SESSION['username']]);
$savedReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle delete
if (isset($_POST['delete_saved']) && isset($_POST['saved_id'])) {
    $stmt = $pdo->prepare("DELETE FROM saved WHERE id = :id AND username = :username");
    $stmt->execute(['id' => (int)$_POST['saved_id'], 'username' => $_SESSION['username']]);
    header('Location: savd.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; padding: 40px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 32px rgba(60, 72, 100, 0.10); padding: 32px; }
        h1 { font-size: 2rem; font-weight: 700; color: #0f2e24; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f4f6fb; color: #3730a3; font-weight: 700; }
        tr:last-child td { border-bottom: none; }
        .delete-btn { color: #ef4444; background: none; border: none; font-size: 1.1rem; cursor: pointer; }
        .delete-btn:hover { color: #b91c1c; }
    </style>
</head>
<body>
<div class="container">
    <h1>Saved Reports</h1>
    <?php if (empty($savedReports)): ?>
        <p>No saved reports found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savedReports as $saved): $data = json_decode($saved['saves'], true); ?>
                <tr>
                    <td><?= htmlspecialchars($data['id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($data['issue_title'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($data['location'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($data['created_at'] ?? '-') ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="saved_id" value="<?= $saved['id'] ?>">
                            <button type="submit" name="delete_saved" class="delete-btn" title="Delete">&times;</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html> 