<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; 

$error = ""; 
$success = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($conn)) {
    // Step 1 Data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $passed_gatecode = $_POST['gatekeeper_code'];
    
    // Step 2 Data
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $nickname = mysqli_real_escape_string($conn, $_POST['nickname']);
    $age = intval($_POST['age']);
    $birthday = mysqli_real_escape_string($conn, $_POST['birthday']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $fb_link = mysqli_real_escape_string($conn, $_POST['fb_link']);
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    $office = mysqli_real_escape_string($conn, $_POST['office']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    
    if (empty($username) || empty($email) || empty($password) || empty($passed_gatecode)) {
        $error = "All core account fields, including the Registration Code, are required.";
    } elseif ($passed_gatecode !== MASTER_VERIFICATION_CODE) {
        $error = "Invalid Registration Code. You must obtain the code from the administrator first.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM table_user WHERE Username='$username' OR Email='$email'");
        
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or Email already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $timestamp = date("Y-m-d H:i:s");
            
            // Insert all data including the new office and position fields
            $query = "INSERT INTO table_user (Username, Email, Password, verification_code, is_verified, code_created_at, full_name, nickname, age, birthday, contact_number, fb_link, section, office, position)
                      VALUES ('$username', '$email', '$hashed_password', NULL, 1, '$timestamp', '$full_name', '$nickname', '$age', '$birthday', '$contact_number', '$fb_link', '$section', '$office', '$position')";
                      
            if (mysqli_query($conn, $query)) {
                $success = "Account successfully authorized and created! Redirecting to login...";
                header("Refresh: 2; url=Login.php");
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Sign Up | ACSCI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --maroon: #800000; --white: #ffffff; --light-bg: #f8f9fa; --text-dark: #333333; --text-light: #555555; }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-bg); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; width: 100%; }
        
        /* NAVBAR STYLES */
        .navbar { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .navbar-brand { display: flex; align-items: center; text-decoration: none; }
        .navbar-brand img { width: 50px; height: 50px; margin-right: 15px; border-radius: 50%; object-fit: cover; }
        .brand-text-container { display: flex; flex-direction: column; }
        .brand-title { font-size: 20px; font-weight: 800; color: var(--maroon); line-height: 1.2; }
        .brand-subtitle { font-size: 11px; font-weight: 400; color: #666; margin-top: 2px; }
        .nav-links { display: flex; gap: 5px; align-items: center; list-style: none; }
        .nav-links a { text-decoration: none; color: #4a4a4a; font-weight: 500; font-size: 14px; padding: 8px 16px; border-radius: 4px; transition: all 0.2s ease; }
        .nav-links a:hover { color: var(--maroon); }
        .portal-btn { background-color: var(--maroon); color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; font-size: 14px; display: inline-block; }
        
        /* AUTH CARD INTERFACE */
        .auth-wrapper { display: flex; justify-content: center; align-items: center; padding: 60px 0; flex-grow: 1; }
        .auth-card { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); width: 100%; max-width: 480px; border-top: 5px solid var(--maroon); }
        .auth-card h2 { font-size: 24px; color: #1e293b; margin-bottom: 25px; font-weight: 700; text-align: center; }
        
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group label { font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #94a3b8; background: #fff; }
        
        .form-row-twin { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-row-uneven { display: grid; grid-template-columns: 2fr 1fr; gap: 15px; }
        
        .btn-submit { background: var(--maroon); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; width: 100%; }
        .btn-submit:hover { background: #600000; }
        .btn-secondary { background: #64748b; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; width: 100%; }
        .btn-secondary:hover { background: #475569; }
        
        .msg { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;}
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;}
        .login-link { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-light); }
        .login-link a { color: var(--maroon); text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        
        /* FOOTER STYLES */
        footer { background-color: var(--maroon); color: #ffffff; padding: 60px 0 20px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1.5fr; gap: 40px; margin-bottom: 40px; }
        .footer-brand h2 { font-size: 20px; color: #ffffff; margin-bottom: 15px; }
        .footer-brand p { font-size: 14px; opacity: 0.9; line-height: 1.6; margin-bottom: 20px; }
        .footer-links h3, .footer-contact h3 { color: #ffffff; font-size: 18px; margin-bottom: 20px; }
        .footer-links ul { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #ffffff; text-decoration: none; font-size: 14px; opacity: 0.9; }
        .footer-links a:hover { text-decoration: underline; }
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
                <li><a href="/">Home</a></li>
                <li><a href="/about/">About</a></li>
                <li><a href="/Calendar/">Calendar</a></li>
                <li><a href="/contacts/">Contact</a></li>
            </ul>
            <div class="nav-action">
                <a href="Login.php" class="portal-btn">Student Portal</a>
            </div>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2 id="formTitle">Create Account</h2>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="signup.php" method="POST" id="registrationForm">
                
                <!-- STEP 1: Account Credentials -->
                <div id="step1">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label style="color: #800000; font-weight: bold;">Admin Registration Code</label>
                        <input type="text" id="gatekeeper_code" name="gatekeeper_code" placeholder="Ask Admin for the code" required>
                    </div>
                    
                    <button type="button" class="btn-submit" onclick="goToStep2()">Next: Personal Details</button>
                    
                    <div class="login-link">
                        Already have an account? <a href="Login.php">Log In</a>
                    </div>
                </div>

                <!-- STEP 2: Personal Input Directory -->
                <div id="step2" style="display: none;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="First Last">
                    </div>
                    
                    <div class="form-row-uneven">
                        <div class="form-group">
                            <label>Nickname</label>
                            <input type="text" name="nickname">
                        </div>
                        <div class="form-group">
                            <label>Age</label>
                            <input type="number" name="age" min="10" max="99">
                        </div>
                    </div>

                    <div class="form-row-twin">
                        <div class="form-group">
                            <label>Birthday</label>
                            <input type="date" name="birthday">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" placeholder="09XX-XXX-XXXX">
                        </div>
                    </div>

                    <div class="form-row-twin">
                        <div class="form-group">
                            <label>Office / Department</label>
                            <select name="office" id="officeSelect" onchange="updatePositions()">
                                <option value="">Select Office...</option>
                                <option value="Office of the President">Office of the President</option>
                                <option value="Office of the Vice President">Office of the Vice President</option>
                                <option value="Office of the Secretary">Office of the Secretary</option>
                                <option value="Finance Office">Finance Office</option>
                                <option value="Public Information Office">Public Information Office</option>
                                <option value="Protocol Office">Protocol Office</option>
                                <option value="Creatives Department">Creatives Department</option>
                                <option value="Production Department">Production Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Position / Role</label>
                            <select name="position" id="positionSelect">
                                <option value="">Select Position...</option>
                                <!-- Populated dynamically by JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="form-row-twin">
                        <div class="form-group">
                            <label>Current Section</label>
                            <input type="text" name="section" placeholder="e.g., 11-Mendel">
                        </div>
                        <div class="form-group">
                            <label>Facebook Link</label>
                            <input type="url" name="fb_link" placeholder="https://facebook.com/...">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Complete Sign Up</button>
                    <button type="button" class="btn-secondary" onclick="goToStep1()">Back</button>
                </div>

            </form>
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
                    <div class="contact-item"><span class="contact-icon"> </span><span>Dona Aurora, Claro M. Recto, Angeles City</span></div>
                    <div class="contact-item"><span class="contact-icon"> </span><span>(045) 887 5502</span></div>
                    <div class="contact-item"><span class="contact-icon"> </span><span>cmricthsangelescity@yahoo.com</span></div>
                </div>
            </div>
            <div class="footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> ACSCI SSLG. All rights reserved.</div>
                <div class="footer-bottom-links"><a href="#">Privacy Policy</a><a href="#">Terms of Use</a></div>
            </div>
        </div>
    </footer>

    <script>
        function goToStep2() {
            const user = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const pass = document.getElementById('password').value.trim();
            const code = document.getElementById('gatekeeper_code').value.trim();

            if (!user || !email || !pass || !code) {
                alert("Please fill in your Username, Email, Password, and the Admin Registration Code first.");
                return;
            }

            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Personal Information';
        }

        function goToStep1() {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Create Account';
        }

        // Intercept the Enter key on Step 1 so it doesn't auto-submit the form
        document.getElementById('step1').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                goToStep2(); 
            }
        });

        // Dynamic Position Populator based on Office Selection
        function updatePositions() {
            const office = document.getElementById('officeSelect').value;
            const positionSelect = document.getElementById('positionSelect');
            
            // Reset position dropdown
            positionSelect.innerHTML = '<option value="">Select Position...</option>';
            
            if (office === "Office of the President" || office === "Office of the Vice President") {
                const opOvpRoles = [
                    "Chief of Staff",
                    "Deputy Chief of Staff",
                    "Chief Executive Officer",
                    "Project Management Officer",
                    "Creations and Quality Officer",
                    "Communications Officer",
                    "Human Resources Manager",
                    "Chief Information Manager",
                    "Operations Director"
                ];
                
                opOvpRoles.forEach(role => {
                    positionSelect.add(new Option(role, role));
                });
            } else if (office !== "") {
                // Generic roles for other offices
                positionSelect.add(new Option("Department Head", "Department Head"));
                positionSelect.add(new Option("Staff Member", "Staff Member"));
            }
        }
    </script>
</body>
</html>
