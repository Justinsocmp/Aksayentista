<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists('config.php')) { 
    require 'config.php'; 
} elseif (file_exists('Config.php')) { 
    require 'Config.php'; 
}

$isLoggedIn = false;
$userData = null;

if(!empty($_SESSION["User_ID"]) && isset($conn)){
    $User_ID = $_SESSION["User_ID"];
    $result = mysqli_query($conn, "SELECT * FROM table_user WHERE User_ID = '" . mysqli_real_escape_string($conn, $User_ID) . "'");              
    if($result && mysqli_num_rows($result) > 0){
        $userData = mysqli_fetch_assoc($result);
        $isLoggedIn = true;
    }
}

// Fetch all projects and encode them to JSON so JavaScript can build the calendar grid
$projects = [];
if(isset($conn)) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'projects'");
    if($check_table && mysqli_num_rows($check_table) > 0) {
        $proj_query = mysqli_query($conn, "SELECT * FROM projects ORDER BY start_date ASC");
        if ($proj_query) {
            while ($row = mysqli_fetch_assoc($proj_query)) {
                $projects[] = $row;
            }
        }
    }
}

$projects_json = json_encode($projects);

// Helper function to colorize badges
function getStatusColor($status) {
    switch($status) {
        case 'Creating the proposal': return 'background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;';
        case 'Signing of papers': return 'background: #fef3c7; color: #b45309; border: 1px solid #fde68a;';
        case 'Making the project': return 'background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa;';
        case 'Ongoing project': return 'background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff;';
        case 'Project done': return 'background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;';
        default: return 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="/pic/SSLG.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Calendar | ACSCI</title>
    <link rel="icon" type="image/png" href="/pic/SSLG.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --maroon: #800000; --white: #ffffff; --light-bg: #f8f9fa; --text-dark: #333333; --text-light: #555555; --border: #e2e8f0; }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-bg); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; line-height: 1.6; }
        
        /* NAVBAR */
        .navbar { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .navbar-brand { display: flex; align-items: center; text-decoration: none; }
        .navbar-brand img { width: 50px; height: 50px; margin-right: 15px; border-radius: 50%; object-fit: cover; }
        .brand-text-container { display: flex; flex-direction: column; }
        .brand-title { font-size: 20px; font-weight: 800; color: var(--maroon); line-height: 1.2; }
        .brand-subtitle { font-size: 11px; font-weight: 400; color: #666; margin-top: 2px; }
        .nav-links { display: flex; gap: 5px; align-items: center; list-style: none; }
        .nav-links a { text-decoration: none; color: #4a4a4a; font-weight: 500; font-size: 14px; padding: 8px 16px; border-radius: 4px; transition: all 0.2s ease; }
        .nav-links a:hover { color: var(--maroon); }
        .nav-links a.active { background-color: #f0e6e6; color: var(--maroon); }
        .portal-btn { background-color: var(--maroon); color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; }
        .nav-action { position: relative; }
        .user-dropdown-menu { position: absolute; top: 120%; right: 0; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.08); width: 220px; display: none; flex-direction: column; padding: 8px 0; z-index: 1010; }
        .user-dropdown-menu.show { display: flex; }
        .user-dropdown-menu a { padding: 12px 20px; text-decoration: none; color: #334155; font-size: 14px; font-weight: 500; display: flex; align-items: center; }
        .user-dropdown-menu a:hover { background: #f8fafc; color: var(--maroon); }
        .dropdown-divider { height: 1px; background: #e2e8f0; margin: 6px 0; }

        /* LARGE CALENDAR UI */
        .calendar-wrapper { padding: 40px 20px; flex-grow: 1; max-width: 1400px; margin: 0 auto; width: 100%; }
        .calendar-app { background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        .cal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; border-bottom: 1px solid var(--border); }
        .cal-nav { display: flex; align-items: center; background: #f8fafc; border-radius: 8px; padding: 6px; border: 1px solid var(--border); }
        .cal-nav button { background: #fff; border: 1px solid var(--border); border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: bold; color: #1e293b; transition: background 0.2s; }
        .cal-nav button:hover:not(:disabled) { background: #f1f5f9; }
        .cal-nav h2 { margin: 0 20px; font-size: 18px; font-weight: 700; color: #1e293b; min-width: 180px; text-align: center; }
        
        .cal-grid-header { display: grid; grid-template-columns: repeat(7, 1fr); border-bottom: 1px solid var(--border); background: #fafafa; }
        .cal-day-name { text-align: left; font-weight: 600; font-size: 11px; color: #64748b; padding: 12px 15px; border-right: 1px solid var(--border); text-transform: uppercase; letter-spacing: 0.5px; }
        .cal-day-name:last-child { border-right: none; }
        
        .cal-days { display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: minmax(140px, auto); }
        .cal-cell { border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 10px; display: flex; flex-direction: column; gap: 4px; transition: background 0.2s; }
        .cal-cell:hover { background: #fcfcfc; }
        .cal-cell.empty-cell { background: #f8fafc; color: #cbd5e1; }
        .cal-cell:nth-child(7n) { border-right: none; }
        
        .cal-date { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; }
        .cal-date.today { background: #1e293b; color: #fff; }
        .cal-date.empty-date { color: #94a3b8; }
        
        /* EVENT PILLS */
        .cal-event { font-size: 11px; font-weight: 600; padding: 6px 8px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; border-left: 3px solid transparent; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .status-proposal { background: #e0f2fe; color: #0369a1; border-left-color: #0284c7; }
        .status-signing { background: #fef3c7; color: #b45309; border-left-color: #d97706; }
        .status-making { background: #ffedd5; color: #c2410c; border-left-color: #ea580c; }
        .status-ongoing { background: #f3e8ff; color: #7e22ce; border-left-color: #9333ea; }
        .status-done { background: #dcfce7; color: #15803d; border-left-color: #16a34a; }

        /* FOOTER */
        footer { background-color: var(--maroon); color: #ffffff; padding: 60px 0 20px; margin-top: auto; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1.5fr; gap: 40px; margin-bottom: 40px; }
        .footer-brand h2 { font-size: 20px; color: #ffffff; margin-bottom: 15px; }
        .footer-brand p { font-size: 14px; opacity: 0.9; line-height: 1.6; margin-bottom: 20px; }
        .footer-links h3, .footer-contact h3 { color: #ffffff; font-size: 18px; margin-bottom: 20px; }
        .footer-links ul { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #ffffff; text-decoration: none; font-size: 14px; opacity: 0.9; }
        .contact-item { display: flex; margin-bottom: 15px; font-size: 14px; opacity: 0.9; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; display: flex; justify-content: space-between; font-size: 13px; opacity: 0.8; }
        @media (max-width: 768px) { .footer-grid { grid-template-columns: 1fr; } .cal-days { grid-auto-rows: minmax(100px, auto); } }
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
                <li><a href="/">Home</a></li>
                <li><a href="/about/">About</a></li>
                <li><a href="/calendar/" class="active">Calendar</a></li>
                <li><a href="/contacts/">Contact</a></li>
            </ul>
            <div class="nav-action">
                <?php if($isLoggedIn): ?>
                    <button class="portal-btn" id="userMenuBtn"><?php echo htmlspecialchars($userData['Username']); ?></button>
                    <div class="user-dropdown-menu" id="userDropdown">
                        <?php if(isset($userData['Role']) && $userData['Role'] === 'admin'): ?>
                            <a href="admin_dashboard.php">Admin Dashboard</a>
                        <?php else: ?>
                            <a href="dashboard.php">Student Dashboard</a>
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

    <main class="calendar-wrapper">
        <div class="calendar-app">
            <div class="cal-header">
                <div class="cal-nav">
                    <!-- ADDED IDs for the buttons to control them -->
                    <button id="prevBtn" onclick="changeMonth(-1)">&lt;</button>
                    <h2 id="monthYearDisplay">Loading...</h2>
                    <button id="nextBtn" onclick="changeMonth(1)">&gt;</button>
                </div>
                <div>
                    <span style="font-size: 13px; color: var(--text-light); font-weight: 500;">Supreme Student Learner Government</span>
                </div>
            </div>
            
            <div class="cal-grid-header">
                <div class="cal-day-name">Monday</div>
                <div class="cal-day-name">Tuesday</div>
                <div class="cal-day-name">Wednesday</div>
                <div class="cal-day-name">Thursday</div>
                <div class="cal-day-name">Friday</div>
                <div class="cal-day-name">Saturday</div>
                <div class="cal-day-name">Sunday</div>
            </div>
            
            <div class="cal-days" id="calendarGrid">
                <!-- JavaScript will generate the calendar cells here -->
            </div>
        </div>
    </main>

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
                        <li><a href="aksayentista.works">Home</a></li>
                        <li><a href="/about/">About</a></li>
                        <li><a href="/calendar/">Calendar</a></li>
                        <li><a href="/contacts/">Contacts</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Information</h3>
                    <div class="contact-item"><span>Angeles City Science High School</span></div>
                    <div class="contact-item"><span>09625410980</span></div>
                </div>
            </div>
            <div class="footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> ACSCI SSLG. All rights reserved.</div>
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

        const projectsData = <?php echo $projects_json; ?>;
        
        let currentDate = new Date();
        
        // BOUNDARY CONSTANTS (Current Month to April 2027)
        const minYear = new Date().getFullYear(); 
        const minMonth = new Date().getMonth(); 
        const maxYear = 2027;
        const maxMonth = 3; // 0-indexed, so 3 is April
        
        function getStatusClass(status) {
            switch(status) {
                case 'Creating the proposal': return 'status-proposal';
                case 'Signing of papers': return 'status-signing';
                case 'Making the project': return 'status-making';
                case 'Ongoing project': return 'status-ongoing';
                case 'Project done': return 'status-done';
                default: return '';
            }
        }

        function isDateInRange(dateToCheck, startDateStr, endDateStr) {
            const check = new Date(dateToCheck.setHours(0,0,0,0));
            
            const startArr = startDateStr.split('-');
            const start = new Date(startArr[0], startArr[1]-1, startArr[2]);
            start.setHours(0,0,0,0);
            
            const endArr = endDateStr.split('-');
            const end = new Date(endArr[0], endArr[1]-1, endArr[2]);
            end.setHours(0,0,0,0);

            return check >= start && check <= end;
        }

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const today = new Date();

            // DISABLE / ENABLE BUTTONS BASED ON BOUNDARIES
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            if (year < minYear || (year === minYear && month <= minMonth)) {
                prevBtn.disabled = true;
                prevBtn.style.opacity = '0.3';
                prevBtn.style.cursor = 'not-allowed';
            } else {
                prevBtn.disabled = false;
                prevBtn.style.opacity = '1';
                prevBtn.style.cursor = 'pointer';
            }

            if (year > maxYear || (year === maxYear && month >= maxMonth)) {
                nextBtn.disabled = true;
                nextBtn.style.opacity = '0.3';
                nextBtn.style.cursor = 'not-allowed';
            } else {
                nextBtn.disabled = false;
                nextBtn.style.opacity = '1';
                nextBtn.style.cursor = 'pointer';
            }

            // Format Month Year Header
            const options = { month: 'long', year: 'numeric' };
            document.getElementById('monthYearDisplay').innerText = currentDate.toLocaleDateString('en-US', options);

            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            let firstDay = new Date(year, month, 1).getDay();
            firstDay = firstDay === 0 ? 6 : firstDay - 1;

            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            // Render Previous Month Padding Cells
            for (let i = 0; i < firstDay; i++) {
                const dayNum = daysInPrevMonth - firstDay + i + 1;
                const prevDate = new Date(year, month - 1, dayNum);
                grid.appendChild(createCell(dayNum, prevDate, true));
            }

            // Render Current Month Cells
            for (let i = 1; i <= daysInMonth; i++) {
                const curDate = new Date(year, month, i);
                const isToday = (i === today.getDate() && month === today.getMonth() && year === today.getFullYear());
                grid.appendChild(createCell(i, curDate, false, isToday));
            }

            // Render Next Month Padding Cells
            const totalCellsRendered = firstDay + daysInMonth;
            const remainingCells = (totalCellsRendered <= 35) ? 35 - totalCellsRendered : 42 - totalCellsRendered;
            
            for (let i = 1; i <= remainingCells; i++) {
                const nextDate = new Date(year, month + 1, i);
                grid.appendChild(createCell(i, nextDate, true));
            }
        }

        function createCell(dayNumber, cellDate, isEmpty, isToday = false) {
            const cell = document.createElement('div');
            cell.className = isEmpty ? 'cal-cell empty-cell' : 'cal-cell';

            const dateSpan = document.createElement('span');
            dateSpan.className = 'cal-date';
            if(isToday) dateSpan.classList.add('today');
            if(isEmpty) dateSpan.classList.add('empty-date');
            dateSpan.innerText = dayNumber;
            cell.appendChild(dateSpan);

            const eventsToday = projectsData.filter(proj => isDateInRange(new Date(cellDate.getTime()), proj.start_date, proj.end_date));
            
            eventsToday.forEach(proj => {
                const eventPill = document.createElement('div');
                eventPill.className = 'cal-event ' + getStatusClass(proj.status);
                eventPill.innerText = proj.title;
                eventPill.title = proj.title + "\n" + proj.status + "\n" + proj.description; 
                cell.appendChild(eventPill);
            });

            return cell;
        }

        function changeMonth(direction) {
            const newMonth = currentDate.getMonth() + direction;
            const testDate = new Date(currentDate.getFullYear(), newMonth, 1);
            const testYear = testDate.getFullYear();
            const testMonth = testDate.getMonth();

            // Prevent programmatic bypass of boundaries
            if (direction === -1 && (testYear < minYear || (testYear === minYear && testMonth < minMonth))) return;
            if (direction === 1 && (testYear > maxYear || (testYear === maxYear && testMonth > maxMonth))) return;

            currentDate.setMonth(currentDate.getMonth() + direction);
            renderCalendar();
        }

        // Initialize
        renderCalendar();
    </script>
</body>
</html>
