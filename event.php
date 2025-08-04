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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['event_description']) && !empty($_POST['category'])) {
        $event_description = $_POST['event_description'];
        $category = $_POST['category'];
        $username = $user['username'];
        $contact = $user['contact_no'];
        $location = $user['location'];

        $stmt = $pdo->prepare("INSERT INTO events (username, contact, location, event_description, category) 
                               VALUES (:username, :contact, :location, :event_description, :category)");
        $result = $stmt->execute([
            'username' => $username,
            'contact' => $contact,
            'location' => $location,
            'event_description' => $event_description,
            'category' => $category
        ]);

        if ($result) {
            header('Location: response_events.php');
            exit;
        } else {
            $error = "Failed to create event. Please try again.";
        }
    } else {
        $error = "Please provide a description and select a category for the event.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Event | E-Shongskar</title>
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
                            500: '#0b493e',
                            600: '#094236',
                            700: '#07362d',
                            800: '#052b24',
                            900: '#021d18',
                        },
                        secondary: {
                            50: '#f0f9fa',
                            100: '#d0eff1',
                            200: '#a8dadc',
                            300: '#86c5c8',
                            400: '#5aacaf',
                            500: '#418e91',
                            600: '#377779',
                            700: '#2c6062',
                            800: '#224a4c',
                            900: '#182f30',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.01)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    },
                },
            },
        }
    </script>
    
    <style>
        /* Background and layout styles */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
            padding-top: 30pt;
        }
        
        html {
            background: linear-gradient(135deg, #e0f7fa 0%, #c8e6c9 100%);
            background-attachment: fixed;
            height: 100%;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Floating circle animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        /* Background pattern with circles */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        
        /* Decorative circles */
        .decorative-circle-1 {
            position: fixed;
            top: 20%;
            right: 10%;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168, 218, 220, 0.2) 0%, rgba(168, 218, 220, 0.05) 70%, transparent 100%);
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
            background: radial-gradient(circle, rgba(11, 73, 62, 0.15) 0%, rgba(11, 73, 62, 0.05) 70%, transparent 100%);
            z-index: -1;
            animation: float 10s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        .decorative-circle-3 {
            position: fixed;
            top: 40%;
            left: 25%;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(200, 230, 201, 0.25) 0%, rgba(200, 230, 201, 0.08) 70%, transparent 100%);
            z-index: -1;
            animation: float 7s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        .decorative-circle-4 {
            position: fixed;
            bottom: 8%;
            right: 12%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(11, 73, 62, 0.12) 0%, rgba(11, 73, 62, 0.04) 70%, transparent 100%);
            z-index: -1;
            animation: float 8s ease-in-out infinite;
            animation-delay: 0.5s;
        }
        
        .decorative-circle-5 {
            position: fixed;
            top: 25%;
            right: 35%;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168, 218, 220, 0.18) 0%, rgba(168, 218, 220, 0.06) 70%, transparent 100%);
            z-index: -1;
            animation: float 6s ease-in-out infinite;
            animation-delay: 1.5s;
        }
        
        .decorative-circle-6 {
            position: fixed;
            bottom: 35%;
            left: 35%;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(200, 230, 201, 0.2) 0%, rgba(200, 230, 201, 0.06) 70%, transparent 100%);
            z-index: -1;
            animation: float 8.5s ease-in-out infinite;
            animation-delay: 2.5s;
        }
        
        /* Additional decorative circles for more visibility */
        .decorative-circle-7 {
            position: fixed;
            top: 15%;
            left: 15%;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168, 218, 220, 0.15) 0%, rgba(168, 218, 220, 0.03) 70%, transparent 100%);
            z-index: -1;
            animation: float 9s ease-in-out infinite;
            animation-delay: 3s;
        }
        
        .decorative-circle-8 {
            position: fixed;
            bottom: 25%;
            right: 25%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(11, 73, 62, 0.1) 0%, rgba(11, 73, 62, 0.02) 70%, transparent 100%);
            z-index: -1;
            animation: float 11s ease-in-out infinite;
            animation-delay: 1.5s;
        }
        
        .decorative-circle-9 {
            position: fixed;
            top: 60%;
            right: 5%;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(200, 230, 201, 0.2) 0%, rgba(200, 230, 201, 0.05) 70%, transparent 100%);
            z-index: -1;
            animation: float 7.5s ease-in-out infinite;
            animation-delay: 2.8s;
        }
        
        .decorative-circle-10 {
            position: fixed;
            top: 70%;
            left: 8%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168, 218, 220, 0.12) 0%, rgba(168, 218, 220, 0.04) 70%, transparent 100%);
            z-index: -1;
            animation: float 10.5s ease-in-out infinite;
            animation-delay: 0.8s;
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
        .delay-400 { animation-delay: 0.4s; }
        
        /* Custom scrollbar */
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
        
        /* Glass morphism effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Form styling */
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(11, 73, 62, 0.15);
        }
    </style>
</head>

