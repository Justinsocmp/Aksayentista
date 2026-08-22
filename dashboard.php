<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; 

// Redirect to login if not authenticated
if (empty($_SESSION["User_ID"]) || !isset($conn)) {
    header("Location: Login.php");
    exit();
}

$User_ID = $_SESSION["User_ID"];

// Read active student details
$user_query = mysqli_query($conn, "SELECT * FROM table_user WHERE User_ID = '" . mysqli_real_escape_string($conn, $User_ID) . "' LIMIT 1");
if ($user_query && mysqli_num_rows($user_query) > 0) {
    $userData = mysqli_fetch_assoc($user_query);
         
    // Redirect if an admin accidentally loads this dashboard view template
    if (isset($userData['Role']) && $userData['Role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    }
} else {
    header("Location: logout.php");
    exit();
}

$isLoggedIn = true; 
?>
<!DOCTYPE html> 
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ACSCI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --maroon: #800000;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --text-dark: #333333;
            --text-light: #555555;
            --border: #e2e8f0;
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
            width: 100%;
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
            STUDENT CENTRAL WORKSPACE CONTENT
           ========================================= */
        .dashboard-wrapper {
            background-color: var(--light-bg);
            flex-grow: 1;
            padding: 60px 0;
        }
        .welcome-header {
            margin-bottom: 35px;
        }
        .welcome-title {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .welcome-subtitle {
            font-size: 14px;
            color: #64748b;
        }
        .card-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            align-items: start;
        }
        .profile-card {
            background: #fff;
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid var(--border);
        }
        .avatar-circle {
            width: 100px;
            height: 100px;
            background-color: #fff1f2;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            border: 3px solid var(--maroon);
        }
        .profile-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .profile-role {
            font-size: 12px;
            background: #fff0f0;
            color: var(--maroon);
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 20px;
            display: inline-block;
            text-transform: uppercase;
            margin-bottom: 25px;
        }
        .info-list {
            text-align: left;
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }
        .info-row {
            margin-bottom: 15px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 14px;
            color: #334155;
            font-weight: 500;
        }
        .panel-card {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid var(--border);
            min-height: 380px;
        }
        .panel-card h3 {
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .panel-card p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }
        /* =========================================
            FOOTER
           ========================================= */
        footer {
            background-color: var(--maroon);
            color: #ffffff;
            padding: 60px 0 20px;
            margin-top: auto;
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
            .card-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; gap: 15px; }
            .footer-bottom-links a { margin: 0 10px; }
        }
    </style>
</head>
<body>
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
                <li><a href="about.php">About</a></li>
                <li><a href="contacts.php">Contact</a></li>
            </ul>
            <div class="nav-action">
                <button class="portal-btn" id="userMenuBtn">
                    <?php echo htmlspecialchars($userData['Username']); ?> 
                </button>
                <div class="user-dropdown-menu" id="userDropdown">
                    <a href="dashboard.php" style="background: #f8fafc; color: var(--maroon); font-weight: 600;">My Student Dashboard</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" style="color: #dc2626; font-weight: 600;">System Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <header class="welcome-header">
                <h2 class="welcome-title">
                    Welcome back, <?php echo !empty($userData['nickname']) ? htmlspecialchars($userData['nickname']) : htmlspecialchars($userData['Username']); ?>!
                </h2>
                <p class="welcome-subtitle">Manage your student portal information and access campus updates.</p>
            </header>

            <div class="card-grid">
                <div class="profile-card">
                    <div class="avatar-circle">🎓</div>
                    <h3 class="profile-name">
                        <?php echo !empty($userData['full_name']) ? htmlspecialchars($userData['full_name']) : htmlspecialchars($userData['Username']); ?>
                    </h3>
                    <span class="profile-role">Student Account</span>
                    
                    <div class="info-list">
                        <!-- NEW: Office and Position Output -->
                        <div class="info-row">
                            <div class="info-label">Office & Position</div>
                            <div class="info-value" style="color: #0f172a; font-weight: 600;">
                                <?php echo !empty($userData['office']) ? htmlspecialchars($userData['office']) : 'Not Provided'; ?>
                                <?php if(!empty($userData['position'])): ?>
                                    <br><span style="font-size: 13px; color: var(--maroon); font-weight: 500;"><?php echo htmlspecialchars($userData['position']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Section</div>
                            <div class="info-value" style="color: var(--maroon); font-weight: 600;">
                                <?php echo !empty($userData['section']) ? htmlspecialchars($userData['section']) : 'Not Provided'; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value">
                                <?php echo !empty($userData['contact_number']) ? htmlspecialchars($userData['contact_number']) : 'Not Provided'; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Birthday & Age</div>
                            <div class="info-value">
                                <?php 
                                $bday = !empty($userData['birthday']) ? htmlspecialchars($userData['birthday']) : 'Not Provided';
                                $age = !empty($userData['age']) ? htmlspecialchars($userData['age']) . ' yrs old' : '';
                                echo $age ? "$bday ($age)" : $bday;
                                ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Facebook Profile</div>
                            <div class="info-value">
                                <?php if(!empty($userData['fb_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($userData['fb_link']); ?>" target="_blank" style="color: #3b82f6; text-decoration: none; font-weight: 500;">View Profile</a>
                                <?php else: ?>
                                    Not Provided
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- System Level Information -->
                        <div class="info-row" style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed var(--border);">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($userData['Email']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Account ID</div>
                            <div class="info-value">#STU-<?php echo $userData['User_ID']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Verification Status</div>
                            <div class="info-value" style="color: #16a34a; font-weight: 600;">Authorized & Active</div>
                        </div>
                    </div>
                </div>

                <div class="panel-card">
                    <h3>📢 Student Notice Board</h3>
                    <p>Welcome to your personal Student Portal Dashboard space! Here you will find tailored campus documents, announcement histories, and student government resources as they are made available by the administrator.</p>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--maroon);">
                        <h4 style="font-size: 14px; margin-bottom: 5px; color: #0f172a;">System Status</h4>
                        <p style="font-size: 13px; margin: 0;">Your account is fully functional and synchronized with the central database server logs.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                <div>&copy; <?php echo date('Y'); ?> CMRICTHS. All rights reserved.</div>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Use</a>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

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