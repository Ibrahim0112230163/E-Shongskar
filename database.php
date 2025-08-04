<?php
// Database connection settings
$host = 'localhost';  // Your host (e.g., 'localhost' or IP address)
$dbname = 'user_system'; // The database you want to connect to
$username = 'root';     // Your MySQL username
$password = '';         // Your MySQL password (empty string if no password)

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optionally, you can set the character encoding for the connection (e.g., UTF-8)
    $pdo->exec("SET NAMES utf8");

    // Uncomment this line if you want a successful connection message (for debugging purposes)
    // echo "Connection successful";  // This will confirm a successful connection

} catch (PDOException $e) {
    // If connection fails, display a detailed error message
    echo "Connection failed: " . $e->getMessage();
    die();  // Stop script execution to prevent further issues
}
?>
