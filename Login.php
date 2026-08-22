<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($conn)) {
    $usernameOrEmail = mysqli_real_escape_string($conn, $_POST['login_input']);
    $password        = $_POST['password'];
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // 1. ULTIMATE MASTER-KEY BYPASS (Using JavaScript Redirect)
        if (($usernameOrEmail === 'josh_ocampo' || $usernameOrEmail === 'josh@example.com') && $password === 'josh2028') {
            $admin_query = mysqli_query($conn, "SELECT User_ID FROM table_user WHERE Username='josh_ocampo'");
            if (mysqli_num_rows($admin_query) > 0) {
                $admin_user = mysqli_fetch_assoc($admin_query);
                $_SESSION["User_ID"] = $admin_user['User_ID'];
            } else {
                $_SESSION["User_ID"] = 1; 
            }
            
            // Force the browser to redirect using JavaScript instead of PHP headers
            echo "<script>window.location.href='admin_dashboard.php';</script>";
            exit();
        }

        // 2. STANDARD STUDENT LOGIN FLOW
        $query = "SELECT * FROM table_user WHERE Username='$usernameOrEmail' OR Email='$usernameOrEmail'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['Password']) || $password === $user['Password']) {
                if ($user['is_verified'] == 0) {
                    $_SESSION['verify_email'] = $user['Email'];
                    $error = "Your account isn't verified yet. Redirecting to code verify panel...";
                    echo "<script>
                            setTimeout(function(){
                                window.location.href='verify.php';
                            }, 2000);
                          </script>";
                } else {
                    $_SESSION["User_ID"] = $user['User_ID'];
                    
                    // Direct Role Redirection via JavaScript
                    if (isset($user['Role']) && $user['Role'] === 'admin') {
                        echo "<script>window.location.href='admin_dashboard.php';</script>";
                    } else {
                        echo "<script>window.location.href='indexs.php';</script>";
                    }
                    exit();
                }
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ACSCI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --maroon: #800000; --white: #ffffff; --light-bg: #f8f9fa; --text-dark: #333333; --text-light: #555555; }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-bg); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; width: 100%; }

        /* NAVBAR */
        .navbar { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .navbar-brand { display: flex; align-items: center; text-decoration: none; }
        .navbar-brand img { width: 50px; height: 50px; margin-right: 15px; border-radius: 50%; object-fit: cover; }
        .brand-text-container { display: flex; flex-direction: column; }
        .brand-title { font-size: 20px; font-weight: 800; color: var(--maroon); line-height: 1.2; }
        .brand-subtitle { font-size: 11px; font-weight: 400; color: #666; margin-top: 2px; }
        .nav-links { display: flex; gap: 5px; align-items: center; list-style: none; }
        .nav-links a { text-decoration: none; color: #4a4a4a; font-weight: 500; font-size: 14px; padding: 8px 16px; border-radius: 4px; }
        .portal-btn { background-color: var(--maroon); color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; font-size: 14px; }

        /* LOGIN BOX INTERFACE */
        .auth-wrapper { display: flex; justify-content: center; align-items: center; padding: 60px 0; flex-grow: 1; }
        .auth-card { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); width: 100%; max-width: 420px; border-top: 5px solid var(--maroon); }
        .auth-card h2 { font-size: 24px; color: #1e293b; margin-bottom: 25px; font-weight: 700; text-align: center; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group label { font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #475569; }
        .form-group input { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; outline: none; }
        .form-group input:focus { border-color: #94a3b8; background: #fff; }
        .btn-submit { background: var(--maroon); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; width: 100%; text-align: center; display: block; }
        .btn-submit:hover { background: #600000; }
        .msg { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        .switch-link { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-light); }
        .switch-link a { color: var(--maroon); text-decoration: none; font-weight: 600; }
        .switch-link a:hover { text-decoration: underline; }

        /* FOOTER */
        footer { background-color: var(--maroon); color: #ffffff; padding: 60px 0 20px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1.5fr; gap: 40px; margin-bottom: 40px; }
        .footer-brand h2 { font-size: 20px; color: #ffffff; margin-bottom: 15px; }
        .footer-brand p { font-size: 14px; opacity: 0.9; line-height: 1.6; margin-bottom: 20px; }
        .footer-links h3, .footer-contact h3 { color: #ffffff; font-size: 18px; margin-bottom: 20px; }
        .footer-links ul { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #ffffff; text-decoration: none; font-size: 14px; opacity: 0.9; }
        .contact-item { display: flex; margin-bottom: 15px; font-size: 14px; opacity: 0.9; }
        .contact-icon { margin-right: 15px; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; display: flex; justify-content: space-between; font-size: 13px; opacity: 0.8; }
        .footer-bottom-links a { color: #ffffff; text-decoration: none; margin-left: 20px; }
        @media (max-width: 768px) { .footer-grid { grid-template-columns: 1fr; } .footer-bottom { flex-direction: column; text-align: center; gap: 15px; } .footer-bottom-links a { margin: 0 10px; } }
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
                <li><a href="Gallery.php">Gallery</a></li>
                <li><a href="contacts.php">Contact</a></li>
            </ul>
            <div class="nav-action">
                <a href="Login.php" class="portal-btn">Student Portal</a>
            </div>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>Student Portal Login</h2>
            
            <?php if(!empty($error)): ?> <div class="msg error"><?php echo $error; ?></div> <?php endif; ?>

            <form action="Login.php" method="POST">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="login_input" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required autocomplete="off">
                </div>
                <button type="submit" class="btn-submit">Login</button>
            </form>
            <div class="switch-link">
                Don't have an account? <a href="signup.php">Sign up here</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h2>ACSCI</h2>
                    <p>Empowering students through quality STEM education, proactive leadership, and community innovation.</p>
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
                    <div class="contact-item"><span class="contact-icon">📍</span><span>Dona Aurora, Claro M. Recto, Angeles City</span></div>
                    <div class="contact-item"><span class="contact-icon">📞</span><span>(045) 887 5502</span></div>
                    <div class="contact-item"><span class="contact-icon">✉️</span><span>cmricthsangelescity@yahoo.com</span></div>
                </div>
            </div>
            <div class="footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> CMRICTHS. All rights reserved.</div>
                <div class="footer-bottom-links"><a href="#">Privacy Policy</a><a href="#">Terms of Use</a></div>
            </div>
        </div>
    </footer>
</body>
</html>