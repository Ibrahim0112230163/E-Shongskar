<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $location = $_POST['location'];
    $street = $_POST['street'];
    $house_no = $_POST['house_no'];
    $contact_no = $_POST['contact_no'];
    $occupation = $_POST['occupation'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $nid_birth_certificate = $_POST['nid_birth_certificate'];
    $email = $_POST['email'];
    $blood_group = $_POST['blood_group'] ?? 'N/A';
    $division = $_POST['division'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Additional fields from page 2 (optional)
    $fathers_name = $_POST['fathers_name'] ?? null;
    $mothers_name = $_POST['mothers_name'] ?? null;
    $marital_status = $_POST['marital_status'] ?? null;
    $education_qualification = $_POST['education_qualification'] ?? null;
    
    // Handle profile picture upload
    $dp_path = null;
    if (isset($_FILES['dp']) && $_FILES['dp']['error'] === UPLOAD_ERR_OK) {
        $fileSize = $_FILES['dp']['size'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB limit
        
        // Check file size
        if ($fileSize > $maxFileSize) {
            die('<div class="alert alert-danger text-center" style="max-width: 700px; margin: 20px auto;">
                    Profile picture is too large. Maximum size allowed is 2MB. <a href="register.php" class="alert-link">Try again</a>
                </div>');
        }
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['dp']['type'];
        if (!in_array($fileType, $allowedTypes)) {
            die('<div class="alert alert-danger text-center" style="max-width: 700px; margin: 20px auto;">
                    Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed. <a href="register.php" class="alert-link">Try again</a>
                </div>');
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploads/profile_pictures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExtension = strtolower(pathinfo($_FILES['dp']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('profile_', true) . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['dp']['tmp_name'], $uploadPath)) {
            $dp_path = $uploadPath;
        } else {
            die('<div class="alert alert-danger text-center" style="max-width: 700px; margin: 20px auto;">
                    Failed to upload profile picture. <a href="register.php" class="alert-link">Try again</a>
                </div>');
        }
    }

    // Validate passwords match
    if ($password !== $confirm_password) {
        die("Passwords do not match. <a href='register.php'>Go back</a>");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Insert into database with additional fields
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (username, full_name, location, street, house_no, contact_no, occupation, dob, gender, nid_birth_certificate, email, blood_group, division, password, dp, fathers_name, mothers_name, marital_status, education_qualification) 
            VALUES 
            (:username, :full_name, :location, :street, :house_no, :contact_no, :occupation, :dob, :gender, :nid_birth_certificate, :email, :blood_group, :division, :password, :dp, :fathers_name, :mothers_name, :marital_status, :education_qualification)
        ");
        $stmt->execute([
            ':username' => $username,
            ':full_name' => $full_name,
            ':location' => $location,
            ':street' => $street,
            ':house_no' => $house_no,
            ':contact_no' => $contact_no,
            ':occupation' => $occupation,
            ':dob' => $dob,
            ':gender' => $gender,
            ':nid_birth_certificate' => $nid_birth_certificate,
            ':email' => $email,
            ':blood_group' => $blood_group,
            ':division' => $division,
            ':password' => $hashed_password,
            ':dp' => $dp_path,
            ':fathers_name' => $fathers_name,
            ':mothers_name' => $mothers_name,
            ':marital_status' => $marital_status,
            ':education_qualification' => $education_qualification
        ]);
        
        // Success message with better styling
        echo '<div class="alert alert-success text-center" style="max-width: 700px; margin: 20px auto;">
                Registration successful. <a href="login.php" class="alert-link">Login here</a>
              </div>';
    } catch (PDOException $e) {
        die('<div class="alert alert-danger text-center" style="max-width: 700px; margin: 20px auto;">
                Error: ' . $e->getMessage() . ' <a href="register.php" class="alert-link">Try again</a>
            </div>');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shongskar - Register</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                        },
                        gradient: {
                            start: '#e0f7fa', /* Start color of background gradient */
                            end: '#c8e6c9',   /* End color of background gradient */
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.01)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                },
            },
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
        
        /* Make inputs and selects consistent */
        input, select, textarea {
            appearance: none;
            -webkit-appearance: none;
        }
        
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230b493e'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5em;
            padding-right: 2.5rem;
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
        
        /* Multi-step form styles */
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .step-indicator .step-circle {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 1rem;
            transition: all 0.3s ease;
        }
        
        .step-indicator .step-circle.active {
            background-color: #0b493e;
            color: white;
        }
        
        .step-indicator .step-circle.completed {
            background-color: #10b981;
            color: white;
        }
        
        .step-indicator .step-circle:not(.active):not(.completed) {
            background-color: #e5e7eb;
            color: #6b7280;
        }
        
        .step-indicator .step-line {
            width: 4rem;
            height: 2px;
            background-color: #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .step-indicator .step-line.completed {
            background-color: #10b981;
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-nav {
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0b493e 0%, #094236 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(11, 73, 62, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-skip {
            background: transparent;
            color: #6b7280;
            text-decoration: underline;
        }
        
        .btn-skip:hover {
            color: #374151;
        }
    </style>
</head>
<body class="font-sans text-gray-800">
    <div class="container mx-auto px-4 py-10">
        <!-- Header with beautiful animation -->
        <div class="text-center mb-8 opacity-0 animate-fadeIn">
            <h1 class="text-4xl md:text-5xl font-bold text-primary-500 font-display mb-3">E-Shongskar</h1>
            <p class="text-lg text-primary-600/80 max-w-xl mx-auto">Join our community platform and be part of something amazing</p>
        </div>
        
        <!-- Main content container with glass morphism effect -->
        <div class="max-w-4xl mx-auto bg-white/90 backdrop-blur-md rounded-2xl shadow-card overflow-hidden mb-20 opacity-0 animate-fadeIn delay-100">
            <!-- Header banner -->
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 py-6 px-8">
                <h2 class="text-2xl font-bold text-white font-display flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> Create Your Account
                </h2>
                <p class="text-white/80 mt-1">Fill in the information below to get started</p>
            </div>
            
            <!-- Form section -->
            <div class="p-8">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step-circle active" id="step-1-indicator">1</div>
                    <div class="step-line" id="line-1"></div>
                    <div class="step-circle" id="step-2-indicator">2</div>
                </div>
                
                <form action="register.php" method="POST" enctype="multipart/form-data" id="registrationForm">
                    <!-- Step 1: Required Information -->
                    <div class="step active" id="step-1">
                        <!-- Personal Information Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-user text-primary-500"></i> Personal Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-at"></i>
                                        </span>
                                        <input type="text" id="username" name="username" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-user-circle"></i>
                                        </span>
                                        <input type="text" id="full_name" name="full_name" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" id="email" name="email" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact Number <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="text" id="contact_no" name="contact_no" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">Occupation <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-briefcase"></i>
                                        </span>
                                        <input type="text" id="occupation" name="occupation" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="nid_birth_certificate" class="block text-sm font-medium text-gray-700 mb-1">NID or Birth Certificate <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-id-card"></i>
                                        </span>
                                        <input type="text" id="nid_birth_certificate" name="nid_birth_certificate" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Details Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-address-card text-primary-500"></i> Personal Details
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="dob" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        <input type="date" id="dob" name="dob" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-venus-mars"></i>
                                        </span>
                                        <select id="gender" name="gender" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="blood_group" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-tint text-red-500"></i>
                                        </span>
                                        <select id="blood_group" name="blood_group" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors">
                                            <option value="N/A">N/A</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-primary-500"></i> Address Details
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="division" class="block text-sm font-medium text-gray-700 mb-1">Division <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-map"></i>
                                        </span>
                                        <select id="division" name="division" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                            <option value="">Select Division</option>
                                            <option value="Dhaka">Dhaka</option>
                                            <option value="Chattogram">Chattogram</option>
                                            <option value="Rajshahi">Rajshahi</option>
                                            <option value="Khulna">Khulna</option>
                                            <option value="Barishal">Barishal</option>
                                            <option value="Sylhet">Sylhet</option>
                                            <option value="Rangpur">Rangpur</option>
                                            <option value="Mymensingh">Mymensingh</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location/City <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-city"></i>
                                        </span>
                                        <input type="text" id="location" name="location" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="street" class="block text-sm font-medium text-gray-700 mb-1">Street <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-road"></i>
                                        </span>
                                        <input type="text" id="street" name="street" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="house_no" class="block text-sm font-medium text-gray-700 mb-1">House No. <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-home"></i>
                                        </span>
                                        <input type="text" id="house_no" name="house_no" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-lock text-primary-500"></i> Security
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-key"></i>
                                        </span>
                                        <input type="password" id="password" name="password" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters</p>
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-shield-alt"></i>
                                        </span>
                                        <input type="password" id="confirm_password" name="confirm_password" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" required>
                                    </div>
                                    <div id="password-error" class="hidden text-red-500 text-xs mt-1">Passwords do not match</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Agreement & Terms -->
                        <div class="my-6 bg-primary-50/50 p-4 rounded-lg border border-primary-100">
                            <div class="flex items-start">
                                <input type="checkbox" id="terms" class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" required>
                                <label for="terms" class="ml-2 block text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-primary-600 hover:underline">Terms of Service</a> and <a href="#" class="text-primary-600 hover:underline">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Navigation Buttons -->
                        <div class="navigation-buttons">
                            <div></div>
                            <div class="flex gap-3">
                                <button type="button" class="btn-nav btn-primary" onclick="nextStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                                <button type="button" class="btn-nav btn-skip" onclick="skipToSubmit()">
                                    Skip to Submit
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Additional Information (Optional) -->
                    <div class="step" id="step-2">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold text-primary-700 mb-2">Additional Information</h3>
                            <p class="text-gray-600">These fields are optional. You can skip them if you want.</p>
                        </div>
                        
                        <!-- Profile Picture Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-image text-primary-500"></i> Profile Picture
                            </h3>
                            
                            <div class="flex flex-col items-center space-y-4">
                                <div class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden" id="profile-preview">
                                    <i class="fas fa-user text-4xl text-gray-400"></i>
                                </div>
                                <div class="flex flex-col items-center">
                                    <label for="dp" class="btn-nav btn-secondary cursor-pointer">
                                        <i class="fas fa-upload"></i> Choose Photo
                                    </label>
                                    <input type="file" id="dp" name="dp" accept="image/*" class="hidden" onchange="previewImage(this)">
                                    <p class="text-xs text-gray-500 mt-2">Max size: 2MB. Formats: JPG, PNG, GIF, WebP</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Family Information Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-users text-primary-500"></i> Family Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="fathers_name" class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-male"></i>
                                        </span>
                                        <input type="text" id="fathers_name" name="fathers_name" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors">
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="mothers_name" class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-female"></i>
                                        </span>
                                        <input type="text" id="mothers_name" name="mothers_name" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Personal Details Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-primary-700 border-b border-gray-200 pb-2 mb-4 flex items-center gap-2">
                                <i class="fas fa-info-circle text-primary-500"></i> Additional Details
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="marital_status" class="block text-sm font-medium text-gray-700 mb-1">Marital Status</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-heart"></i>
                                        </span>
                                        <select id="marital_status" name="marital_status" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors">
                                            <option value="">Select Status</option>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Divorced">Divorced</option>
                                            <option value="Widowed">Widowed</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="education_qualification" class="block text-sm font-medium text-gray-700 mb-1">Education Qualification</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <i class="fas fa-graduation-cap"></i>
                                        </span>
                                        <input type="text" id="education_qualification" name="education_qualification" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition-colors" placeholder="e.g., Bachelor's in Computer Science">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Navigation Buttons -->
                        <div class="navigation-buttons">
                            <button type="button" class="btn-nav btn-secondary" onclick="prevStep()">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <div class="flex gap-3">
                                <button type="submit" class="btn-nav btn-primary">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                                <button type="button" class="btn-nav btn-skip" onclick="skipAndSubmit()">
                                    Skip & Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom validation script -->
    <script>
        let currentStep = 1;
        const totalSteps = 2;
        
        // Multi-step navigation functions
        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            // Show current step
            document.getElementById(`step-${step}`).classList.add('active');
            
            // Update step indicators
            updateStepIndicators(step);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function updateStepIndicators(step) {
            for (let i = 1; i <= totalSteps; i++) {
                const indicator = document.getElementById(`step-${i}-indicator`);
                const line = document.getElementById(`line-${i}`);
                
                if (i < step) {
                    indicator.classList.add('completed');
                    indicator.classList.remove('active');
                    if (line) line.classList.add('completed');
                } else if (i === step) {
                    indicator.classList.add('active');
                    indicator.classList.remove('completed');
                } else {
                    indicator.classList.remove('active', 'completed');
                    if (line) line.classList.remove('completed');
                }
            }
        }
        
        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }
        
        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }
        
        function skipToSubmit() {
            if (validateCurrentStep()) {
                document.getElementById('registrationForm').submit();
            }
        }
        
        function skipAndSubmit() {
            document.getElementById('registrationForm').submit();
        }
        
        function validateCurrentStep() {
            if (currentStep === 1) {
                // Validate required fields in step 1
                const requiredFields = [
                    'username', 'full_name', 'email', 'contact_no', 'occupation',
                    'nid_birth_certificate', 'dob', 'gender', 'division', 'location',
                    'street', 'house_no', 'password', 'confirm_password'
                ];
                
                let isValid = true;
                const firstInvalidField = null;
                
                for (const fieldId of requiredFields) {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-300');
                        field.classList.remove('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-300');
                        if (isValid) {
                            field.focus();
                            isValid = false;
                        }
                    } else {
                        field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-300');
                        field.classList.add('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-300');
                    }
                }
                
                // Validate password match
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                if (password !== confirmPassword) {
                    document.getElementById('password-error').classList.remove('hidden');
                    document.getElementById('confirm_password').classList.add('border-red-500');
                    if (isValid) {
                        document.getElementById('confirm_password').focus();
                        isValid = false;
                    }
                } else {
                    document.getElementById('password-error').classList.add('hidden');
                    document.getElementById('confirm_password').classList.remove('border-red-500');
                }
                
                // Check terms checkbox
                const termsCheckbox = document.getElementById('terms');
                if (!termsCheckbox.checked) {
                    termsCheckbox.classList.add('ring-2', 'ring-red-500');
                    if (isValid) {
                        termsCheckbox.focus();
                        isValid = false;
                    }
                } else {
                    termsCheckbox.classList.remove('ring-2', 'ring-red-500');
                }
                
                return isValid;
            }
            return true; // Step 2 fields are optional
        }
        
        // Profile picture preview function
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB in bytes
                
                // Check file size
                if (file.size > maxSize) {
                    alert('File is too large! Maximum size allowed is 2MB.');
                    input.value = ''; // Clear the input
                    document.getElementById('profile-preview').innerHTML = '<i class="fas fa-user text-4xl text-gray-400"></i>';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type! Only JPG, PNG, GIF, and WebP images are allowed.');
                    input.value = ''; // Clear the input
                    document.getElementById('profile-preview').innerHTML = '<i class="fas fa-user text-4xl text-gray-400"></i>';
                    return;
                }
                
                const reader = new FileReader();
                const preview = document.getElementById('profile-preview');
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover rounded-full" alt="Profile Preview">`;
                }
                
                reader.readAsDataURL(file);
            }
        }

        // Show success alert with animation if exists
        document.addEventListener('DOMContentLoaded', function() {
            const alertSuccess = document.querySelector('.alert-success');
            if (alertSuccess) {
                alertSuccess.classList.add('animate-fadeIn');
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 300);
            }
        });

        // Password validation
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const passwordError = document.getElementById('password-error');
        
        // Real-time password validation
        confirmPasswordField.addEventListener('input', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                passwordError.classList.remove('hidden');
                confirmPasswordField.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-300');
                confirmPasswordField.classList.remove('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-300');
            } else {
                passwordError.classList.add('hidden');
                confirmPasswordField.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-300');
                confirmPasswordField.classList.add('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-300');
            }
        });
        
        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (currentStep === 1) {
                e.preventDefault();
                if (validateCurrentStep()) {
                    nextStep();
                }
                return;
            }
            
            // Final validation for step 2 (if on step 2)
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                passwordError.classList.remove('hidden');
                confirmPasswordField.classList.add('border-red-500');
                confirmPasswordField.focus();
                return;
            }
            
            // Check if terms checkbox is checked
            const termsCheckbox = document.getElementById('terms');
            if (!termsCheckbox.checked) {
                e.preventDefault();
                termsCheckbox.classList.add('ring-2', 'ring-red-500');
                // Go back to step 1 to show terms
                currentStep = 1;
                showStep(currentStep);
                const termsSection = termsCheckbox.closest('div');
                setTimeout(() => {
                    termsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
                return;
            }
        });
        
        // Field highlighting on focus
        const formInputs = document.querySelectorAll('input, select');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('div.relative').classList.add('ring-2', 'ring-primary-100', 'rounded-lg');
            });
            
            input.addEventListener('blur', function() {
                this.closest('div.relative').classList.remove('ring-2', 'ring-primary-100', 'rounded-lg');
            });
        });
    </script>
</body>
</html>