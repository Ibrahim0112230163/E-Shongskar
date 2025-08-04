<?php
include 'database.php';
session_start();

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Fetch user data to auto-fill fields
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("No data found.");
}

// Handle the report submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['description']) && !empty($_POST['issue_title']) && !empty($_POST['issue_type'])) {
        $description = trim($_POST['description']);
        $issue_title = trim($_POST['issue_title']);
        $issue_type = $_POST['issue_type'];
        $name = $user['full_name'];
        $contact = $user['contact_no'];
        $location = $user['location'];
        $photo = null;

        // Handle photo upload
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        }

        // Insert the report into the database
        $stmt = $pdo->prepare("
            INSERT INTO report_issues (username, name, description, contact, location, issue_type, issue_title, photo) 
            VALUES (:username, :name, :description, :contact, :location, :issue_type, :issue_title, :photo)
        ");
        $stmt->execute([
            'username' => $_SESSION['username'],
            'name' => $name,
            'description' => $description,
            'contact' => $contact,
            'location' => $location,
            'issue_type' => $issue_type,
            'issue_title' => $issue_title,
            'photo' => $photo
        ]);

        // Redirect with success message
        $_SESSION['report_success'] = true;
        header('Location: Notification_report_issue.php');
        exit;
    } else {
        $error = "Please fill out all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue | E-Shongskar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        sans: ['Segoe UI', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.01)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    },
                    maxWidth: {
                        container: '70%',
                    }
                },
            },
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            padding-top: 140px; /* Adjust this value based on your header height */
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
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
        
        /* Make sure all sections are transparent to show the background */
        section {
            background: transparent !important;
        }
        
        /* Animated background pattern with circles */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .bg-pattern::before,
        .bg-pattern::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            opacity: 0.05;
        }
        
        .bg-pattern::before {
            background: #0b493e; /* primary color */
            top: -300px;
            right: -100px;
        }
        
        .bg-pattern::after {
            background: #a8dadc; /* accent color */
            bottom: -200px;
            left: -150px;
        }
        
        /* Additional decorative circles */
        .decorative-circle-1 {
            position: fixed;
            top: 20%;
            right: 10%;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(168, 218, 220, 0.05);
            z-index: -1;
            animation: float 8s ease-in-out infinite;
        }
        
        .decorative-circle-2 {
            position: fixed;
            bottom: 15%;
            left: 5%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.04);
            z-index: -1;
            animation: float 9s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        .decorative-circle-3 {
            position: fixed;
            top: 40%;
            left: 25%;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(200, 230, 201, 0.1);
            z-index: -1;
            animation: float 7s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        .decorative-circle-4 {
            position: fixed;
            bottom: 8%;
            right: 15%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(11, 73, 62, 0.03);
            z-index: -1;
            animation: float 8s ease-in-out infinite;
            animation-delay: 0.5s;
        }
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        
        /* Form field animations */
        .form-field {
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.3s; }
        .delay-3 { animation-delay: 0.4s; }
        .delay-4 { animation-delay: 0.5s; }
        .delay-5 { animation-delay: 0.6s; }
        .delay-6 { animation-delay: 0.7s; }
        .delay-7 { animation-delay: 0.8s; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%); background-attachment: fixed; min-height: 100vh;" class="text-gray-800 font-sans">
    <!-- Background pattern with circles -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>
    <div class="decorative-circle-4"></div>
    
    <!-- Add header -->
    <?php include 'header.php'; ?>
    
    <!-- Hero Section with Glass Morphism -->
    <section class="text-primary-800 relative overflow-hidden" style="margin-top: -70px; background: transparent;">
        <!-- Abstract shapes background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-50 rounded-full opacity-30 animate-pulse"></div>
            <div class="absolute top-40 -left-20 w-72 h-72 bg-secondary-100 rounded-full opacity-20" style="animation: float 8s ease-in-out infinite;"></div>
            <div class="absolute -bottom-32 right-20 w-80 h-80 bg-primary-100 rounded-full opacity-20" style="animation: float 10s ease-in-out infinite; animation-delay: 1s;"></div>
            <div class="absolute top-60 right-1/4 w-40 h-40 bg-secondary-200 rounded-full opacity-10" style="animation: float 7s ease-in-out infinite; animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/3 w-60 h-60 bg-primary-200 rounded-full opacity-15" style="animation: float 9s ease-in-out infinite; animation-delay: 0.5s;"></div>
        </div>
        
        <!-- Content -->
        <div class="container mx-auto max-w-container px-4 py-16 relative z-10">
            <div class="flex flex-col items-center">
                <!-- Back Button -->
                <div class="self-start mb-6 opacity-0 animate-fadeIn">
                    <a href="Notification_report_issue.php" class="inline-flex items-center px-4 py-2 bg-secondary hover:bg-secondary-400 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-300 hover:shadow-md">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Reports
                    </a>
                </div>

                <h1 class="text-4xl lg:text-5xl font-bold mb-4 text-center opacity-0 animate-fadeIn">Report an Issue</h1>
                <p class="text-lg text-primary-800/80 mb-8 max-w-2xl text-center opacity-0 animate-fadeIn delay-100">Share your concerns with the community to help improve our surroundings.</p>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <div class="container mx-auto max-w-container px-4 pb-16 relative z-10">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-card hover:shadow-card-hover transition-all duration-300 overflow-hidden opacity-0 animate-fadeIn delay-200">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-red-700"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
                
                <form action="" method="post" enctype="multipart/form-data" class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="form-field delay-1">
                            <label for="name" class="block text-sm font-medium text-primary-700 mb-2">Full Name</label>
                            <input type="text" id="name" name="name" 
                                class="bg-primary-50/50 border border-primary-200 text-primary-800 rounded-lg block w-full p-3 cursor-not-allowed"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                        </div>
                        
                        <!-- Contact -->
                        <div class="form-field delay-2">
                            <label for="contact" class="block text-sm font-medium text-primary-700 mb-2">Contact Number</label>
                            <input type="text" id="contact" name="contact" 
                                class="bg-primary-50/50 border border-primary-200 text-primary-800 rounded-lg block w-full p-3 cursor-not-allowed"
                                value="<?php echo htmlspecialchars($user['contact_no']); ?>" readonly>
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="mt-6 form-field delay-3">
                        <label for="location" class="block text-sm font-medium text-primary-700 mb-2">Location</label>
                        <input type="text" id="location" name="location" 
                            class="bg-primary-50/50 border border-primary-200 text-primary-800 rounded-lg block w-full p-3 cursor-not-allowed"
                            value="<?php echo htmlspecialchars($user['location']); ?>" readonly>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <!-- Issue Title -->
                        <div class="form-field delay-4">
                            <label for="issue_title" class="block text-sm font-medium text-primary-700 mb-2">Issue Title</label>
                            <input type="text" id="issue_title" name="issue_title" 
                                class="bg-white/80 border border-primary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 text-primary-800 rounded-lg block w-full p-3 transition-all duration-200"
                                placeholder="Enter a concise title for the issue..." required>
                        </div>
                        
                        <!-- Issue Type -->
                        <div class="form-field delay-5">
                            <label for="issue_type" class="block text-sm font-medium text-primary-700 mb-2">Issue Type</label>
                            <select id="issue_type" name="issue_type" 
                                class="bg-white/80 border border-primary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 text-primary-800 rounded-lg block w-full p-3 transition-all duration-200"
                                required>
                                <option value="">-- Select Issue Type --</option>
                                <option value="Damaged Roads">Damaged Roads</option>
                                <option value="Infrastructure Issues">Infrastructure Issues</option>
                                <option value="Hygiene Problems">Hygiene Problems</option>
                                <option value="Corruption">Corruption</option>
                                <option value="Flooding Challenges">Flooding Challenges</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mt-6 form-field delay-6">
                        <label for="description" class="block text-sm font-medium text-primary-700 mb-2">Description</label>
                        <textarea id="description" name="description" 
                            class="bg-white/80 border border-primary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 text-primary-800 rounded-lg block w-full p-3 transition-all duration-200 min-h-[150px]"
                            placeholder="Please provide detailed information about the issue..." required></textarea>
                    </div>
                    
                    <!-- Photo -->
                    <div class="mt-6 form-field delay-7">
                        <label class="block text-sm font-medium text-primary-700 mb-2">Attach a picture/screenshot</label>
                        <div id="fileUploadArea" class="border-2 border-dashed border-primary-200 rounded-lg p-6 text-center cursor-pointer hover:bg-primary-50/50 transition-all duration-300">
                            <i class="fas fa-cloud-upload-alt text-primary-500 text-2xl mb-3"></i>
                            <p class="text-primary-800 mb-1">Drag & drop files here or click to browse</p>
                            <p class="text-xs text-primary-600">Supports: JPG, PNG (Max 5MB)</p>
                            <input type="file" id="fileInput" name="photo" class="hidden" accept="image/*">
                        </div>
                        <div id="filePreview" class="hidden mt-3">
                            <div class="flex items-center bg-primary-50 rounded-lg p-2">
                                <i class="fas fa-file-image text-primary-500 mr-2"></i>
                                <span id="fileName" class="text-primary-800 flex-grow"></span>
                                <button type="button" id="removeFile" class="text-primary-500 hover:text-primary-700 transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
                        <a href="Notification_report_issue.php" class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-primary-700 font-medium rounded-full shadow-md transition-all duration-300 transform hover:scale-105 hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-300">
                            <i class="fas fa-arrow-left mr-2"></i> 
                            <span>Back to Reports</span>
                        </a>
                        
                        <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-8 rounded-full shadow-md transition-all duration-300 transform hover:scale-105 hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-400 relative overflow-hidden">
                            <span class="relative z-10 flex items-center justify-center">
                                <i class="fas fa-paper-plane mr-2"></i> 
                                <span>Submit Report</span>
                            </span>
                            <div class="absolute inset-0 h-full w-full bg-white/20 transform -translate-x-full transition-transform duration-300 ease-out group-hover:translate-x-0"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('fileInput');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const removeFile = document.getElementById('removeFile');
            
            // Handle file upload click
            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Handle file selection
            fileInput.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size exceeds 5MB limit');
                        return;
                    }
                    
                    fileName.textContent = file.name;
                    filePreview.classList.remove('hidden');
                    filePreview.classList.add('block');
                    fileUploadArea.classList.add('hidden');
                }
            });
            
            // Handle drag and drop
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('border-primary-500', 'bg-primary-50/50');
            });
            
            fileUploadArea.addEventListener('dragleave', function() {
                this.classList.remove('border-primary-500', 'bg-primary-50/50');
            });
            
            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-primary-500', 'bg-primary-50/50');
                
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
            
            // Handle file removal
            removeFile.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = '';
                filePreview.classList.add('hidden');
                filePreview.classList.remove('block');
                fileUploadArea.classList.remove('hidden');
            });
            
            // Enhance background circle animations
            const decorativeCircles = document.querySelectorAll('.decorative-circle-1, .decorative-circle-2, .decorative-circle-3, .decorative-circle-4');
            
            if (decorativeCircles.length > 0) {
                let angles = [0, 0, 0, 0];
                
                setInterval(() => {
                    decorativeCircles.forEach((circle, index) => {
                        angles[index] += 0.02 * (index + 1) * 0.5;
                        const x = Math.sin(angles[index]) * (20 + index * 5);
                        const y = Math.cos(angles[index]) * (15 + index * 3);
                        circle.style.transform = `translate(${x}px, ${y}px) scale(${0.95 + Math.sin(angles[index]) * 0.1})`;
                    });
                }, 50);
            }
        });
    </script>
</body>
</html>