index.php:
<?php
include 'database.php';

$activeUserCount = 0;
$solved_reports = 0;

try {
    $query = "SELECT COUNT(*) as active_count FROM users";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $activeUserCount = $result['active_count'];
} catch (PDOException $e) {
    $activeUserCount = "Error fetching data";
}

try {
    $query = "SELECT COUNT(*) as reports_solved FROM report_issues WHERE status = 'solved'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $solved_reports = $result['reports_solved'];
} catch (PDOException $e) {
    $solved_reports = "Error fetching data";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shongskar - Community Safety Platform</title>
    <meta name="description" content="E-Shongskar connects communities for safety, emergency response, and neighborhood collaboration">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <!-- Header -->
    <!-- <header class="header">
        <div class="container header-container">
            <a href="#" class="logo">
                <img class="logo-img" src="EShongskarIcon.jpg" alt="E-Shongskar Logo">
                <span class="logo-text">E-Shongskar</span>
            </a>
            
            <button class="mobile-menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            
            <nav class="nav">
                <ul class="nav-list">
                    <li><a href="#" class="nav-link">Home</a></li>
                    <li><a href="#" class="nav-link">Services</a></li>
                    <li><a href="#" class="nav-link">About</a></li>
                    <li><a href="#" class="nav-link">Contact</a></li>
                    <li><a href="login.php" class="btn btn-secondary">Login</a></li>
                </ul>
            </nav>
        </div>
    </header> -->


<header class="header">
    <div class="container header-container">
        <a href="#" class="logo">
            <img class="logo-img" src="EShongskarIcon.png" alt="E-Shongskar Logo">
            <span class="logo-text">E-Shongskar</span>
        </a>
        
        <!-- <button class="mobile-menu-btn"></button> -->
        
        <nav class="nav">
            <ul class="nav-list">
                <li><a href="#" class="nav-link">Home</a></li>
                <li><a href="#" class="nav-link">Services</a></li>
                <li><a href="#" class="nav-link">About</a></li>
                <li><a href="#" class="nav-link">Contact</a></li>
            </ul>
            <a href="login.php" class="btn btn-secondary nav-login-btn">Login</a>
        </nav>
    </div>
</header>

    <!-- Hero Section -->
    <section class="hero section">
        <div class="container hero-container">
            <div class="hero-content fade-in">
                <h1 class="hero-title">Empowering Communities Through Technology</h1>
                <p class="hero-subtitle">E-Shongskar connects neighbors, enhances safety, and fosters collaboration to create stronger, more resilient communities.</p>
                <div class="hero-buttons">
                    <a href="report_issue.php" class="btn btn-primary">Report Issue</a>
                    <a href="blood_donation.php" class="btn btn-outline">Search for Blood</a>
                </div>
            </div>

<div class="iphone float">
    <div class="iphone-cam"></div>
    <img class="gif" src="giphy.gif">
</div>
            <!-- <div class="hero-image fade-in delay-1">
                <img src="phoneDisplay.png" alt="E-Shongskar App Preview" class="hero-img">
            </div> -->
            <div class="hero-pattern"></div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services section">
        <div class="container">
            <h2 class="section-title">Our Comprehensive Services</h2>
            <p class="section-subtitle">Everything you need to stay connected, safe, and informed in your community</p>
            
            <div class="services-grid">
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <img src="ServiceReportImg.jpg" alt="Report Icon">
                    </div>
                    <h3 class="service-title">Incident Reporting</h3>
                    <p class="service-description">Quickly report neighborhood incidents with real-time alerts to keep everyone informed and safe.</p>
                </div>
                
                <div class="service-card fade-in delay-1">
                    <div class="service-icon">
                        <img src="ServiceEventImg.jpg" alt="Events Icon">
                    </div>
                    <h3 class="service-title">Community Events</h3>
                    <p class="service-description">Discover and organize local events to strengthen community bonds and participation.</p>
                </div>
                
                <div class="service-card fade-in delay-2">
                    <div class="service-icon">
                        <img src="ServiceSosImg.jpg" alt="SOS Icon">
                    </div>
                    <h3 class="service-title">Emergency SOS</h3>
                    <p class="service-description">Instant emergency alerts with your location sent to nearby community members and authorities.</p>
                </div>
                
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <img src="ServiceBloodImg.jpg" alt="Blood Donation Icon">
                    </div>
                    <h3 class="service-title">Blood Donation Network</h3>
                    <p class="service-description">Connect donors with recipients in critical need through our life-saving blood donation platform.</p>
                </div>
                
                <div class="service-card fade-in delay-1">
                    <div class="service-icon">
                        <img src="ServiceChatImg.jpg" alt="Chat Icon">
                    </div>
                    <h3 class="service-title">Neighborhood Chat</h3>
                    <p class="service-description">Secure messaging with nearby residents for quick communication and coordination.</p>
                </div>
                
                <!-- <div class="service-card fade-in delay-2">
                    <div class="service-icon">
                        <img src="ServiceLocationImg.jpg" alt="Location Icon">
                    </div>
                    <h3 class="service-title">Location Services</h3>
                    <p class="service-description">Share your location during emergencies or when organizing community activities.</p>
                </div> -->
                    <div class="service-card fade-in delay-2">
                    <div class="service-icon">
                        <img src="telephone2.png" alt="Emergency Icon">
                    </div>
                    <h3 class="service-title">Emergency Contacts</h3>
                    <p class="service-description">Easily get access to all the emergency contacts whenever in need</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features section">
        <div class="features-pattern"></div>
        <div class="container features-container">
            <h2 class="section-title">Key Features</h2>
            <p class="section-subtitle">Designed to keep you safe and connected with your community</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <img src="alert-removebg-preview.png" alt="Safety Alerts Icon">
                    </div>
                    <h3 class="feature-title">Real-time Safety Alerts</h3>
                    <p class="feature-description">Receive instant notifications about incidents in your area to stay situationally aware and avoid potential dangers.</p>
                </div>
                
                <div class="feature-card fade-in delay-1">
                    <div class="feature-icon">
                        <img src="connect-removebg-preview.png" alt="Stay Informed Icon">
                    </div>
                    <h3 class="feature-title">Community Network</h3>
                    <p class="feature-description">Build connections with neighbors to share information, resources, and support when needed most.</p>
                </div>
                
                <div class="feature-card fade-in delay-2">
                    <div class="feature-icon">
                        <img src="sos-removebg-preview.png" alt="SOS Feature Icon">
                    </div>
                    <h3 class="feature-title">Emergency Response</h3>
                    <p class="feature-description">One-tap SOS with automatic location sharing to alert nearby help in critical situations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats section">
        <div class="container">
            <h2 class="section-title">Our Impact</h2>
            <p class="section-subtitle">Join thousands of community members making their neighborhoods safer</p>
            
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-icon">
                        <img src="people-removebg-preview.png" alt="Community Members Icon">
                    </div>
                    <div class="stat-number"><?= $activeUserCount ?></div>
                    <div class="stat-label">Active Community Members</div>
                </div>
                
                <div class="stat-card fade-in delay-1">
                    <div class="stat-icon">
                        <img src="360_F_288138556_2EnxidqIM2bzlpIySUIg8P12pb9Eqw3i.jpg" alt="Issues Resolved Icon">
                    </div>
                    <div class="stat-number"><?= $solved_reports ?></div>
                    <div class="stat-label">Issues Successfully Resolved</div>
                </div>
                
                <div class="stat-card fade-in delay-2">
                    <div class="stat-icon">
                        <img src="blood.jpg" alt="Blood Donations Icon">
                    </div>
                    <div class="stat-number">12</div>
                    <div class="stat-label">Life-saving Blood Donations</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials section">
        <div class="container">
            <h2 class="section-title">Community Voices</h2>
            <p class="section-subtitle">Hear what our users say about their E-Shongskar experience</p>
            
            <div class="testimonials-grid">
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        "E-Shongskar transformed how our neighborhood communicates. When we had a break-in attempt, the alert system helped us respond immediately and prevent theft."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="monpura.jpg" alt="Nowar Sharmin">
                        </div>
                        <div class="author-info">
                            <div class="author-name">Nowar Sharmin</div>
                            <div class="author-rating">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card fade-in delay-1">
                    <div class="testimonial-content">
                        "As someone who lives alone, the SOS feature gives me peace of mind. I know help is just one tap away if I ever need it. It also keeps my family tension-free"
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="chris.jpg" alt="Michael Chen">
                        </div>
                        <div class="author-info">
                            <div class="author-name">Michael Chen</div>
                            <div class="author-rating">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card fade-in delay-2">
                    <div class="testimonial-content">
                        "The blood donation network saved my nephew's life when he needed an urgent transfusion. The community response was overwhelming and heartwarming."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="safa.jpg" alt="Safia Kabir">
                        </div>
                        <div class="author-info">
                            <div class="author-name">Safia Kabir</div>
                            <div class="author-rating">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <!-- <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img class="footer-logo-img" src="EShongskarIcon.jpg" alt="E-Shongskar Logo">
                        <span class="footer-logo-text">E-Shongskar</span>
                    </div>
                    <p class="footer-description">
                        Building safer, more connected communities through technology and collaboration since 2023.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-icon">FB</a>
                        <a href="#" class="social-icon">TW</a>
                        <a href="#" class="social-icon">IG</a>
                        <a href="#" class="social-icon">LI</a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">Home</a></li>
                        <li><a href="#" class="footer-link">Services</a></li>
                        <li><a href="#" class="footer-link">About Us</a></li>
                        <li><a href="#" class="footer-link">Contact</a></li>
                        <li><a href="login.php" class="footer-link">Login</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3 class="footer-title">Services</h3>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">Incident Reporting</a></li>
                        <li><a href="#" class="footer-link">Community Events</a></li>
                        <li><a href="#" class="footer-link">Emergency SOS</a></li>
                        <li><a href="blood_donation.php" class="footer-link">Blood Donation</a></li>
                        <li><a href="#" class="footer-link">Neighborhood Chat</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-links">
                        <li class="footer-link">123 Community St, Dhaka</li>
                        <li class="footer-link">contact@eshongskar.com</li>
                        <li class="footer-link">+880 1234 567890</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2023 E-Shongskar. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer> -->
        <?php include 'footer.php'; ?>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('.nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        
        // Header scroll effect
        const header = document.querySelector('.header');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Animation on scroll
        const fadeElements = document.querySelectorAll('.fade-in');
        
        const fadeInOnScroll = () => {
            fadeElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        };
        
        // Initialize elements as invisible
        fadeElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        });
        
        window.addEventListener('scroll', fadeInOnScroll);
        window.addEventListener('load', fadeInOnScroll);
    </script>
</body>
</html>