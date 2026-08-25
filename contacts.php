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
    <title>Contact Us | ACSCI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --maroon: #800000;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --text-dark: #333333;
            --text-light: #555555;
            --input-border: #e2e8f0;
            --btn-blue: #3b82f6;
            --btn-blue-hover: #2563eb;
        }
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: var(--light-bg);
            color: var(--text-dark);
            min-height: 100vh;
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
        }
        .portal-btn:hover {
            background-color: #600000;
            color: #ffffff;
        }

        /* =========================================
           CONTACT CONTENT LAYOUT (image_7d1615.png)
           ========================================= */
        .contact-wrapper {
            padding: 60px 0;
        }
        .contact-main-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 30px;
            align-items: start;
            margin-bottom: 40px;
        }
        
        /* Left Column: Info Box */
        .info-card-box {
            background: var(--white);
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }
        .info-card-box h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 35px;
        }
        .info-item-row {
            display: flex;
            margin-bottom: 30px;
            align-items: start;
        }
        .info-item-row:last-child {
            margin-bottom: 0;
        }
        .info-icon-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #fff1f2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .info-text-block h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .info-text-block p {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
            word-break: break-word;
        }

        /* Right Column: Form Box */
        .form-card-box {
            background: var(--white);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }
        .form-card-box h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 25px;
        }
        .form-row-three {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-dark);
            background-color: #f8fafc;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #94a3b8;
            background-color: var(--white);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 180px;
        }
        .form-submit-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .submit-action-btn {
            background-color: var(--btn-blue);
            color: var(--white);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-action-btn:hover {
            background-color: var(--btn-blue-hover);
        }

        /* Message Status Check Bar */
        .status-tracking-panel {
            margin-top: 35px;
            padding-top: 20px;
            border-left: 4px solid #06b6d4;
            padding-left: 20px;
        }
        .status-tracking-panel h4 {
            font-size: 15px;
            font-weight: 700;
            color: #0891b2;
            margin-bottom: 5px;
        }
        .status-tracking-panel p {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        .status-tracking-panel a {
            font-size: 14px;
            color: #06b6d4;
            text-decoration: none;
            font-weight: 600;
        }
        .status-tracking-panel a:hover {
            text-decoration: underline;
        }

        /* Map Container Wrapper */
        .map-showcase-container {
            width: 100%;
            background: var(--white);
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            overflow: hidden;
            margin-top: 10px;
        }
        .map-showcase-container iframe {
            width: 100%;
            height: 450px;
            border: 0;
            border-radius: 12px;
            display: block;
        }

        /* =========================================
           FOOTER
           ========================================= */
        footer {
            background-color: var(--maroon);
            color: #ffffff;
            padding: 60px 0 20px;
            margin-top: 40px;
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
            .contact-main-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .form-row-three {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        @media (max-width: 768px) {
            .footer-grid {
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

    <!-- UNIFIED NAVIGATION BAR -->
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
                <li><a href="aksayentista.works">Home</a></li>
                <li><a href="/about/">About</a></li>
                <li><a href="/Calendar/">Gallery</a></li>
                <li><a href="/contacts/" class="active">Contact</a></li>
            </ul>
            <div class="nav-action">
                <?php if($isLoggedIn): ?>
                    <a href="dashboard.php" class="portal-btn">
                        <?php echo htmlspecialchars($userData['Username']); ?>
                    </a>
                <?php else: ?>
                    <a href="Login.php" class="portal-btn">Student Portal</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTACT INTERFACE SECTION -->
    <main class="container contact-wrapper">
        <div class="contact-main-grid">
            
            <!-- Left Pane: Contact Information Boxed Cards -->
            <div class="info-card-box">
                <h2>Contact Information</h2>
                
                <div class="info-item-row">
                    <div class="info-icon-circle">📍</div>
                    <div class="info-text-block">
                        <h3>Location</h3>
                        <p>Lourdes Sur East, Angeles, Pampanga</p>
                    </div>
                </div>

                <div class="info-item-row">
                    <div class="info-icon-circle">📞</div>
                    <div class="info-text-block">
                        <h3>Phone</h3>
                        <p>639625410980</p>
                    </div>
                </div>

                <div class="info-item-row">
                    <div class="info-icon-circle">✉️</div>
                    <div class="info-text-block">
                        <h3>Email</h3>
                        <p>acsci.ssg@depedangelescity.com</p>
                    </div>
                </div>
            </div>

            <!-- Right Pane: Submission Form & Tracking Links -->
            <div class="form-card-box">
                <h2>Send Message</h2>
                <form action="" method="POST">
                    <div class="form-row-three">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required autocomplete="off"></textarea>
                    </div>

                    <div class="form-submit-row">
                        <button type="submit" class="submit-action-btn">Send Message</button>
                    </div>
                </form>

                <div class="status-tracking-panel">
                    <h4>Already sent a message?</h4>
                    <p>You can check the status of your previous inquiries using your tracking ID.</p>
                    <a href="#">Check Message Status</a>
                </div>
            </div>

        </div>

        <div class="map-showcase-container">
            <iframe 
                src="https://maps.google.com/maps?q=Angeles%20City%20Science%20High%20School,%20Dona%20Aurora%20St,%20Claro%20M.%20Recto,%20Angeles,%20Pampanga&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </main>

    <!-- UNIFIED FOOTER -->
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

</body>
</html>
