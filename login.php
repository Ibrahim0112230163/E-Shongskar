<?php
// Start PHP session and include database connection
include 'database.php';
session_start();

// Check if user is already logged in
if (isset($_SESSION['username'])) {
    header("Location: landingPageAfterLogin.php");
    exit();
}

// Handle form submission for both user and admin login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['admin_login'])) {
        // Admin login processing
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if ($username === 'admin' && $password === 'admin') {
            $_SESSION['admin'] = true;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid admin credentials";
        }
    } else {
        // Regular user login processing
        $username = $_POST['username'];
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_id'] = $user['id'];
                
                header("Location: landingPageAfterLogin.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Shongskar</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef7f6',
                            100: '#d5eae7',
                            200: '#abd4cd',
                            300: '#81bdb3',
                            400: '#56a69a',
                            500: '#0b493e', /* Main primary color */
                            600: '#094236',
                            700: '#07362d',
                            800: '#052b24',
                            900: '#021d18',
                            DEFAULT: '#0b493e',
                            light: '#094236',
                            lighter: '#abd4cd',
                            dark: '#021d18',
                        },
                        secondary: {
                            50: '#f0f9fa',
                            100: '#d0eff1',
                            200: '#a8dadc', /* accent color */
                            300: '#86c5c8',
                            400: '#5aacaf',
                            500: '#418e91',
                            600: '#377779',
                            700: '#2c6062',
                            800: '#224a4c',
                            900: '#182f30',
                            DEFAULT: '#a8dadc',
                            light: '#d0eff1',
                            dark: '#377779',
                        },
                        gradient: {
                            start: '#e0f7fa', /* Start color of background gradient */
                            end: '#c8e6c9',   /* End color of background gradient */
                        },
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui'],
                        'heading': ['Poppins', 'ui-sans-serif', 'system-ui'],
                    },
                    boxShadow: {
                        card: '0 10px 30px rgba(0, 0, 0, 0.1)',
                        'card-hover': '0 15px 35px rgba(0, 0, 0, 0.15)',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                    },
                }
            }
        }
    </script>
    <style>
        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 5s ease-in-out infinite;
        }
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        
        /* Scrollbar customization */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #0b493e;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #094236;
        }
        
        /* Background styles */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        html {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            height: 100%;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
        
        section {
            background: transparent !important;
        }
        
        /* Card styling for better contrast against gradient */
        .bg-white {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Subtle animated background pattern */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            opacity: 0.3;
        }
        
        .bg-pattern::before,
        .bg-pattern::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .bg-pattern::before {
            background: #0b493e;
            top: -200px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        
        .bg-pattern::after {
            background: #a8dadc;
            bottom: -200px;
            left: -100px;
            animation: float 10s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        /* Subtle decorative circles */
        .decorative-circle {
            position: fixed;
            border-radius: 50%;
            z-index: -1;
            opacity: 0.02;
        }
        
        /* Login panel styles */
        .login-panel {
            min-height: 480px; /* Set fixed min-height for consistency */
            width: 100%;
            max-width: 400px; /* Responsive on small screens */
        }
    </style>
</head>
<body class="font-sans text-gray-800">
    <!-- Subtle background pattern -->
    <div class="bg-pattern"></div>
    
    <!-- Very subtle decorative circles -->
    <div class="decorative-circle w-48 h-48 top-20 right-20 bg-primary-300 animate-float"></div>
    <div class="decorative-circle w-40 h-40 bottom-20 left-10 bg-secondary-200 animate-float" style="animation-delay: 1s;"></div>
    <div class="decorative-circle w-32 h-32 top-1/2 left-1/4 bg-primary-200 animate-float" style="animation-delay: 1.5s;"></div>
    
    <div class="min-h-screen flex items-center justify-center p-4 md:p-6 lg:p-8 relative">
        <!-- Header with beautiful animation -->
        
    
    <!-- Main Container -->
    <div id="loginContainer" class="w-full max-w-6xl bg-white/95 backdrop-blur-md rounded-2xl overflow-hidden shadow-card transition-all duration-500 flex flex-col lg:flex-row relative z-10 opacity-0 animate-fadeIn delay-100 mt-20 lg:mt-0">
        <!-- Left Section: Video/Image -->
        <div class="lg:w-5/12 bg-gradient-to-br from-primary-dark to-primary order-2 lg:order-1 transition-all duration-500">
            <div class="relative h-72 lg:h-full w-full overflow-hidden">
                <!-- Video -->
                <div class="absolute inset-0 z-0">
                    <video autoplay loop muted class="w-full h-full object-cover">
                        <source src="login_video.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-dark/70 to-transparent"></div>
                </div>
                
                <!-- Content -->
                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8 z-10">
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-white">E-Shongskar</h2>
                        </div>
                        <h3 class="text-2xl font-semibold text-white mb-3">Community Safety Platform</h3>
                        <p class="text-white/80 max-w-md">Connect with your neighborhood, contribute to community safety, and be part of something greater.</p>
                    </div>
                    
                    <div class="flex flex-wrap gap-3">
                        <div class="flex items-center gap-2 bg-white/10 rounded-full py-2 px-4 backdrop-blur-sm">
                            <div class="w-6 h-6 rounded-full bg-secondary flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
                                </svg>
                            </div>
                            <span class="text-white text-sm">Secure Community</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 rounded-full py-2 px-4 backdrop-blur-sm">
                            <div class="w-6 h-6 rounded-full bg-secondary flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
                                </svg>
                            </div>
                            <span class="text-white text-sm">Easy Reporting</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 rounded-full py-2 px-4 backdrop-blur-sm">
                            <div class="w-6 h-6 rounded-full bg-secondary flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
                                </svg>
                            </div>
                            <span class="text-white text-sm">24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Section: Login Form -->
        <div class="lg:w-7/12 py-10 px-8 md:p-12 lg:p-16 bg-white/95 order-1 lg:order-2 transition-all duration-500 flex items-center justify-center">
            <div class="flex flex-col items-center justify-center w-full">
                <!-- Tab Buttons -->
                <div class="flex mb-8 bg-gray-100 rounded-lg p-1 relative z-10">
                    <button type="button" id="userLoginBtn" class="tab-btn active py-2 px-6 rounded-lg text-primary-500 font-medium transition duration-300 focus:outline-none">
                        <i class="fas fa-user mr-2"></i> User Login
                    </button>
                    <button type="button" id="adminLoginBtn" class="tab-btn py-2 px-6 rounded-lg text-gray-500 font-medium transition duration-300 focus:outline-none">
                        <i class="fas fa-shield-alt mr-2"></i> Admin Login
                    </button>
                </div>
                
                <!-- User Login Form -->
                <div id="userForm" class="login-panel">
                    <div class="mb-8 space-y-4 text-center">
                        <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary-500">Welcome Back!</h1>
                        <p class="text-gray-600">Please login to access your account and connect with your community.</p>
                        <?php if (isset($error) && !isset($_POST['admin_login'])): ?>
                            <div class="bg-red-50 text-red-800 p-4 rounded-lg border border-red-100">
                                <div class="flex">
                                    <svg class="w-5 h-5 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span><?php echo htmlspecialchars($error); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                    <div class="space-y-2">
                        <label for="username" class="block font-medium text-gray-700">Username </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                </svg>
                            </div>
                            <input type="text" id="username" name="username" class="pl-11 w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Enter your username or email" required>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="password" class="block font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" class="pl-11 w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    
                    
                    <div class="space-y-4">
                        <button type="submit" class="w-full py-3 px-6 bg-primary hover:bg-primary-light text-white font-medium rounded-lg shadow-sm transition-all transform hover:-translate-y-0.5">
                            <div class="flex items-center justify-center">
                                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M14.243 5.757a6 6 0 10-.986 9.284 1 1 0 111.087 1.678A8 8 0 1118 10a1 1 0 00-1.757-.869 6 6 0 01-1.999-3.374z" clip-rule="evenodd" />
                                    <path fill-rule="evenodd" d="M14.5 10a4.5 4.5 0 10-9 0 4.5 4.5 0 009 0zm-2-1.5a1 1 0 00-1-1h-3a1 1 0 000 2h2v3a1 1 0 002 0v-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Sign In</span>
                            </div>
                        </button>
                        
                        <button type="button" id="registerBtn" class="w-full py-3 px-6 bg-white text-primary border-2 border-primary font-medium rounded-lg transition-all hover:bg-primary-50 transform hover:-translate-y-0.5">
                            <div class="flex items-center justify-center">
                                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                                </svg>
                                <span>New Here? Register Now</span>
                            </div>
                        </button>
                        
                        
                    </div>
                </form>
            </div>
            
            <!-- Admin Login Form (Removed duplicate) -->
            <div id="adminForm" class="login-panel hidden">
                <div class="mb-8 space-y-4 text-center">
                    <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary-500">Admin Access</h1>
                    <p class="text-gray-600">Secure admin login for authorized personnel only.</p>
                    <?php if (isset($error) && isset($_POST['admin_login'])): ?>
                        <div class="bg-red-50 text-red-800 p-4 rounded-lg border border-red-100">
                            <div class="flex">
                                <svg class="w-5 h-5 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                    <input type="hidden" name="admin_login" value="1">
                    <div class="space-y-2">
                        <label for="adminUsername" class="block font-medium text-gray-700">Admin ID</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="text" id="adminUsername" name="username" class="pl-11 w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Enter admin ID" required>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="adminPassword" class="block font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="password" id="adminPassword" name="password" class="pl-11 w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Enter admin password" required>
                        </div>
                    </div>
                    
                    
                    
                    <div class="space-y-4">
                        <button type="submit" class="w-full py-3 px-6 bg-primary hover:bg-primary-light text-white font-medium rounded-lg shadow-sm transition-all transform hover:-translate-y-0.5">
                            <div class="flex items-center justify-center">
                                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Login as Admin</span>
                            </div>
                        </button>
                        
                        <button type="button" id="userBtn" class="w-full py-3 px-6 bg-white text-primary border-2 border-primary font-medium rounded-lg transition-all hover:bg-primary-50 transform hover:-translate-y-0.5">
                            <div class="flex items-center justify-center">
                                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                                <span>User Login</span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </div>

    <!-- Decorative Element -->
    <div class="absolute bottom-4 md:bottom-8 left-0 right-0 flex justify-center text-xs text-gray-500 pointer-events-none">
        <p>© 2025 E-Shongskar - Community Safety Platform</p>
    </div>

    <script>
        // Register button click
        document.getElementById('registerBtn').addEventListener('click', function() {
            window.location.href = 'register.php';
        });
        
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userForm = document.getElementById('userForm');
            const adminForm = document.getElementById('adminForm');
            const userLoginBtn = document.getElementById('userLoginBtn');
            const adminLoginBtn = document.getElementById('adminLoginBtn');
            const userBtn = document.getElementById('userBtn');
            
            // Switch to user login
            userLoginBtn.addEventListener('click', function() {
                userForm.classList.remove('hidden');
                adminForm.classList.add('hidden');
                userLoginBtn.classList.add('active', 'bg-white', 'text-primary-500');
                userLoginBtn.classList.remove('text-gray-500');
                adminLoginBtn.classList.remove('active', 'bg-white', 'text-primary-500');
                adminLoginBtn.classList.add('text-gray-500');
            });
            
            // Switch to admin login
            adminLoginBtn.addEventListener('click', function() {
                adminForm.classList.remove('hidden');
                userForm.classList.add('hidden');
                adminLoginBtn.classList.add('active', 'bg-white', 'text-primary-500');
                adminLoginBtn.classList.remove('text-gray-500');
                userLoginBtn.classList.remove('active', 'bg-white', 'text-primary-500');
                userLoginBtn.classList.add('text-gray-500');
            });
            
            // Switch back to user login from admin form
            if (userBtn) {
                userBtn.addEventListener('click', function() {
                    userForm.classList.remove('hidden');
                    adminForm.classList.add('hidden');
                    userLoginBtn.classList.add('active', 'bg-white', 'text-primary-500');
                    userLoginBtn.classList.remove('text-gray-500');
                    adminLoginBtn.classList.remove('active', 'bg-white', 'text-primary-500');
                    adminLoginBtn.classList.add('text-gray-500');
                });
            }
            
            // Initialize
            userLoginBtn.classList.add('bg-white');
        });
    </script>
    </div> <!-- Close the min-h-screen flex container -->
</body>
</html>