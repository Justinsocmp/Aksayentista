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

// Check user session
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

// Fetch articles for the carousel
$articles = [];
if(isset($conn)) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'articles'");
    if($check_table && mysqli_num_rows($check_table) > 0) {
        $query = "SELECT * FROM articles ORDER BY sort_order ASC, id ASC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $articles[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACSCI SSLG</title>
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
            display: none; /* Controlled via JS */
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
            Carousel Styles
           ========================================= */
        .carousel {
            position: relative;
            width: 100%;
            height: calc(100vh - 80px);
            overflow: hidden;
            background-color: var(--maroon);
        }
        .list .item {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 0.8s ease;
            display: flex;
            align-items: flex-end;
        }
        .list .item.active {
            opacity: 1;
        }
        .list .item::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(115, 4, 4, 0.95) 0%, rgba(0, 0, 0, 0.2) 60%, transparent 100%);
        }
        .content {
            position: relative;
            z-index: 2;
            padding: 3rem 4rem 5rem;
            max-width: 800px;
            color: var(--white);
        }
        .content .title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }
        .content .name {
            display: inline-block;
            background: var(--white);
            color: var(--maroon);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .content .des {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .content .btn a {
            display: inline-block;
            padding: 0.8rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            background: var(--white);
            color: var(--maroon);
            transition: all 0.2s ease;
        }
        .content .btn a:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }
        .arrows {
            position: absolute;
            bottom: 2.5rem;
            right: 3rem;
            z-index: 10;
            display: flex;
            gap: 1rem;
        }
        .arrows button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.6);
            background: rgba(115, 4, 4, 0.5);
            color: var(--white);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .arrows button:hover {
            background: var(--white);
            color: var(--maroon);
        }
        .timeRunning {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 5px;
            background: var(--white);
            width: 0%;
            z-index: 10;
        }

        /* =========================================
            Features & Showcase
           ========================================= */
        .features {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 5rem 2rem;
            background-color: var(--light-bg);
        }
        .feature-card {
            text-align: center;
            padding: 2.5rem;
            background: var(--white);
            border-radius: 12px;
            border-top: 4px solid var(--maroon);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            flex: 1;
            min-width: 250px;
            max-width: 350px;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-card h3 {
            color: var(--maroon);
            margin: 1rem 0;
        }
        .image-showcase {
            padding: 5rem 2rem;
            background-color: var(--white);
        }
        .showcase-text {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 3rem;
        }
        .showcase-text h2 {
            color: var(--maroon);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .showcase-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .showcase-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 4/3;
        }
        .showcase-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .showcase-item:hover img {
            transform: scale(1.1);
        }
        .showcase-overlay {
            position: absolute;
            inset: 0;
            background: rgba(115, 4, 4, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .showcase-item:hover .showcase-overlay {
            opacity: 1;
        }
        .showcase-overlay h3 {
            color: var(--white);
            font-size: 1.5rem;
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
    <li><a href="indexs.php" class="active">Home</a></li>
    <li><a href="about.php">About</a></li>
    <li><a href="calendar.php">Calendar</a></li>
    <li><a href="contacts.php">Contact</a></li>
</ul>
            <div class="nav-action">
    <?php if($isLoggedIn): ?>
        <button class="portal-btn" id="userMenuBtn">
            <?php echo htmlspecialchars($userData['Username']); ?> ▾
        </button>
        <div class="user-dropdown-menu" id="userDropdown">
            <?php if(isset($userData['Role']) && $userData['Role'] === 'admin'): ?>
                <a href="admin_dashboard.php">🛠️ Admin Control Room</a>
            <?php else: ?>
                <a href="dashboard.php">👨‍🎓 My Student Dashboard</a>
            <?php endif; ?>
            
            <div class="dropdown-divider"></div>
            <a href="logout.php" style="color: #dc2626; font-weight: 600;">🚪 System Logout</a>
        </div>
    <?php else: ?>
        <a href="Login.php" class="portal-btn">Student Portal</a>
    <?php endif; ?>
</div>
        </div>
    </nav>

    <main class="main-content">
    <div class="carousel">
            <div class="list">
                <?php if (empty($articles)): ?>
                    <div class="item active" style="background-color: #f8f9fa;">
                        <div class="content">
                            <div class="title" style="color: #730404;">Welcome to CMRICTHS</div>
                            <div class="des" style="color: #333;">Empowering Future Tech Leaders. Add slides via your admin panel.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $index => $article): ?>
                    <div class="item <?php echo $index === 0 ? 'active' : ''; ?>" 
                          style="background-image: url('<?php echo htmlspecialchars($article['image']); ?>'); background-size: cover; background-position: center;">
                        
                        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Slide Background" style="width: 100%; height: 100%; object-fit: cover; position: absolute; top:0; left:0; z-index: 1;" onerror="this.style.display='none'">
                        
                        <div class="content" style="position: relative; z-index: 5;">
                            <div class="title"><?php echo htmlspecialchars($article['title']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($article['category']); ?></div>
                            <div class="des"><?php echo htmlspecialchars($article['description']); ?></div>
                            <div class="btn">
    <?php 
    // Determine target href path link
    $target_link = ($article['link'] === '#' || empty($article['link'])) ? 'article.php?id=' . $article['id'] : $article['link'];
    ?>
    <a href="<?php echo htmlspecialchars($target_link); ?>">See More</a>
</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="arrows" style="z-index: 20;">
                <button class="prev">&lt;</button>
                <button class="next">&gt;</button>
            </div>
            <div class="timeRunning" style="z-index: 20;"></div>
        </div>

        <!-- Features Section -->
        <section class="features">
        <div class="feature-card">
    <h3>Academic Excellence</h3>
    <p>Rigorous STEM curriculum fostering innovation, critical thinking, and scientific research.</p>
</div>
<div class="feature-card">
    <h3>Advanced Science & Tech Labs</h3>
    <p>Equipped with specialized physics, chemistry, biology, and robotics laboratories.</p>
</div>
<div class="feature-card">
    <h3>Competitive Culture</h3>
    <p>Proven track record of excellence in national and international science and math olympiads.</p>
</div>
<div class="feature-card">
    <h3>Expert STEM Faculty</h3>
    <p>Dedicated mentors and subject-matter specialists guiding student research and capstone projects.</p>
</div>
        </section>

        <!-- Image Showcase -->
        <section class="image-showcase">
            <div class="showcase-text">
                <h2>Life at ACSCI</h2>
                <p>Experience excellence in ICT education with our state-of-the-art facilities and dynamic learning environment.</p>
            </div>
            <div class="showcase-grid">
                <div class="showcase-item">
                    <img src="comlab.jpg" alt="Computer Laboratory" onerror="this.src='https://placehold.co/600x400?text=Computer+Lab'">
                    <div class="showcase-overlay">
                        <h3>Modern Computer Labs</h3>
                    </div>
                </div>
                <div class="showcase-item">
                    <img src="student.jpg" alt="Student Activities" onerror="this.src='https://placehold.co/600x400?text=Student+Life'">
                    <div class="showcase-overlay">
                        <h3>Student Life</h3>
                    </div>
                </div>
                <div class="showcase-item">
                    <img src="event.jpg" alt="School Events" onerror="this.src='https://placehold.co/600x400?text=Events'">
                    <div class="showcase-overlay">
                        <h3>School Events</h3>
                    </div>
                </div>
                <div class="showcase-item">
                    <img src="room.jpg" alt="School Facilities" onerror="this.src='https://placehold.co/600x400?text=Facilities'">
                    <div class="showcase-overlay">
                        <h3>Facilities</h3>
                    </div>
                </div>
            </div>
        </section>
    </main>

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

    <!-- INTERACTIVE JAVASCRIPT REGISTRY Engines -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Interactive Dropdown Trigger Configuration Engine
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');

            if (userMenuBtn && userDropdown) {
                userMenuBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });

                // Dismiss dropdown view framework if administrative operator clicks outside of panel area boundaries
                document.addEventListener('click', function (e) {
                    if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('show');
                    }
                });
            }

            // Carousel Slide Progression Logic Engine
            const items = document.querySelectorAll('.list .item');
            const prevBtn = document.querySelector('.prev');
            const nextBtn = document.querySelector('.next');
            const bar = document.querySelector('.timeRunning');
            const carousel = document.querySelector('.carousel');
            if (!items.length) return;
            const DURATION = 5000; 
            let current = 0;
            let timer = null;

            function goTo(index) {
                items[current].classList.remove('active');
                current = (index + items.length) % items.length;
                items[current].classList.add('active');
                resetBar();
            }
            function goNext() { goTo(current + 1); }
            function goPrev() { goTo(current - 1); }

            function resetBar() {
                bar.style.transition = 'none';
                bar.style.width = '0%';
                bar.offsetWidth; 
                bar.style.transition = 'width ' + DURATION + 'ms linear';
                bar.style.width = '100%';
            }
            function startAuto() {
                clearInterval(timer);
                timer = setInterval(goNext, DURATION);
                resetBar();
            }
            if(nextBtn) nextBtn.addEventListener('click', function () { goNext(); startAuto(); });
            if(prevBtn) prevBtn.addEventListener('click', function () { goPrev(); startAuto(); });
            startAuto();
        });
    </script>
</body>
</html>