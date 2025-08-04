<?php
// Start the session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us | E-Shongskar</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eef6f5',
              100: '#d4e9e7',
              200: '#a9d3cd',
              300: '#7fbdb4',
              400: '#54a69a',
              500: '#0b493e',
              600: '#0a4237',
              700: '#08352d',
              800: '#062922',
              900: '#041c18',
            },
            secondary: {
              50: '#f0f9ef',
              100: '#d8efd6',
              200: '#b2dfad',
              300: '#8bcf85',
              400: '#65bf5c',
              500: '#4CAF50',
              600: '#3e8c40',
              700: '#306930',
              800: '#224620',
              900: '#142310',
            },
          },
          boxShadow: {
            'card': '0 10px 30px rgba(0, 0, 0, 0.05)',
            'card-hover': '0 20px 40px rgba(0, 0, 0, 0.1)',
          },
          fontFamily: {
            'sans': ['Inter', 'system-ui', 'sans-serif'],
            'heading': ['Poppins', 'sans-serif'],
          }
        }
      }
    }
  </script>
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    /* Background and animation styles */
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-float {
      animation: float 5s ease-in-out infinite;
    }
    
    .animate-fadeIn {
      animation: fadeIn 0.5s ease forwards;
    }
    
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
    
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
      padding-top: 80px; /* For header space */
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
    
    /* Background pattern with circles */
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
      background: #0b493e;
      top: -300px;
      right: -100px;
    }
    
    .bg-pattern::after {
      background: #a8dadc;
      bottom: -200px;
      left: -150px;
    }
    
    /* Decorative circles */
    .decorative-circle {
      position: fixed;
      border-radius: 50%;
      z-index: -1;
    }
    
    .circle-1 {
      top: 20%;
      right: 10%;
      width: 200px;
      height: 200px;
      background: rgba(168, 218, 220, 0.05);
      animation: float 8s ease-in-out infinite;
    }
    
    .circle-2 {
      bottom: 15%;
      left: 5%;
      width: 300px;
      height: 300px;
      background: rgba(11, 73, 62, 0.04);
      animation: float 8s ease-in-out infinite;
      animation-delay: 2s;
    }
    
    .circle-3 {
      top: 40%;
      left: 25%;
      width: 150px;
      height: 150px;
      background: rgba(200, 230, 201, 0.1);
      animation: float 8s ease-in-out infinite;
      animation-delay: 1s;
    }
    
    .circle-4 {
      bottom: 8%;
      right: 12%;
      width: 180px;
      height: 180px;
      background: rgba(11, 73, 62, 0.03);
      animation: float 8s ease-in-out infinite;
    }
    
    .circle-5 {
      bottom: 25%;
      left: 35%;
      width: 120px;
      height: 120px;
      background: rgba(168, 218, 220, 0.06);
      animation: float 7s ease-in-out infinite;
      animation-delay: 1s;
    }
    
    .circle-6 {
      top: 15%;
      left: 10%;
      width: 100px;
      height: 100px;
      background: rgba(11, 73, 62, 0.02);
      animation: float 6s ease-in-out infinite;
      animation-delay: 2s;
    }
    
    /* Main container for content */
    .main-container {
      width: 70%;
      margin: 0 auto;
      position: relative;
      z-index: 10;
    }
    
    @media (max-width: 1280px) {
      .main-container {
        width: 80%;
      }
    }
    
    @media (max-width: 768px) {
      .main-container {
        width: 90%;
      }
    }
    
    /* Card styling */
    .glass-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .glass-card:hover {
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      transform: translateY(-5px);
    }
    
    /* Team cards */
    .team-card {
      width: 240px;
      height: 320px;
      transition: transform 0.3s, box-shadow 0.3s;
      overflow: hidden;
      position: relative;
    }
    
    .team-card:hover {
      transform: translateY(-8px) scale(1.03);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .team-card img {
      transition: transform 0.5s;
    }
    
    .team-card:hover img {
      transform: scale(1.1);
    }
    
    /* Stats Section */
    .stat-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 2rem;
      text-align: center;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      position: relative;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(11, 73, 62, 0.1) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .stat-card:hover::before {
      opacity: 1;
    }
    
    .stat-icon {
      width: 70px;
      height: 70px;
      margin: 0 auto 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: rgba(11, 73, 62, 0.1);
      border-radius: 50%;
      transition: all 0.3s ease;
    }
    
    .stat-card:hover .stat-icon {
      background-color: rgba(11, 73, 62, 0.2);
      transform: scale(1.1);
    }
    
    .stat-number {
      font-size: 3rem;
      font-weight: 700;
      color: #0b493e;
      margin-bottom: 0.5rem;
      font-family: 'Poppins', sans-serif;
    }
    
    .stat-label {
      color: #4a5568;
      font-size: 1.1rem;
      font-weight: 500;
    }

    /* Value cards */
    .value-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      transition: all 0.3s ease;
      overflow: hidden;
      height: 100%;
    }
    
    .value-card:hover {
      transform: translateY(-7px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    .value-icon {
      font-size: 2rem;
      height: 4rem;
      width: 4rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background-color: rgba(11, 73, 62, 0.1);
      color: #0b493e;
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
    }
    
    .value-card:hover .value-icon {
      transform: scale(1.15);
      background-color: rgba(11, 73, 62, 0.15);
    }
  </style>
</head>
<?php include 'header.php'; ?>
<body>
  <!-- Background pattern with circles -->
  <div class="bg-pattern"></div>
  <div class="decorative-circle circle-1"></div>
  <div class="decorative-circle circle-2"></div>
  <div class="decorative-circle circle-3"></div>
  <div class="decorative-circle circle-4"></div>
  <div class="decorative-circle circle-5"></div>
  <div class="decorative-circle circle-6"></div>
  
  <!-- Header -->
  <?php include 'header.php'; ?>

  <!-- Main Content -->
  <div class="main-container">
    <!-- Hero Section -->
    <section class="py-10 animate-fadeIn">
      <div class="glass-card p-8 md:p-12">
        <div class="flex flex-col md:flex-row gap-8 items-center">
          <div class="w-full md:w-3/5">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-500 mb-4">About E-Shongskar</h1>
            <div class="w-16 h-1 bg-secondary-500 mb-6"></div>
            <p class="text-gray-700 text-lg mb-6">E-Shongskar is a community-driven platform designed to empower citizens of Bangladesh through technology, fostering safety, security, and mutual support.</p>
            <p class="text-gray-700 mb-8">From blood donations to emergency alerts, issue reporting to community events - we bring essential services together in one unified platform to create stronger, safer communities.</p>
            <div class="flex flex-wrap gap-4">
              <a href="#mission" class="inline-block bg-primary-500 text-white font-medium px-8 py-3 rounded-full hover:bg-primary-600 transition shadow-md">Our Mission</a>
              <a href="#connect" class="inline-block bg-white border border-primary-500 text-primary-500 font-medium px-8 py-3 rounded-full hover:bg-primary-50 transition shadow-md">Connect With Us</a>
            </div>
          </div>
          <div class="w-full md:w-2/5 flex justify-center">
            <div class="relative">
              <div class="absolute -z-10 w-64 h-64 rounded-full bg-primary-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></div>
              <img src="logoEShongs.png" alt="E-Shongskar Logo" class="w-48 h-48 md:w-64 md:h-64 object-contain animate-float">
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Our Mission Section -->
    <section id="mission" class="py-10 animate-fadeIn delay-100">
      <h2 class="text-3xl font-bold text-primary-500 text-center mb-2">Our Mission</h2>
      <div class="w-16 h-1 bg-secondary-500 mx-auto mb-10"></div>
      
      <div class="glass-card p-8 overflow-hidden relative">
        <div class="flex flex-col md:flex-row gap-8 items-center">
          <div class="w-full md:w-1/2">
            <div class="rounded-2xl overflow-hidden shadow-lg">
              <img src="ServiceBloodImg.jpg" alt="Community Support" class="w-full h-64 object-cover">
            </div>
          </div>
          <div class="w-full md:w-1/2">
            <h3 class="text-2xl font-bold text-primary-600 mb-4">Unity & Support</h3>
            <p class="text-gray-700 mb-4">E-Shongskar aims to empower individuals to actively support each other through a unified platform that addresses critical community needs:</p>
            <ul class="space-y-3">
              <li class="flex items-start">
                <i class="fas fa-circle-check text-secondary-500 mt-1 mr-3"></i>
                <span>Facilitating blood donations to save lives</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-circle-check text-secondary-500 mt-1 mr-3"></i>
                <span>Providing emergency SOS alerts during critical situations</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-circle-check text-secondary-500 mt-1 mr-3"></i>
                <span>Enabling community reporting of local issues</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-circle-check text-secondary-500 mt-1 mr-3"></i>
                <span>Creating a space for organizing community events</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-circle-check text-secondary-500 mt-1 mr-3"></i>
                <span>Building connections through secure messaging</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Our Inspiration Section -->
    <section class="py-10 animate-fadeIn delay-200">
      <h2 class="text-3xl font-bold text-primary-500 text-center mb-2">Our Inspiration</h2>
      <div class="w-16 h-1 bg-secondary-500 mx-auto mb-10"></div>
      
      <div class="glass-card p-8 md:p-12">
        <div class="flex flex-col md:flex-row gap-8 items-center">
          <div class="w-full md:w-3/5 order-2 md:order-1">
            <h3 class="text-2xl font-bold text-primary-600 mb-4">Community-Driven Solutions</h3>
            <p class="text-gray-700 mb-6">Considering the challenges facing safety and security in Bangladesh, we envisioned a platform that would harness the power of technology and community engagement to create meaningful change.</p>
            <p class="text-gray-700 mb-6">Our inspiration comes from the belief that when empowered with the right tools, communities can address their most pressing needs through cooperation, information sharing, and mutual support.</p>
            <div class="flex flex-wrap gap-4 items-center">
              <div class="text-lg font-semibold text-primary-500">Our Values:</div>
              <span class="px-4 py-2 bg-primary-50 text-primary-600 rounded-full text-sm">Empowerment</span>
              <span class="px-4 py-2 bg-primary-50 text-primary-600 rounded-full text-sm">Transparency</span>
              <span class="px-4 py-2 bg-primary-50 text-primary-600 rounded-full text-sm">Innovation</span>
              <span class="px-4 py-2 bg-primary-50 text-primary-600 rounded-full text-sm">Inclusivity</span>
            </div>
          </div>
          <div class="w-full md:w-2/5 flex justify-center order-1 md:order-2">
            <div class="rounded-full overflow-hidden border-4 border-white shadow-lg">
              <img src="connect-removebg-preview.png" alt="Community Connection" class="w-64 h-64 object-contain p-4">
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Our Core Values -->
    <section class="py-10 animate-fadeIn delay-300">
      <h2 class="text-3xl font-bold text-primary-500 text-center mb-2">Our Core Values</h2>
      <div class="w-16 h-1 bg-secondary-500 mx-auto mb-10"></div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Value 1 -->
        <div class="glass-card p-6 value-card">
          <div class="value-icon mx-auto">
            <i class="fas fa-hands-helping"></i>
          </div>
          <h3 class="text-xl font-bold text-primary-500 text-center mb-3">Community First</h3>
          <p class="text-gray-700 text-center">We prioritize the needs and wellbeing of communities, creating solutions that address their most pressing challenges.</p>
        </div>
        
        <!-- Value 2 -->
        <div class="glass-card p-6 value-card">
          <div class="value-icon mx-auto">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h3 class="text-xl font-bold text-primary-500 text-center mb-3">Safety & Security</h3>
          <p class="text-gray-700 text-center">We're committed to building a safer environment through technology-enabled support systems and rapid response mechanisms.</p>
        </div>
        
        <!-- Value 3 -->
        <div class="glass-card p-6 value-card">
          <div class="value-icon mx-auto">
            <i class="fas fa-lightbulb"></i>
          </div>
          <h3 class="text-xl font-bold text-primary-500 text-center mb-3">Innovation</h3>
          <p class="text-gray-700 text-center">We continuously explore new approaches to leverage technology for social good and community empowerment.</p>
        </div>
        
        <!-- Value 4 -->
        <div class="glass-card p-6 value-card">
          <div class="value-icon mx-auto">
            <i class="fas fa-handshake"></i>
          </div>
          <h3 class="text-xl font-bold text-primary-500 text-center mb-3">Collaboration</h3>
          <p class="text-gray-700 text-center">We believe in the power of working together to solve complex problems and create lasting positive change.</p>
        </div>
        
        <!-- Value 5 -->
        <div class="glass-card p-6 value-card">
          <div class="value-icon mx-auto">
            <i class="fas fa-users"></i>
          </div>
          <h3 class="text-xl font-bold text-primary-500 text-center mb-3">Inclusivity</h3>
          <p class="text-gray-700 text-center">Our platform is designed to be accessible and beneficial to all members of society, regardless of background.</p>
        </div>
        
        <!-- Value 6 -->
        <div class="glass-card p-6 value-card">
          <div class="value-icon mx-auto">
            <i class="fas fa-heart"></i>
          </div>
          <h3 class="text-xl font-bold text-primary-500 text-center mb-3">Compassion</h3>
          <p class="text-gray-700 text-center">We approach every challenge with empathy, understanding that behind every issue is a human story worthy of care.</p>
        </div>
      </div>
    </section>
    
    <!-- Meet the Team Section -->
    <section class="py-10 animate-fadeIn delay-400">
      <h2 class="text-3xl font-bold text-primary-500 text-center mb-2">Meet Our Team</h2>
      <div class="w-16 h-1 bg-secondary-500 mx-auto mb-4"></div>
      <p class="text-center text-gray-700 mb-10 max-w-2xl mx-auto">Passionate university students dedicated to making a positive impact through technology, social innovation, and community engagement.</p>
      
      <div class="flex flex-wrap justify-center gap-8">
        <!-- Team Member 1 -->
        <div class="team-card glass-card p-6 flex flex-col items-center">
          <div class="mb-4 w-40 h-40 rounded-full overflow-hidden border-4 border-secondary-300">
            <img src="uploads/icons8-grow-up-60.png" alt="Team Member" class="w-full h-full object-cover">
          </div>
          <h3 class="text-xl font-bold text-primary-500 mb-1">Ibrahim Khalil</h3>
          <p class="text-secondary-600 mb-2">Backend Developer</p>
          <p class="text-gray-600 text-sm text-center mb-4">Passionate about creating robust systems that serve communities.</p>
          <div class="flex gap-3 text-gray-400">
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-linkedin"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-github"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
        
        <!-- Team Member 2 -->
        <div class="team-card glass-card p-6 flex flex-col items-center">
          <div class="mb-4 w-40 h-40 rounded-full overflow-hidden border-4 border-secondary-300">
            <img src="uploads/icons8-grow-up-60.png" alt="Team Member" class="w-full h-full object-cover">
          </div>
          <h3 class="text-xl font-bold text-primary-500 mb-1">Shahriar Mahmud</h3>
          <p class="text-secondary-600 mb-2">UI/UX Designer</p>
          <p class="text-gray-600 text-sm text-center mb-4">Creating user-centric designs that make technology accessible to all.</p>
          <div class="flex gap-3 text-gray-400">
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-linkedin"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-github"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
        
        <!-- Team Member 3 -->
        <div class="team-card glass-card p-6 flex flex-col items-center">
          <div class="mb-4 w-40 h-40 rounded-full overflow-hidden border-4 border-secondary-300">
            <img src="uploads/icons8-grow-up-60.png" alt="Team Member" class="w-full h-full object-cover">
          </div>
          <h3 class="text-xl font-bold text-primary-500 mb-1">Mohammad Akibul</h3>
          <p class="text-secondary-600 mb-2">Frontend Developer</p>
          <p class="text-gray-600 text-sm text-center mb-4">Building intuitive interfaces that connect people with essential services.</p>
          <div class="flex gap-3 text-gray-400">
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-linkedin"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-github"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
        
        <!-- Team Member 4 -->
        <div class="team-card glass-card p-6 flex flex-col items-center">
          <div class="mb-4 w-40 h-40 rounded-full overflow-hidden border-4 border-secondary-300">
            <img src="uploads/icons8-grow-up-60.png" alt="Team Member" class="w-full h-full object-cover">
          </div>
          <h3 class="text-xl font-bold text-primary-500 mb-1">Moriom Mim</h3>
          <p class="text-secondary-600 mb-2">Content Manager</p>
          <p class="text-gray-600 text-sm text-center mb-4">Crafting compelling narratives that inspire community action.</p>
          <div class="flex gap-3 text-gray-400">
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-linkedin"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fab fa-github"></i></a>
            <a href="#" class="hover:text-primary-500 transition"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-10 animate-fadeIn delay-500">
      <h2 class="text-3xl font-bold text-primary-500 text-center mb-2">Our Impact</h2>
      <div class="w-16 h-1 bg-secondary-500 mx-auto mb-10"></div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stat 1 -->
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-users text-primary-500 text-2xl"></i>
          </div>
          <div class="stat-number"><?php echo $activeUserCount ?? '+1000'; ?></div>
          <div class="stat-label">Active Community Members</div>
        </div>
        
        <!-- Stat 2 -->
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-check-circle text-primary-500 text-2xl"></i>
          </div>
          <div class="stat-number"><?php echo $solved_reports ?? '+500'; ?></div>
          <div class="stat-label">Issues Successfully Resolved</div>
        </div>
        
        <!-- Stat 3 -->
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-heart text-primary-500 text-2xl"></i>
          </div>
          <div class="stat-number"><?php echo $blood_donations ?? '+300'; ?></div>
          <div class="stat-label">Life-saving Blood Donations</div>
        </div>
      </div>
    </section>
    
    <!-- Contact Section -->
    <section id="connect" class="py-10 mb-8 animate-fadeIn delay-500">
      <h2 class="text-3xl font-bold text-primary-500 text-center mb-2">Get In Touch</h2>
      <div class="w-16 h-1 bg-secondary-500 mx-auto mb-10"></div>
      
      <div class="glass-card p-8">
        <div class="flex flex-col md:flex-row gap-8">
          <div class="w-full md:w-1/2">
            <h3 class="text-2xl font-bold text-primary-600 mb-6">Contact Us</h3>
            <div class="space-y-4">
              <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center mr-4">
                  <i class="fas fa-map-marker-alt text-primary-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">Address</h4>
                  <p class="text-gray-600">Dhaka, Bangladesh</p>
                </div>
              </div>
              
              <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center mr-4">
                  <i class="fas fa-envelope text-primary-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">Email</h4>
                  <p class="text-gray-600">contact@e-shongskar.com</p>
                </div>
              </div>
              
              <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center mr-4">
                  <i class="fas fa-phone text-primary-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">Phone</h4>
                  <p class="text-gray-600">+880 1XX XXX XXXX</p>
                </div>
              </div>
            </div>
            
            <div class="mt-8">
              <h4 class="font-medium text-gray-800 mb-3">Follow Us</h4>
              <div class="flex gap-4">
                <a href="#" class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-500 hover:bg-primary-500 hover:text-white transition">
                  <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-500 hover:bg-primary-500 hover:text-white transition">
                  <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-500 hover:bg-primary-500 hover:text-white transition">
                  <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-500 hover:bg-primary-500 hover:text-white transition">
                  <i class="fab fa-linkedin-in"></i>
                </a>
              </div>
            </div>
          </div>
          
          <div class="w-full md:w-1/2">
            <h3 class="text-2xl font-bold text-primary-600 mb-6">Send Us a Message</h3>
            <form action="#" method="POST" class="space-y-4">
              <div>
                <label for="name" class="block text-gray-700 mb-1">Name</label>
                <input type="text" id="name" name="name" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary-500 focus:outline-none">
              </div>
              
              <div>
                <label for="email" class="block text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary-500 focus:outline-none">
              </div>
              
              <div>
                <label for="message" class="block text-gray-700 mb-1">Message</label>
                <textarea id="message" name="message" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary-500 focus:outline-none"></textarea>
              </div>
              
              <button type="submit" class="bg-primary-500 text-white font-medium px-6 py-3 rounded-lg hover:bg-primary-600 transition w-full">Send Message</button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
  
  <!-- Footer -->
  <?php include 'footer.php'; ?>

  <script>
    // Add scroll animations
    document.addEventListener('DOMContentLoaded', function() {
      const animatedElements = document.querySelectorAll('.animate-fadeIn');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, {
        threshold: 0.1
      });
      
      animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(element);
      });
    });
  </script>
</body>

</html>