<body class="text-gray-800">
    <!-- Background pattern with circles -->
    <div class="bg-pattern"></div>
    <div class="decorative-circle-1"></div>
    <div class="decorative-circle-2"></div>
    <div class="decorative-circle-3"></div>
    <div class="decorative-circle-4"></div>
    <div class="decorative-circle-5"></div>
    <div class="decorative-circle-6"></div>
    <div class="decorative-circle-7"></div>
    <div class="decorative-circle-8"></div>
    <div class="decorative-circle-9"></div>
    <div class="decorative-circle-10"></div>

    <?php include 'header.php'; ?>
    
    <!-- Hero Section -->
    <section class="relative overflow-hidden py-16" style="margin-top: -70px; background: transparent;">
        <!-- Abstract shapes background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-50 rounded-full opacity-30 animate-pulse"></div>
            <div class="absolute top-40 -left-20 w-72 h-72 bg-secondary-100 rounded-full opacity-20" style="animation: float 8s ease-in-out infinite;"></div>
            <div class="absolute -bottom-32 right-20 w-80 h-80 bg-primary-100 rounded-full opacity-20" style="animation: float 10s ease-in-out infinite; animation-delay: 1s;"></div>
        </div>
        
        <!-- Content -->
        <div class="container mx-auto max-w-4xl px-4 relative z-10">
            <div class="flex flex-col items-center text-center">
                <!-- Back Button -->
                <div class="w-full flex justify-start mb-6 opacity-0 animate-fadeIn">
                    <a href="response_events.php" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-primary-800 font-medium px-6 py-3 rounded-full transition-all duration-300 backdrop-blur-sm shadow-sm hover:shadow-md">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Events</span>
                    </a>
                </div>
                
                <h1 class="text-4xl lg:text-5xl font-bold text-primary-800 mb-4 opacity-0 animate-fadeIn delay-100">Create Event</h1>
                <p class="text-lg text-primary-800/80 mb-8 max-w-2xl opacity-0 animate-fadeIn delay-200">Share your event with the community and make a positive impact</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-12 px-4" style="background: transparent !important;">
        <div class="container mx-auto max-w-3xl">
            <!-- Form Container -->
            <div class="glass-effect rounded-3xl shadow-card p-8 md:p-12 opacity-0 animate-fadeIn delay-300">
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="event.php" class="space-y-6">
                    <!-- User Information Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-sm font-semibold text-primary-800 mb-2">
                                <i class="fas fa-user mr-2 text-primary-500"></i>Username
                            </label>
                            <input type="text" 
                                   class="form-input w-full px-4 py-3 border border-primary-200 rounded-xl bg-white/80 text-primary-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   readonly>
                        </div>
                        <div>
                            <label for="contact" class="block text-sm font-semibold text-primary-800 mb-2">
                                <i class="fas fa-phone mr-2 text-primary-500"></i>Contact No.
                            </label>
                            <input type="text" 
                                   class="form-input w-full px-4 py-3 border border-primary-200 rounded-xl bg-white/80 text-primary-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200" 
                                   id="contact" 
                                   name="contact" 
                                   value="<?php echo htmlspecialchars($user['contact_no']); ?>" 
                                   readonly>
                        </div>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-semibold text-primary-800 mb-2">
                            <i class="fas fa-map-marker-alt mr-2 text-primary-500"></i>Location
                        </label>
                        <input type="text" 
                               class="form-input w-full px-4 py-3 border border-primary-200 rounded-xl bg-white/80 text-primary-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200" 
                               id="location" 
                               name="location" 
                               value="<?php echo htmlspecialchars($user['location']); ?>" 
                               readonly>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-semibold text-primary-800 mb-2">
                            <i class="fas fa-tags mr-2 text-primary-500"></i>Event Category
                        </label>
                        <select class="form-input w-full px-4 py-3 border border-primary-200 rounded-xl bg-white/80 text-primary-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200" 
                                id="category" 
                                name="category" 
                                required>
                            <option selected disabled value="">-- Select a Category --</option>
                            <option value="Community Development Events">Community Development Events</option>
                            <option value="Health and Wellness Events">Health and Wellness Events</option>
                            <option value="Educational and Awareness Events">Educational and Awareness Events</option>
                            <option value="Social and Cultural Events">Social and Cultural Events</option>
                            <option value="Environment and Sustainability">Environment and Sustainability</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <!-- Event Description -->
                    <div>
                        <label for="event_description" class="block text-sm font-semibold text-primary-800 mb-2">
                            <i class="fas fa-edit mr-2 text-primary-500"></i>Event Description
                        </label>
                        <textarea class="form-input w-full px-4 py-3 border border-primary-200 rounded-xl bg-white/80 text-primary-800 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 resize-none" 
                                  id="event_description" 
                                  name="event_description" 
                                  rows="6"
                                  placeholder="Describe your event in detail - include the purpose, activities, target audience, and any special requirements..." 
                                  required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-6">
                        <button type="submit" 
                                class="group bg-primary-500 hover:bg-primary-600 text-white font-semibold py-4 px-8 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary-400 flex items-center gap-3">
                            <i class="fas fa-calendar-plus group-hover:scale-110 transition-transform duration-300"></i>
                            <span>Create Event</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const description = document.getElementById('event_description').value.trim();
            const category = document.getElementById('category').value;

            if (description === '') {
                e.preventDefault();
                showNotification('Please enter a description for the event.', 'error');
                return false;
            }

            if (category === '') {
                e.preventDefault();
                showNotification('Please select a category for the event.', 'error');
                return false;
            }

            return true;
        });

        // Notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Animate out and remove
            setTimeout(() => {
                notification.style.transform = 'translateX(full)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Character counter for description
        const descriptionTextarea = document.getElementById('event_description');
        const maxLength = 1000;
        
        // Create character counter
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-sm text-gray-500 mt-1 text-right';
        counterDiv.textContent = `0 / ${maxLength} characters`;
        descriptionTextarea.parentNode.appendChild(counterDiv);

        descriptionTextarea.addEventListener('input', function() {
            const length = this.value.length;
            counterDiv.textContent = `${length} / ${maxLength} characters`;
            counterDiv.className = length > maxLength * 0.9 ? 'text-sm text-orange-500 mt-1 text-right' : 'text-sm text-gray-500 mt-1 text-right';
        });
    </script>
</body>

</html>