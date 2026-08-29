<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists('config.php')) { 
    require 'config.php'; 
} elseif (file_exists('Config.php')) { 
    require 'Config.php'; 
}

// 1. TEMPORARY MASTER BYPASS
if (isset($_SESSION["User_ID"]) || TRUE) { 
    $User_ID = 1; 
    $is_admin_bypass = true; 
}
if (!empty($_SESSION["User_ID"])) {
    $User_ID = $_SESSION["User_ID"];
}

// Safe Account Reader
$admin_check = mysqli_query($conn, "SELECT * FROM table_user WHERE Username='josh_ocampo' LIMIT 1");
if ($admin_check && mysqli_num_rows($admin_check) > 0) {
    $adminData = mysqli_fetch_assoc($admin_check);
} else {
    $adminData = ['Username' => 'josh_ocampo_local'];
}

// Global Message Status Trackers
$update_msg = "";
$error_msg = "";

// Ensure articles table container exists securely
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `image` TEXT NOT NULL,
    `link` VARCHAR(255) DEFAULT '#',
    `sort_order` INT DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conn, "ALTER TABLE `articles` MODIFY `image` TEXT NOT NULL;");

// NEW: Ensure projects table exists for the Calendar
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` VARCHAR(100) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// NEW: Handle Admin Adding a Project
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_project'])) {
    $p_title = mysqli_real_escape_string($conn, $_POST['project_title']);
    $p_desc = mysqli_real_escape_string($conn, $_POST['project_desc']);
    $p_status = mysqli_real_escape_string($conn, $_POST['project_status']);
    $p_start = mysqli_real_escape_string($conn, $_POST['start_date']);
    $p_end = mysqli_real_escape_string($conn, $_POST['end_date']);

    $insert_proj = "INSERT INTO projects (title, description, status, start_date, end_date) VALUES ('$p_title', '$p_desc', '$p_status', '$p_start', '$p_end')";
    if(mysqli_query($conn, $insert_proj)) {
        $update_msg = "Project successfully added to the calendar!";
    } else {
        $error_msg = "Failed to add project.";
    }
}

// NEW: Handle Admin Updating a Project Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_project_status'])) {
    $p_id = intval($_POST['project_id']);
    $p_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    if(mysqli_query($conn, "UPDATE projects SET status='$p_status' WHERE id='$p_id'")) {
        $update_msg = "Project status successfully updated!";
    } else {
        $error_msg = "Failed to update project status.";
    }
}

// Handle Admin Updating a User's Office and Position
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_office_role'])) {
    $edit_user_id = mysqli_real_escape_string($conn, $_POST['edit_user_id']);
    $new_office = mysqli_real_escape_string($conn, $_POST['new_office']);
    $new_position = mysqli_real_escape_string($conn, $_POST['new_position']);
    
    $update_query = "UPDATE table_user SET office='$new_office', position='$new_position' WHERE User_ID='$edit_user_id'";
    if(mysqli_query($conn, $update_query)) {
        $update_msg = "Successfully updated the Office and Position for User #$edit_user_id!";
    } else {
        $error_msg = "Failed to update user office/role.";
    }
}

// Handle Account Expiration Configurations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_config'])) {
    $value = intval($_POST['expiry_value']);
    $unit = mysqli_real_escape_string($conn, $_POST['expiry_unit']);
    $final_string = "$value $unit";
    
    mysqli_query($conn, "INSERT INTO table_config (config_key, config_value) VALUES ('verification_expiry_limit', '$final_string') 
                         ON DUPLICATE KEY UPDATE config_value='$final_string'");
    $update_msg = "Verification code lifetime successfully updated to: <strong>$final_string(s)</strong>!";
}

// Handle Carousel Article Slide Maker Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slide'])) {
    $title = mysqli_real_escape_string($conn, $_POST['headline_title']);
    
    if ($_POST['category'] === 'Other' && !empty($_POST['custom_category'])) {
        $category = mysqli_real_escape_string($conn, $_POST['custom_category']);
    } else {
        $category = mysqli_real_escape_string($conn, $_POST['category']);
    }
    
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image = mysqli_real_escape_string($conn, $_POST['bg_image']);
    $link = mysqli_real_escape_string($conn, $_POST['link_url']);
    $order = intval($_POST['sort_order']);

    if (empty($title) || empty($description)) {
        $error_msg = "Headline/Title and Description details are required fields.";
    } else {
        $slide_query = "INSERT INTO articles (title, category, description, image, link, sort_order) 
                        VALUES ('$title', '$category', '$description', '$image', '$link', '$order')";
        if (mysqli_query($conn, $slide_query)) {
            $update_msg = "New carousel landing slide successfully pushed live!";
        } else {
            $error_msg = "Failed to store slide metadata details.";
        }
    }
}

// Handle Administrative Action Links 
if (isset($_GET['action'])) {
    $target_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    if ($_GET['action'] === 'verify') {
        mysqli_query($conn, "UPDATE table_user SET is_verified = 1, verification_code = NULL WHERE User_ID = '$target_id'");
        header("Location: admin_dashboard.php?tab=accounts");
        exit();
    } elseif ($_GET['action'] === 'expire') {
        mysqli_query($conn, "UPDATE table_user SET code_created_at = '2000-01-01 00:00:00' WHERE User_ID = '$target_id'");
        header("Location: admin_dashboard.php?tab=accounts");
        exit();
    } elseif ($_GET['action'] === 'delete' && $target_id != $User_ID) {
        mysqli_query($conn, "DELETE FROM table_user WHERE User_ID = '$target_id'");
        header("Location: admin_dashboard.php?tab=accounts");
        exit();
    } elseif ($_GET['action'] === 'delete_slide') {
        mysqli_query($conn, "DELETE FROM articles WHERE id = '$target_id'");
        header("Location: admin_dashboard.php?tab=slides");
        exit();
    } elseif ($_GET['action'] === 'delete_project') {
        mysqli_query($conn, "DELETE FROM projects WHERE id = '$target_id'");
        header("Location: admin_dashboard.php?tab=calendar");
        exit();
    }
}

// Read System Database Status States
$config_query = mysqli_query($conn, "SELECT config_value FROM table_config WHERE config_key='verification_expiry_limit'");
$current_expiry = (mysqli_num_rows($config_query) > 0) ? mysqli_fetch_assoc($config_query)['config_value'] : "1 hour";
preg_match('/(\d+)\s+(\w+)/', $current_expiry, $matches);
$current_val = $matches[1] ?? 1;
$current_unit = $matches[2] ?? "hour";

// Base query for tabs
$users_list = mysqli_query($conn, "SELECT * FROM table_user ORDER BY User_ID DESC");
$articles_list = mysqli_query($conn, "SELECT * FROM articles ORDER BY sort_order ASC, id ASC");
$projects_list = mysqli_query($conn, "SELECT * FROM projects ORDER BY start_date ASC");

// Directory Filter Query
$office_filter = isset($_GET['filter_office']) ? mysqli_real_escape_string($conn, $_GET['filter_office']) : '';
$directory_sql = "SELECT * FROM table_user";
if (!empty($office_filter)) {
    $directory_sql .= " WHERE office = '$office_filter'";
}
$directory_sql .= " ORDER BY User_ID DESC";
$directory_list = mysqli_query($conn, $directory_sql);

// Track active navigational UI context window view state
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ACSCI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --maroon: #800000;
            --white: #ffffff;
            --light-bg: #f4f6f9;
            --text-dark: #333333;
            --text-light: #555555;
            --border: #e2e8f0;
        }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-bg); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }
        
        /* NAVBAR */
        .navbar { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; width: 100%; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; }
        .navbar-brand { display: flex; align-items: center; text-decoration: none; }
        .navbar-brand img { width: 45px; height: 45px; margin-right: 12px; border-radius: 50%; object-fit: cover; }
        .brand-title { font-size: 18px; font-weight: 800; color: var(--maroon); line-height: 1.2; }
        .brand-subtitle { font-size: 11px; color: #666; }
        .portal-btn { background-color: var(--maroon); color: #ffffff; text-decoration: none; padding: 8px 20px; border-radius: 4px; font-weight: 600; font-size: 13px; }
        
        /* DOUBLE COLUMN SIDEBAR CONTAINER GRID MAP */
        .dashboard-layout { display: flex; flex-grow: 1; min-height: calc(100vh - 69px); }
        
        /* SIDEBAR INTERFACE NAVBAR */
        .sidebar { width: 280px; background: #ffffff; border-right: 1px solid var(--border); padding: 30px 15px; display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
        .sidebar-btn { display: flex; align-items: center; gap: 12px; padding: 14px 18px; color: #475569; font-weight: 600; font-size: 14px; text-decoration: none; border-radius: 8px; transition: all 0.2s; }
        .sidebar-btn:hover { background: #f8fafc; color: var(--maroon); }
        .sidebar-btn.active { background: #fff0f0; color: var(--maroon); border-left: 4px solid var(--maroon); border-radius: 0 8px 8px 0; }
        .sidebar-divider { height: 1px; background: var(--border); margin: 15px 0; }
        
        /* MAIN CONTENT VIEW PANE */
        .main-view-pane { flex-grow: 1; padding: 40px; overflow-y: auto; }
        .view-title { font-size: 26px; font-weight: 800; color: #1e293b; margin-bottom: 25px; }
        
        /* CARD ARCHITECTURE PANELS */
        .panel-card { background: var(--white); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01); border: 1px solid var(--border); margin-bottom: 30px; }
        .panel-card h3 { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* ALERTS STYLES */
        .alert { padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 25px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        
        /* DYNAMIC FORM GRID STRUCTURING */
        .form-row-twin { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; width: 100%; }
        .form-group label { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .form-group input, .form-group textarea, .form-group select { padding: 11px 16px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: #f8fafc; outline: none; width: 100%; }
        .form-group input:focus, .form-group textarea:focus { border-color: #cbd5e1; background: #fff; }
        
        .btn-action { background: var(--maroon); color: var(--white); border: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-block; }
        .btn-action:hover { opacity: 0.9; }
        .btn-small { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        
        /* DATA STORAGE TABLE INTERFACES */
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background-color: #f1f5f9; color: #475569; font-weight: 600; padding: 14px 16px; border-bottom: 2px solid var(--border); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); color: #334155; vertical-align: top; }
        tr:hover td { background-color: #f8fafc; }
        
        /* STATE BADGES */
        .badge { display: inline-block; padding: 4px 12px; font-size: 12px; font-weight: 600; border-radius: 20px; text-transform: capitalize; }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-expired { background-color: #ffeeec; color: #dc2626; border: 1px solid #fecaca; }
        .badge-admin { background-color: #e0f2fe; color: #0369a1; }
        .badge-category { background-color: #ffe4e6; color: #9f1239; border-radius: 6px; }
        
        /* PROJECT STATUS BADGES */
        .badge-proposal { background-color: #e0f2fe; color: #0369a1; }
        .badge-signing { background-color: #fef3c7; color: #b45309; }
        .badge-making { background-color: #ffedd5; color: #c2410c; }
        .badge-ongoing { background-color: #f3e8ff; color: #7e22ce; }
        .badge-done { background-color: #dcfce7; color: #15803d; }

        .table-actions a { text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 15px; }
        .act-verify { color: #16a34a; }
        .act-expire { color: #ea580c; }
        .act-delete { color: #dc2626; }
        .act-disabled { color: #94a3b8; pointer-events: none; }
        
        .slide-preview-thumb { width: 70px; height: 45px; object-fit: cover; border-radius: 4px; background: #e2e8f0; border: 1px solid var(--border); }
        
        /* FOOTER BRACKET */
        footer { background-color: var(--maroon); color: #ffffff; padding: 25px 0; text-align: center; font-size: 13px; border-top: 1px solid rgba(0,0,0,0.1); margin-top: auto; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="indexs.php" class="navbar-brand">
                <img src="logo.png" alt="ACSci Logo" onerror="this.src='https://placehold.co/50x50?text=Logo'">
                <div class="brand-text-container">
                    <span class="brand-title">ACSCI Control Room</span>
                    <span class="brand-subtitle">Angeles City Science High School</span>
                </div>
            </a>
            <div class="nav-action">
                <a href="#" class="portal-btn">Admin: <?php echo htmlspecialchars($adminData['Username']); ?></a>
            </div>
        </div>
    </nav>

    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <a href="admin_dashboard.php?tab=accounts" class="sidebar-btn <?php echo ($active_tab === 'accounts') ? 'active' : ''; ?>">
                <span>  System Accounts</span>
            </a>
            <a href="admin_dashboard.php?tab=directory" class="sidebar-btn <?php echo ($active_tab === 'directory') ? 'active' : ''; ?>">
                <span>  User Directory</span>
            </a>
            <!-- NEW CALENDAR TAB -->
            <a href="admin_dashboard.php?tab=calendar" class="sidebar-btn <?php echo ($active_tab === 'calendar') ? 'active' : ''; ?>">
                <span>📅 Project Calendar</span>
            </a>
            <a href="admin_dashboard.php?tab=slides" class="sidebar-btn <?php echo ($active_tab === 'slides') ? 'active' : ''; ?>">
                <span>  Carousel Slide Maker</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="/" class="sidebar-btn">  Main Website</a>
            <a href="/calendar/" class="sidebar-btn">📅 View Calendar</a>
            <a href="/about/" class="sidebar-btn">  About Page</a>
            <a href="/contacts/" class="sidebar-btn">  Contact Center</a>
            <a href="/logout/" class="sidebar-btn" style="color: #dc2626; margin-top: auto;">  System Logout</a>
        </aside>

        <main class="main-view-pane">
            
            <?php if(!empty($update_msg)): ?>
                <div class="alert alert-success"><?php echo $update_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- TAB 1: ACCOUNTS -->
            <?php if ($active_tab === 'accounts'): ?>
                <h2 class="view-title">System Settings & Accounts</h2>
                
                <?php if (defined('MASTER_VERIFICATION_CODE')): ?>
                <div style="background: #fff; border-left: 5px solid #800000; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.01); border: 1px solid var(--border); border-left-width: 5px;">
                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.1rem; font-weight: 700;">  Registration Gatekeeper Code</h3>
                    <p style="margin: 0; color: #475569; font-size: 0.95rem;">
                        Active Access Passkey: 
                        <strong style="color: #800000; font-size: 1.2rem; background: #fff0f0; padding: 4px 10px; border-radius: 6px; border: 1px dashed #800000; font-family: monospace; margin-left: 5px;">
                            <?php echo MASTER_VERIFICATION_CODE; ?>
                        </strong>
                    </p>
                    <small style="color: #94a3b8; display: block; margin-top: 8px; font-size: 0.85rem;">
                        * Give this token passcode manually to authorized registrants to bypass form creation barriers.
                    </small>
                </div>
                <?php endif; ?>

                <div class="panel-card">
                    <h3>  Code Expiration Settings</h3>
                    <form style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;" action="admin_dashboard.php?tab=accounts" method="POST">
                        <input type="number" name="expiry_value" value="<?php echo $current_val; ?>" min="1" required style="width: 120px; padding: 10px 16px; border: 1px solid var(--border); border-radius: 6px;">
                        <select name="expiry_unit" required style="width: 150px; padding: 10px 16px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="minute" <?php if($current_unit == 'minute' || $current_unit == 'minutes') echo 'selected'; ?>>Minute(s)</option>
                            <option value="hour" <?php if($current_unit == 'hour' || $current_unit == 'hours') echo 'selected'; ?>>Hour(s)</option>
                            <option value="day" <?php if($current_unit == 'day' || $current_unit == 'days') echo 'selected'; ?>>Day(s)</option>
                        </select>
                        <button type="submit" name="save_config" class="btn-action" style="padding: 10px 24px;">Update Limit</button>
                    </form>
                </div>

                <div class="panel-card">
                    <h3>  Registered Accounts Registry</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email Address</th>
                                    <th>Account Role</th>
                                    <th>Status</th>
                                    <th>Management Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($users_list, 0); 
                                while($row = mysqli_fetch_assoc($users_list)): 
                                ?>
                                <?php 
                                $status_badge = "";
                                $is_expired = false;
                                if ($row['is_verified'] == 1) {
                                    $status_badge = '<span class="badge badge-success">Verified</span>';
                                } else {
                                    if (!empty($row['code_created_at'])) {
                                        $createdAt = strtotime($row['code_created_at']);
                                        $expiresAt = strtotime("+$current_expiry", $createdAt);
                                        $timeLeft = $expiresAt - time();

                                        if ($timeLeft <= 0) {
                                            $is_expired = true;
                                            $status_badge = '<span class="badge badge-expired">Expired</span>';
                                        } else {
                                            $minutesLeft = ceil($timeLeft / 60);
                                            $status_badge = '<span class="badge badge-pending">Pending (' . $minutesLeft . 'm left)</span>';
                                        }
                                    } else {
                                        $status_badge = '<span class="badge badge-pending">Pending Code</span>';
                                    }
                                }
                                ?>
                                <tr>
                                    <td>#<?php echo $row['User_ID']; ?></td>
                                    <td style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($row['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($row['Role'] === 'admin') ? 'badge-admin' : 'badge-success'; ?>">
                                            <?php echo strtoupper($row['Role'] ? $row['Role'] : 'USER'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td class="table-actions">
                                        <?php if($row['is_verified'] == 0): ?>
                                            <a href="admin_dashboard.php?action=verify&id=<?php echo $row['User_ID']; ?>" class="act-verify" onclick="return confirm('Directly verify this profile manual override?')">Activate</a>
                                            <?php if(!$is_expired): ?>
                                                <a href="admin_dashboard.php?action=expire&id=<?php echo $row['User_ID']; ?>" class="act-expire" onclick="return confirm('Force expire verification timestamp parameter window loop legacy?')">Expire Early</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="act-disabled" style="margin-right: 15px; color: #cbd5e1;">Active</span>
                                        <?php endif; ?>

                                        <?php if($row['User_ID'] != $User_ID): ?>
                                            <a href="admin_dashboard.php?action=delete&id=<?php echo $row['User_ID']; ?>" class="act-delete" onclick="return confirm('Permanently wipe this user profile account details permanently?')">Delete</a>
                                        <?php else: ?>
                                            <span class="act-disabled" style="color: #cbd5e1;">Self Account</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>


            <!-- TAB 2: USER DIRECTORY -->
            <?php if ($active_tab === 'directory'): ?>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
                    <h2 class="view-title" style="margin-bottom: 0;">User Directory & Roles</h2>
                    
                    <form action="admin_dashboard.php" method="GET" style="display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="tab" value="directory">
                        <label style="font-weight: 600; font-size: 14px; color: #475569;">Filter Office:</label>
                        <select name="filter_office" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; outline: none;">
                            <option value="">All Offices (Show Everyone)</option>
                            <?php 
                            $filter_options = [
                                "Office of the President", "Office of the Vice President", 
                                "Office of the Secretary", "Finance Office", 
                                "Public Information Office", "Protocol Office", 
                                "Creatives Department", "Production Department"
                            ];
                            foreach($filter_options as $opt) {
                                $selected = ($office_filter === $opt) ? 'selected' : '';
                                echo "<option value=\"$opt\" $selected>$opt</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>

                <div class="panel-card">
                    <h3>📇 Detailed Student & Staff Information</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Full Name (Nickname)</th>
                                    <th style="min-width: 250px;">Office & Position</th>
                                    <th>Section</th>
                                    <th>Age / Bday</th>
                                    <th>Contact Info</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(mysqli_num_rows($directory_list) > 0):
                                    while($dir_row = mysqli_fetch_assoc($directory_list)): 
                                ?>
                                <tr>
                                    <td>#<?php echo $dir_row['User_ID']; ?></td>
                                    <td style="font-weight: 600; color: #0f172a;">
                                        <?php echo !empty($dir_row['full_name']) ? htmlspecialchars($dir_row['full_name']) : htmlspecialchars($dir_row['Username']); ?>
                                        <?php if(!empty($dir_row['nickname'])): ?>
                                            <br><span style="font-size: 12px; color: #64748b; font-weight: 400;">"<?php echo htmlspecialchars($dir_row['nickname']); ?>"</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if(isset($_GET['edit_id']) && $_GET['edit_id'] == $dir_row['User_ID']): ?>
                                            <form method="POST" action="admin_dashboard.php?tab=directory<?php echo !empty($office_filter) ? '&filter_office='.urlencode($office_filter) : ''; ?>" style="display:flex; flex-direction:column; gap:8px;">
                                                <input type="hidden" name="edit_user_id" value="<?php echo $dir_row['User_ID']; ?>">
                                                
                                                <select name="new_office" id="editOfficeSelect_<?php echo $dir_row['User_ID']; ?>" onchange="updateEditPositions(<?php echo $dir_row['User_ID']; ?>)" style="padding:6px; border:1px solid var(--border); border-radius:4px; font-size:13px; width:100%;">
                                                    <option value="">No Office</option>
                                                    <?php 
                                                    foreach($filter_options as $opt) {
                                                        $selected = ($dir_row['office'] === $opt) ? 'selected' : '';
                                                        echo "<option value=\"$opt\" $selected>$opt</option>";
                                                    }
                                                    ?>
                                                </select>

                                                <select name="new_position" id="editPositionSelect_<?php echo $dir_row['User_ID']; ?>" style="padding:6px; border:1px solid var(--border); border-radius:4px; font-size:13px; width:100%;">
                                                    <option value="">No Position</option>
                                                    <?php 
                                                    $curr_office = $dir_row['office'];
                                                    $curr_pos = $dir_row['position'];
                                                    
                                                    $opOvpRoles = ["Head", "Chief of Staff", "Deputy Chief of Staff", "Chief Executive Officer", "Project Management Officer", "Creations and Quality Officer", "Communications Officer", "Human Resources Manager", "Chief Information Manager", "Operations Director"];
                                                    $genericRoles = ["Head", "Department Head", "Staff Member"];
                                                    
                                                    $roles_to_show = [];
                                                    if ($curr_office === "Office of the President" || $curr_office === "Office of the Vice President") {
                                                        $roles_to_show = $opOvpRoles;
                                                    } elseif (!empty($curr_office)) {
                                                        $roles_to_show = $genericRoles;
                                                    }
                                                    
                                                    foreach ($roles_to_show as $role) {
                                                        $sel = ($curr_pos === $role) ? 'selected' : '';
                                                        echo "<option value=\"$role\" $sel>$role</option>";
                                                    }
                                                    
                                                    if (!empty($curr_pos) && !in_array($curr_pos, $roles_to_show)) {
                                                        echo "<option value=\"".htmlspecialchars($curr_pos)."\" selected>".htmlspecialchars($curr_pos)." (Current)</option>";
                                                    }
                                                    ?>
                                                </select>

                                                <div style="display:flex; gap:8px; margin-top:4px;">
                                                    <button type="submit" name="update_office_role" style="background:#16a34a; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer;">Save</button>
                                                    <a href="admin_dashboard.php?tab=directory<?php echo !empty($office_filter) ? '&filter_office='.urlencode($office_filter) : ''; ?>" style="background:#64748b; color:#fff; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; text-decoration:none;">Cancel</a>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <?php echo !empty($dir_row['office']) ? htmlspecialchars($dir_row['office']) : '<span style="color:#cbd5e1; font-style:italic;">No Office Assigned</span>'; ?>
                                            <?php if(!empty($dir_row['position'])): ?>
                                                <br><span style="font-size: 13px; color: var(--maroon); font-weight: 600;"><?php echo htmlspecialchars($dir_row['position']); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo !empty($dir_row['section']) ? htmlspecialchars($dir_row['section']) : '<span style="color:#cbd5e1;">-</span>'; ?></td>
                                    
                                    <td>
                                        <?php echo !empty($dir_row['age']) ? htmlspecialchars($dir_row['age']) . ' yrs' : '<span style="color:#cbd5e1;">-</span>'; ?>
                                        <br><span style="font-size: 12px; color: #64748b;"><?php echo !empty($dir_row['birthday']) ? htmlspecialchars($dir_row['birthday']) : ''; ?></span>
                                    </td>

                                    <td>
                                        <span style="font-size: 13px; color: #475569;"><?php echo !empty($dir_row['contact_number']) ? htmlspecialchars($dir_row['contact_number']) : 'No Phone'; ?></span>
                                        <br>
                                        <?php if(!empty($dir_row['fb_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($dir_row['fb_link']); ?>" target="_blank" style="font-size: 12px; color: #3b82f6; text-decoration: none; font-weight: 500;">FB Link</a>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color:#cbd5e1;">No FB</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="table-actions">
                                        <?php if(!isset($_GET['edit_id']) || $_GET['edit_id'] != $dir_row['User_ID']): ?>
                                            <a href="admin_dashboard.php?tab=directory&edit_id=<?php echo $dir_row['User_ID']; ?><?php echo !empty($office_filter) ? '&filter_office='.urlencode($office_filter) : ''; ?>" class="btn-small">Edit Role</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile; 
                                else:
                                ?>
                                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">No user records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>


            <!-- NEW TAB 3: PROJECT CALENDAR -->
            <?php if ($active_tab === 'calendar'): ?>
                <h2 class="view-title">Project Calendar Management</h2>
                
                <div class="panel-card">
                    <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 25px;">Add New Project</h3>
                    <form action="admin_dashboard.php?tab=calendar" method="POST">
                        <div class="form-row-twin">
                            <div class="form-group">
                                <label>Project Title *</label>
                                <input type="text" name="project_title" placeholder="e.g. Intramurals 2026" required>
                            </div>
                            <div class="form-group">
                                <label>Current Status</label>
                                <select name="project_status" required>
                                    <option value="Creating the proposal">Creating the proposal</option>
                                    <option value="Signing of papers">Signing of papers</option>
                                    <option value="Making the project">Making the project</option>
                                    <option value="Ongoing project">Ongoing project</option>
                                    <option value="Project done">Project done</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Project Description *</label>
                            <textarea name="project_desc" rows="3" placeholder="Briefly describe the project goals..." required></textarea>
                        </div>

                        <div class="form-row-twin">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label>Estimated End Date *</label>
                                <input type="date" name="end_date" required>
                            </div>
                        </div>

                        <button type="submit" name="add_project" class="btn-action">Add Project to Calendar</button>
                    </form>
                </div>

                <div class="panel-card">
                    <h3>Active & Completed Projects <span style="background: var(--maroon); color: #fff; padding: 2px 8px; border-radius: 20px; font-size: 12px; margin-left: 5px;"><?php echo mysqli_num_rows($projects_list); ?></span></h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project Title & Details</th>
                                    <th>Dates</th>
                                    <th>Status Manager</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($projects_list) == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No projects added to the calendar yet.</td>
                                </tr>
                                <?php else: ?>
                                    <?php while($proj = mysqli_fetch_assoc($projects_list)): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #0f172a; font-size: 15px;"><?php echo htmlspecialchars($proj['title']); ?></strong><br>
                                            <span style="color: #64748b; font-size: 13px;"><?php echo htmlspecialchars($proj['description']); ?></span>
                                        </td>
                                        <td>
                                            <span style="font-size: 13px; font-weight: 600; color: #3b82f6;">Start:</span> <span style="font-size: 13px;"><?php echo $proj['start_date']; ?></span><br>
                                            <span style="font-size: 13px; font-weight: 600; color: var(--maroon);">End:</span> <span style="font-size: 13px;"><?php echo $proj['end_date']; ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" action="admin_dashboard.php?tab=calendar" style="display:flex; gap: 5px; align-items:center;">
                                                <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                                                <select name="new_status" style="padding: 6px; border: 1px solid var(--border); border-radius: 4px; font-size: 12px; outline: none; width: 160px;">
                                                    <?php 
                                                    $statuses = ['Creating the proposal', 'Signing of papers', 'Making the project', 'Ongoing project', 'Project done'];
                                                    foreach($statuses as $s) {
                                                        $selected = ($proj['status'] === $s) ? 'selected' : '';
                                                        echo "<option value=\"$s\" $selected>$s</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <button type="submit" name="update_project_status" style="background: #3b82f6; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">Update</button>
                                            </form>
                                        </td>
                                        <td class="table-actions">
                                            <a href="admin_dashboard.php?action=delete_project&id=<?php echo $proj['id']; ?>" class="act-delete" onclick="return confirm('Permanently remove this project from the calendar?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>


            <!-- TAB 4: SLIDES -->
            <?php if ($active_tab === 'slides'): ?>
                <h2 class="view-title">Homepage Slides Management</h2>
                
                <div class="panel-card">
                    <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 25px;">Add new slide</h3>
                    <form action="admin_dashboard.php?tab=slides" method="POST">
                        <div class="form-row-twin">
                            <div class="form-group">
                                <label>Headline / Title *</label>
                                <input type="text" name="headline_title" placeholder="e.g. Brigada Eskwela 2026" required>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" id="categorySelect" onchange="toggleCategoryInput()" required>
                                    <option value="Announcements">Announcements</option>
                                    <option value="News & Events">News & Events</option>
                                    <option value="Academic Highlights">Academic Highlights</option>
                                    <option value="Other">Other (Specify Below)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="customCategoryGroup" style="display: none;">
                            <label style="color: #800000; font-weight: bold;">Specify Custom Category *</label>
                            <input type="text" name="custom_category" id="customCategoryInput" placeholder="Enter custom category name">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Type brief description or highlights summary here..."></textarea>
                        </div>

                        <div class="form-row-twin">
                            <div class="form-group">
                                <label>Background image (path or URL)</label>
                                <input type="text" name="bg_image" placeholder="images/slide1.jpg or https://...">
                            </div>
                            <div class="form-group">
                                <label>Link (See More)</label>
                                <input type="text" name="link_url" value="#">
                            </div>
                        </div>
                        
                        <div class="form-group" style="max-width: 200px;">
                            <label>Display Sort Order</label>
                            <input type="number" name="sort_order" value="1" min="1">
                        </div>

                        <button type="submit" name="add_slide" class="btn-action">Add slide</button>
                    </form>
                </div>

                <div class="panel-card">
                    <h3>All slides <span style="background: var(--maroon); color: #fff; padding: 2px 8px; border-radius: 20px; font-size: 12px; margin-left: 5px;"><?php echo mysqli_num_rows($articles_list); ?></span></h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($articles_list) == 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No landing slides pushed to homepage slider database arrays yet.</td>
                                </tr>
                                <?php else: ?>
                                    <?php while($slide = mysqli_fetch_assoc($articles_list)): ?>
                                    <tr>
                                        <td><?php echo $slide['id']; ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($slide['image']); ?>" class="slide-preview-thumb" onerror="this.src='https://placehold.co/70x45?text=No+Img'">
                                        </td>
                                        <td style="font-weight: 600; color: #0f172a; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($slide['title']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-category"><?php echo htmlspecialchars($slide['category'] ? $slide['category'] : 'General'); ?></span>
                                        </td>
                                        <td style="font-weight: 600; color: var(--maroon);"><?php echo $slide['sort_order']; ?></td>
                                        <td class="table-actions">
                                            <a href="#" class="act-verify" style="color: #475569;">Edit</a>
                                            <a href="admin_dashboard.php?action=delete_slide&id=<?php echo $slide['id']; ?>" class="act-delete" onclick="return confirm('Permanently wipe this carousel image row frame slide from index?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <footer>
        <div class="container">
            <div>&copy; <?php echo date('Y'); ?> ACSCI SSLG. Admin Management Control Room Terminal.</div>
        </div>
    </footer>

    <script>
        function toggleCategoryInput() {
            const select = document.getElementById('categorySelect');
            const group = document.getElementById('customCategoryGroup');
            const input = document.getElementById('customCategoryInput');
            
            if (select.value === 'Other') {
                group.style.display = 'block';
                input.required = true;
                input.focus();
            } else {
                group.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }

        function updateEditPositions(userId) {
            const officeSelect = document.getElementById('editOfficeSelect_' + userId);
            const positionSelect = document.getElementById('editPositionSelect_' + userId);
            
            if (!officeSelect || !positionSelect) return;
            
            const office = officeSelect.value;
            positionSelect.innerHTML = '<option value="">No Position</option>';
            
            if (office === "Office of the President" || office === "Office of the Vice President") {
                const opOvpRoles = [
                    "Head",
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
                positionSelect.add(new Option("Head", "Head"));
                positionSelect.add(new Option("Department Head", "Department Head"));
                positionSelect.add(new Option("Staff Member", "Staff Member"));
            }
        }
    </script>
</body>
</html>
