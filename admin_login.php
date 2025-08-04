<?php

include 'database.php';

// Set cache control headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // if the username and password both same 'admin'
    if ($username === 'admin' && $password === 'admin') {
        
        $_SESSION['admin'] = true;
        header("Location: admin_dashboard.php");
        exit;
    }

    // Otherwise, check if the password is 'admin' and the username exists in the 'admin' table
    if ($password === 'admin') {
        // Prepare the query to check if the username exists in the admin table
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username");
        $stmt->execute([':username' => $username]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // If the username exists, log the user in
            $_SESSION['admin'] = true;
            header("Location: admin_dashboard.php");
            exit;
        } else {
            // Username does not exist in the admin table
            $error = "Invalid credentials!";
        }
    } else {
        // Incorrect password
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
       * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #2ecc71, #3498db);
            margin: 0;
            overflow: hidden;
        }

        .login-container {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px 35px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
        }

        .login-container h1 {
            text-align: center;
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .login-container .icon {
            font-size: 4rem;
            color: #ff5f6d;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-control {
            background-color: #f6f6f6;
            border: 1px solid #e2e2e2;
            border-radius: 10px;
            font-size: 1rem;
            padding: 12px;
            margin-bottom: 20px;
            transition: border-color 0.3s ease-in-out;
        }

        .form-control:focus {
            border-color: #ff5f6d;
            box-shadow: 0 0 5px rgba(255, 95, 109, 0.4);
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .btn-primary {
            background-color: #ff5f6d;
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.1rem;
            padding: 12px;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #e04d55;
        }

        .footer-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: #ff5f6d;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #e04d55;
        }
        .btn-primary {
            background-color:rgb(45, 68, 224);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.1rem;
            padding: 12px;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color:rgb(131, 151, 241);
        }
        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .login-container {
                padding: 30px 25px;
            }

            .login-container h1 {
                font-size: 1.7rem;
            }

            .btn-primary {
                font-size: 1rem;
            }

            .form-control {
                padding: 10px;
            }
        }
    </style>
</head>

<body style="background: linear-gradient(to right, #0f2e24, #6c757d);">

    <!-- Login Container -->
    <div class="login-container" id="loginContainer">
        <div class="icon">
           <img src="logo.png" alt="logo" height="120px">
        </div>
        <h1>Login to Admin Panel</h1>
        <?php if (isset($error)): ?>
            <p class="error" style="color: red; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="footer-links">
            <a href="login.php" style="color: rgb(102, 127, 195);;">Back to User Login</a>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    
    <script>
    // Prevent back button access after logout
    window.history.forward();
    
    // Clear browser history and prevent going back
    if (window.history && window.history.pushState) {
        window.history.pushState('forward', null, './admin_login.php');
        window.onpopstate = function () {
            window.history.pushState('forward', null, './admin_login.php');
        };
    }
    
    // Additional security: prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Clear any cached data
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>

</html>
