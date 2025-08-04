<?php
include 'database.php';

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT photo FROM report_issues WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['photo']) {
        header("Content-Type: image/jpeg");
        echo $result['photo'];
        exit;
    } else {
        echo "Image not found or doesn't exist.";
    }
} else {
    echo "No image ID specified.";
}
?>
