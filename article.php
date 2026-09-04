<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$isLoggedIn = false;
$userData = null;

// Check user session for the dynamic navbar button
if(!empty($_SESSION["User_ID"]) && isset($conn)){
    $User_ID = $_SESSION["User_ID"];
    $result = mysqli_query($conn, "SELECT * FROM table_user WHERE User_ID = '" . mysqli_real_escape_string($conn, $User_ID) . "'");
         
    if($result && mysqli_num_rows($result) > 0){
        $userData = mysqli_fetch_assoc($result);
        $isLoggedIn = true;
    }
}

// Fetch the specific article slide from the database
$article = null;
if (isset($_GET['id']) && isset($conn)) {
    $article_id = mysqli_real_escape_string($conn, $_GET['id']);
    $article_query = mysqli_query($conn, "SELECT * FROM articles WHERE id = '$article_id' LIMIT 1");
    if ($article_query && mysqli_num_rows($article_query) > 0) {
        $article = mysqli_fetch_assoc($article_query);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="/pic/SSLG.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article ? htmlspecialchars($article['title']) : 'Article Not Found'; ?> | ACSCI</title>
    <link rel="icon" type="image/png" href="/pic/SSLG.png">
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
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-bg); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; width: 100%; }

        /* NAVBAR */
        .navbar { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .navbar-brand { display: flex; align-items: center; text-decoration: none; }
        .navbar-brand img { width: 50px; height: 50px; margin-right: 15px; border-radius: 50%; object-fit: cover; }
        .brand-text-container { display: flex; flex-direction: column; }
        .brand-title { font-size: 20px; font-weight: 800; color: var(--maroon); line-height: 1.2; }
        .brand-subtitle { font-size: 11px; color: #666; margin-top: 2px; }
        .nav-links { display: flex; gap: 5px; align-items: center; list-style: none; }
        .nav-links a { text-decoration: none; color: #4a4a4a; font-weight: 500; font-size: 14px; padding: 8px 16px; border-radius: 4px; transition: all 0.2s; }
        .nav-links a:hover { color: var(--maroon); }
        .portal-btn { background-color: var(--maroon); color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; font-size: 14px; display: inline-block; cursor: pointer; border: none; }

        /* USER DROPDOWN */
        .nav-action { position: relative; }
        .user-dropdown-menu { position: absolute; top: 120%; right: 0; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.08); width: 220px; display: none; flex-direction: column; padding: 8px 0; z-index: 1010; }
        .user-dropdown-menu.show { display: flex; }
        .user-dropdown-menu a { padding: 12px 20px; text-decoration: none; color: #334155; font-size: 14px; font-weight: 500; display: flex; align-items: center; }
        .user-dropdown-menu a:hover { background: #f8fafc; color: var(--maroon); }
        .dropdown-divider { height: 1px; background: #e2e8f0; margin: 6px 0; }

        /* ARTICLE LAYOUT */
        .article-wrapper { padding: 60px 0; flex-grow: 1; }
        .article-card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid var(--border); overflow: hidden; }
        .article-hero-img { width: 100%; height: 450px; object-fit: cover; background-color: #eaeaea; display: block; }
        .article-body { padding: 40px; }
        .article-category { display: inline-block; background: #fff0f0; color: var(--maroon); font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 4px 12px; border-radius: 6px; border: 1px solid #ffe2e2; margin-bottom: 15px; }
        .article-title { font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 20px; line-height: 1.3; }
        .article-text { color: var(--text-light); font-size: 16px; line-height: 1.8; white-space: pre-line; }
        
        /* ERROR NOTIFICATION PANEL */
        .error-card { background: #ffffff; border-radius: 12px; padding: 50px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border-top: 4px solid var(--maroon); }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 24px; background: var(--maroon); color: #fff; text-decoration: none; font-weight: 600; border-radius: 6px; }

        /* FOOTER */
        footer { background-color: var(--maroon); color: #ffffff; padding: 40px 0 20px; margin-top: 40px; }
        .footer-bottom { text-align: center; font-size: 13px; opacity: 0.8; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-inner">
            <a href="indexs.php" class="navbar-brand">
                <img src="/pic/SSLG.png" alt="ACSci Logo" onerror="this.src='https://placehold.co/50x50?text=Logo'">
                <div class="brand-text-container">
                    <span class="brand-title">ACSCI</span>
                    <span class="brand-subtitle">Angeles City Science High School</span>
                </div>
            </a>
            <ul class="nav-links">
                <li><a href="aksayentista.works">Home</a></li>
                <li><a href="/about/">About</a></li>
                 <li><a href="/calendar/">Calendar</a></li>
                <li><a href="/contacts/">Contact</a></li>
            </ul>
            <div class="nav-action">
                <?php if($isLoggedIn): ?>
                    <button class="portal-btn" id="userMenuBtn"><?php echo htmlspecialchars($userData['Username']); ?> ▾</button>
                    <div class="user-dropdown-menu" id="userDropdown">
                        <a href="admin_dashboard.php">🛠️ Admin Dashboard</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" style="color: #dc2626; font-weight: 600;">🚪 System Logout</a>
                    </div>
                <?php else: ?>
                    <a href="Login.php" class="portal-btn">Student Portal</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container article-wrapper">
        <?php if ($article): ?>
            <article class="article-card">
                <?php if (!empty($article['image'])): ?>
                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Article Image" class="article-hero-img" onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="article-body">
                    <span class="article-category"><?php echo htmlspecialchars($article['category']); ?></span>
                    <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                    <div class="article-text">
                        <?php echo htmlspecialchars($article['description']); ?>
                    </div>
                    <a href="indexs.php" class="btn-back" style="background-color: #666; margin-top: 30px;">← Back to Home</a>
                </div>
            </article>
        <?php else: ?>
            <div class="error-card">
                <h2 style="color: var(--maroon);">Article Not Found</h2>
                <p style="color: #666; margin-top: 10px;">The requested announcement article does not exist or has been deleted by an administrator.</p>
                <a href="indexs.php" class="btn-back">Return Home</a>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> ACSCI SSLG. All rights reserved.
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
