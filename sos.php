<?php
// authenticate.php
include 'database.php'; // Database connection
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SOS Button</title>
  <!-- Bootstrap CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #c3cfe2 0%,  #f5f7fa 100%);
      text-align: center;
      padding-top: 150px;
      font-family: Arial, sans-serif;
    }

    h5 {
      color: #00291d;
      font-weight: 600;
      margin-bottom: 40px;
    }

    .sos-button {
      width: 300px;
      height: 300px;
      background: radial-gradient(circle at center, #f4ef2b, #9a8700);
      border: 6px solid #a28011;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      font-weight: 500;
      margin: auto;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
      cursor: pointer;
      transition: transform 0.1s ease-in-out;
    }

    .sos-button:active {
      transform: scale(0.98);
    }

    #confirmation {
      margin-top: 30px;
      font-size: 16px;
      font-weight: 500;
      color: #0f5132;
      display: none;
    }
  </style>
</head>
<body>
    <?php include 'header.php'; ?>

  <h5>Don’t panic. Tap the button below to send a SOS to your favourite contacts</h5>

  <div class="sos-button" onclick="sendSOS()">
    Tap to send a SOS
  </div>

  <div id="confirmation">SOS sent successfully to your favourite contacts!</div>

  <script>
    function sendSOS() {
      document.getElementById('confirmation').style.display = 'block';
    }
  </script>

</body>
</html>
