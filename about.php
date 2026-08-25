<?php  
// Only start a session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for config file
if (file_exists('config.php')) {
    require 'config.php';
} elseif (file_exists('Config.php')) {
    require 'Config.php';
}

$isLoggedIn = false;
$userData = null;

// Check user session for the dynamic navbar button
if(!empty($_SESSION["User_ID"]) && isset($conn)){
    $User_ID = $_SESSION["User_ID"];
    $result = mysqli_query($conn, "SELECT * FROM table_user WHERE User_ID = '" . mysqli_real_escape_string($conn, $User_ID) . "'");               
    if($result && mysqli_num_rows($result) > 0){
        $userData = mysqli_fetch_assoc($result);
        $isLoggedIn = true;
    } else {
        session_unset();
        session_destroy();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | ACSCI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
         
    <style>
        :root {
            --maroon: #800000;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --text-dark: #333333;
            --text-light: #555555;
        }
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: var(--white);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        /* =========================================
            NAVIGATION BAR            
           ========================================= */
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .nav-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .navbar-brand img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
            border-radius: 50%;
            object-fit: cover;
        }
        .brand-text-container {
            display: flex;
            flex-direction: column;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--maroon);
            line-height: 1.2;
        }
        .brand-subtitle {
            font-size: 11px;
            font-weight: 400;
            color: #666;
            margin-top: 2px;
        }
        .nav-links {
            display: flex;
            gap: 5px;
            align-items: center;
            list-style: none;
        }
        .nav-links a {
            text-decoration: none;
            color: #4a4a4a;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        .nav-links a:hover {
            color: var(--maroon);
        }
        .nav-links a.active {
            background-color: #f0e6e6;
            color: var(--maroon);
        }
        
        /* =========================================
            USER DROPDOWN ARCHITECTURE
           ========================================= */
        .nav-action {
            position: relative;
        }
        .portal-btn {
            background-color: var(--maroon);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .portal-btn:hover {
            background-color: #600000;
            color: #ffffff;
        }
        .user-dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.08);
            width: 220px;
            display: none;
            flex-direction: column;
            padding: 8px 0;
            z-index: 1010;
        }
        .user-dropdown-menu.show {
            display: flex;
        }
        .user-dropdown-menu a {
            padding: 12px 20px;
            text-decoration: none;
            color: #334155;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }
        .user-dropdown-menu a:hover {
            background: #f8fafc;
            color: var(--maroon);
        }
        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 6px 0;
        }

        /* =========================================
            PAGE HEADER            
           ========================================= */
        .page-header {
            background-color: var(--maroon); 
            padding: 80px 20px;
            text-align: center;
        }
        .page-header h1 {
            color: #ffffff;
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .page-header p {
            color: #ffffff;
            font-size: 16px;
            opacity: 0.9;
        }
        /* =========================================
            ABOUT CONTENT SECTIONS            
           ========================================= */
        .about-section {
            padding: 80px 0;
            background-color: #ffffff;
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        .about-text h2 {
            font-size: 32px;
            color: var(--maroon);
            margin-bottom: 20px;
            font-weight: 800;
        }
        .about-text p {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.8;
            font-size: 15px;
        }
        .about-image {
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }
        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: contain; 
            background-color: #f1f5f9; 
        }
        /* =========================================
            MISSION & VISION SECTION            
           ========================================= */
        .mission-vision {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .mv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .mv-card {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 5px solid var(--maroon);
        }
        .mv-card h3 {
            font-size: 24px;
            color: #222;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .mv-card p {
            color: #666;
            line-height: 1.7;
            font-size: 15px;
        }
        
        /* =========================================
            PLEDGE SECTION (FULLY CENTERED & UNIFIED)
           ========================================= */
        .pledge-section {
            padding: 80px 0;
            background-color: #f8f9fa;
            border-top: 1px solid #eaeaea;
        }
        .pledge-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .pledge-header h2 {
            font-size: 32px;
            color: var(--maroon);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pledge-box {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 50px;
            border-radius: 12px;
            border-top: 5px solid var(--maroon);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            text-align: center; 
        }
        .pledge-content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 30px;
            color: #1e293b;
            font-size: 15px;
            line-height: 2;
            font-weight: 500;
        }
        .pledge-paragraph {
            margin: 0;
        }
        .pledge-paragraph.highlighted {
            font-style: italic;
            color: #475569;
        }
        .pledge-footer-text {
            font-size: 16px;
            color: #0f172a;
            font-weight: 700;
            border-top: 1px dashed #cbd5e1;
            padding-top: 25px;
            margin-top: 10px;
        }

        /* =========================================
            SIDE-BY-SIDE LEADERSHIP SECTION            
           ========================================= */
        .officers-section {
            padding: 100px 0;
            background-color: #ffffff;
            border-top: 1px solid #eaeaea;
        }
        .leadership-block {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 50px;
            margin-bottom: 80px;
            align-items: start;
        }
        .leadership-block:last-child {
            margin-bottom: 0;
        }
        .left-title-pane h2 {
            font-size: 36px;
            color: #1e293b;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 15px;
            position: relative;
        }
        .left-title-pane h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background-color: #3b82f6; 
            margin-top: 15px;
        }
        .left-title-pane p {
            font-size: 15px;
            color: var(--text-light);
        }
        /* Core Officers Card Layout Grid */
        .cards-right-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
        }
        .officer-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .officer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }
        .officer-photo {
            width: 140px;
            height: 140px;
            background-color: #e2e8f0;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--maroon);
            overflow: hidden;
        }
        .officer-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .officer-card h4 {
            font-size: 17px;
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .officer-card p {
            font-size: 13px;
            color: var(--maroon);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Compact/Smaller Representatives Grid Setup */
        .reps-right-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        .rep-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 15px 10px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.01);
            transition: transform 0.2s ease;
        }
        .rep-card:hover {
            transform: translateY(-3px);
        }
        .rep-photo {
            width: 100px;
            height: 100px;
            background-color: #f1f5f9;
            border-radius: 50%;
            margin: 0 auto 12px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #64748b;
            overflow: hidden;
        }
        .rep-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .rep-card h4 {
            font-size: 14px;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .rep-card p {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        /* =========================================
            FOOTER            
           ========================================= */
        footer {
            background-color: var(--maroon);
            color: #ffffff;
            padding: 60px 0 20px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-brand {
            display: flex;
            flex-direction: column;
        }
        .footer-logo-wrap {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .footer-logo-wrap img {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            background: white;
            border-radius: 50%;
            padding: 2px;
            object-fit: cover;
        }
        .footer-brand h2 {
            font-size: 20px;
            color: #ffffff; 
            margin-bottom: 15px;
        }
        .footer-brand p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .social-icons {
            display: flex;
            gap: 10px;
        }
        .social-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .social-circle:hover {
            background-color: rgba(255,255,255,0.3);
        }
        .footer-links h3 {
            color: #ffffff; 
            font-size: 18px;
            margin-bottom: 20px;
        }
        .footer-links ul {
            list-style: none;
        }
        .footer-links li {
            margin-bottom: 12px;
        }
        .footer-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        .footer-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        .footer-contact h3 {
            color: #ffffff; 
            font-size: 18px;
            margin-bottom: 20px;
        }
        .contact-item {
            display: flex;
            margin-bottom: 15px;
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
        }
        .contact-icon {
            margin-right: 15px;
            min-width: 20px;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            opacity: 0.8;
        }
        .footer-bottom-links a {
            color: #ffffff;
            text-decoration: none;
            margin-left: 20px;
        }
        .footer-bottom-links a:hover {
            text-decoration: underline;
        }
        @media (max-width: 992px) {
            .leadership-block {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
        @media (max-width: 768px) {
            .about-grid, .mv-grid, .footer-grid {
                grid-template-columns: 1fr;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .footer-bottom-links a {
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVIGATION BAR -->
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="indexs.php" class="navbar-brand">
                <img src="logo.png" alt="ACSci Logo" onerror="this.src='https://placehold.co/50x50?text=Logo'">
                <div class="brand-text-container">
                    <span class="brand-title">ACSCI</span>
                    <span class="brand-subtitle">Angeles City Science High School</span>
                </div>
            </a>
            <ul class="nav-links">
                <li><a href="indexs.php">Home</a></li>
                <li><a href="about.php" class="active">About</a></li>
                <li><a href="Gallery.php">Gallery</a></li>
                <li><a href="contacts.php">Contact</a></li>
            </ul>
            <div class="nav-action">
                <?php if($isLoggedIn): ?>
                    <button class="portal-btn" id="userMenuBtn">
                        <?php echo htmlspecialchars($userData['Username']); ?> ▾
                    </button>
                    <div class="user-dropdown-menu" id="userDropdown">
                        <?php if(isset($userData['Role']) && $userData['Role'] === 'admin'): ?>
                            <a href="admin_dashboard.php">Control Room</a>
                        <?php else: ?>
                            <a href="dashboard.php">Dashboard</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" style="color: #dc2626; font-weight: 600;">System Logout</a>
                    </div>
                <?php else: ?>
                    <a href="Login.php" class="portal-btn">Student Portal</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <!-- PAGE HEADER -->
        <header class="page-header">
            <div class="container">
                <h1>About Us</h1>
                <p>Discover the history, mission, and leadership of our student body.</p>
            </div>
        </header>

        <!-- WHO WE ARE SECTION -->
        <section class="about-section">
            <div class="container about-grid">
                <div class="about-text">
                    <h2>Who We Are</h2>
                    <p>The Supreme Student Learner Government (SSLG) is the highest governing student body dedicated to the welfare and representation of the student populace. We serve as the bridge between the students and the school administration.</p>
                    <p>Our core focus is to foster a proactive, inclusive, and innovative school environment. Through various programs, initiatives, and community engagements, we aim to hone the leadership skills of every student while upholding academic excellence.</p>
                </div>
                <!-- IMAGE PLACEHOLDER UPDATED TO LOAD YOUR GROUP PIC -->
                <div class="about-image">
                    <img src="pic/Group.jpg" alt="ACSci SSLG Group Member Portrait">
                </div>
            </div>
        </section>

        <!-- MISSION & VISION SECTION -->
        <section class="mission-vision">
            <div class="container mv-grid">
                <div class="mv-card">
                    <h3>Our Mission</h3>
                    <p>To empower the student body through active representation, transparent governance, and the execution of meaningful projects that promote holistic development, social responsibility, and academic excellence within the campus and beyond.</p>
                </div>
                <div class="mv-card">
                    <h3>Our Vision</h3>
                    <p>We envision a united, dynamic, and forward-thinking student community where every learner is equipped with the leadership skills and values necessary to become catalysts for positive change in the modern world.</p>
                </div>
            </div>
        </section>

        <!-- PLEDGE SECTION (UNIFIED, CENTERED AND NUMBER-FREE) -->
        <section class="pledge-section">
            <div class="container">
                <div class="pledge-header">
                    <h2>Promiso dela Estyudante</h2>
                </div>
                <div class="pledge-box">
                    <div class="pledge-content-wrapper">
                        
                        <p class="pledge-paragraph">
                            Ako ay isang mag-aaral ng Angeles City Science High School<br>
                            Pinanghahahwakan ang isang pangako<br>
                            Sa aking sarili at sa paaralan<br>
                            Sa aking pamilya at sa bansang sinilangan
                        </p>
                        
                        <p class="pledge-paragraph highlighted">
                            Bilang mag-aaral ng paaralang ito<br>
                            Isasabuhay ang mga aral mula rito<br>
                            Pagsisikapang mag-aral nang mabuti<br>
                            Mamuhay na may dangal at pagmamalaki
                        </p>
                        
                        <p class="pledge-paragraph">
                            Pagbibigay-galang sa bawat nilalang<br>
                            Pagsunod sa utos, batas na inatang<br>
                            Pagiging malinis sa kapaligiran<br>
                            Tutuparin na walang pag-aalinlangan
                        </p>
                        
                        <p class="pledge-paragraph highlighted">
                            Ipaglalaban ang totoo't nararapat<br>
                            Di lilimutin ang mga moral na dapat<br>
                            Isasapuso ang mga kabutihang asal<br>
                            Iaalay sa Diyos na Maykapal!
                        </p>

                        <div class="pledge-footer-text">
                            Saan man magpunta ay hindi magbabago<br>
                            Buong karangalan ipagmamalaki ko<br>
                            Di lilimutin ang pangakong ito<br>
                            Sapagkat Angeles City Science High School ang itatatak ko
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <!-- LEADERSHIP / OFFICERS SECTION -->
        <section class="officers-section">
            <div class="container">
                                  
                <!-- TIER 1 BLOCK: CORE OFFICERS -->
                <div class="leadership-block">
                    <div class="left-title-pane">
                        <h2>Our Core Officers</h2>
                        <p>Dedicated executive team managing governance, operations, and student representation for the current academic year.</p>
                    </div>
                                          
                    <div class="cards-right-grid">
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/PRESIDENT.png" alt="President" onerror="this.src='https://placehold.co/140x140?text=President'">
                            </div>
                            <h4>Student Name</h4>
                            <p>President</p>
                        </div>
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/VICE_PRESIDENT.png" alt="Vice President" onerror="this.src='https://placehold.co/140x140?text=Vice+President'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Vice President</p>
                        </div>
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/SECRETARY.png" alt="Secretary" onerror="this.src='https://placehold.co/140x140?text=Secretary'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Secretary</p>
                        </div>
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/TREASURER.png" alt="Treasurer" onerror="this.src='https://placehold.co/140x140?text=Treasurer'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Treasurer</p>
                        </div>
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/AUDITOR.png" alt="Auditor" onerror="this.src='https://placehold.co/140x140?text=Auditor'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Auditor</p>
                        </div>
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/PIO.png" alt="P.I.O." onerror="this.src='https://placehold.co/140x140?text=P.I.O.'">
                            </div>
                            <h4>Student Name</h4>
                            <p>P.I.O.</p>
                        </div>
                        <div class="officer-card">
                            <div class="officer-photo">
                                <img src="Cores/PO.png" alt="Protocol Officer" onerror="this.src='https://placehold.co/140x140?text=Protocol+Officer'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Protocol Officer</p>
                        </div>
                    </div>
                </div>

                <!-- TIER 2 BLOCK: COMPACT REPRESENTATIVES -->
                <div class="leadership-block">
                    <div class="left-title-pane">
                        <h2>Grade Level Representatives</h2>
                        <p>Voice and representatives for individual batches across all year levels.</p>
                    </div>
                                          
                    <div class="reps-right-grid">
                        <!-- Grade 7 -->
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_7.png" alt="Grade 7 Rep" onerror="this.src='https://placehold.co/100x100?text=G7+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Incoming Grade 7 Rep</p>
                        </div>
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_7.png" alt="Grade 7 Rep" onerror="this.src='https://placehold.co/100x100?text=G7+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Incoming Grade 7 Rep</p>
                        </div>
                        
                        <!-- Grade 8 -->
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_8.png" alt="Grade 8 Rep" onerror="this.src='https://placehold.co/100x100?text=G8+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 8 Rep</p>
                        </div>
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_8.png" alt="Grade 8 Rep" onerror="this.src='https://placehold.co/100x100?text=G8+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 8 Rep</p>
                        </div>
                        
                        <!-- Grade 9 -->
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_9.png" alt="Grade 9 Rep" onerror="this.src='https://placehold.co/100x100?text=G9+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 9 Rep</p>
                        </div>
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_9.png" alt="Grade 9 Rep" onerror="this.src='https://placehold.co/100x100?text=G9+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 9 Rep</p>
                        </div>
                        
                        <!-- Grade 10 -->
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_10.png" alt="Grade 10 Rep" onerror="this.src='https://placehold.co/100x100?text=G10+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 10 Rep</p>
                        </div>
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_10.png" alt="Grade 10 Rep" onerror="this.src='https://placehold.co/100x100?text=G10+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 10 Rep</p>
                        </div>
                        
                        <!-- Grade 11 -->
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_11.png" alt="Grade 11 Rep" onerror="this.src='https://placehold.co/100x100?text=G11+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Incoming Grade 11 Rep</p>
                        </div>
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_11.png" alt="Grade 11 Rep" onerror="this.src='https://placehold.co/100x100?text=G11+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Incoming Grade 11 Rep</p>
                        </div>
                        
                        <!-- Grade 12 -->
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_12.png" alt="Grade 12 Rep" onerror="this.src='https://placehold.co/100x100?text=G12+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 12 Rep</p>
                        </div>
                        <div class="rep-card">
                            <div class="rep-photo">
                                <img src="Representatives/GRADE_12.png" alt="Grade 12 Rep" onerror="this.src='https://placehold.co/100x100?text=G12+Rep'">
                            </div>
                            <h4>Student Name</h4>
                            <p>Grade 12 Rep</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- MATCHING FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo-wrap">
                        <img src="logo.png" alt="ACSci Logo" onerror="this.src='https://placehold.co/60x60?text=Logo'">
                    </div>
                    <h2>ACSCI</h2>
                    <p>Empowering students through quality STEM education, proactive leadership, and community innovation.</p>
                    <div class="social-icons">
                        <div class="social-circle">FB</div>
                        <div class="social-circle">IG</div>
                        <div class="social-circle">TK</div>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="indexs.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="Gallery.php">Gallery</a></li>
                        <li><a href="contacts.php">Contacts</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Information</h3>
                    <div class="contact-item">
                        <span class="contact-icon"><b>Location</b></span>
                        <span>Lourdes Sur East,<br>Angeles City, Pampanga,<br>Philippines</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon"><b>Phone</b></span>
                        <span>639625410980</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon"><b>Email</b></span>
                        <span>acsci.ssg@depedangelescity.com</span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> ACSCI SSLG. All rights reserved.</div>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Use</a>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- INTERACTIVE JAVASCRIPT DROPDOWN ENGINE -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');

            if (userMenuBtn && userDropdown) {
                userMenuBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function (e) {
                    if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>
