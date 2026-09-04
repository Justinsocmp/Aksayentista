<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists('config.php')) {
    require 'config.php';
} elseif (file_exists('Config.php')) {
    require 'Config.php';
}

if (empty($_SESSION["User_ID"]) || !isset($conn)) {
    header("Location: Login.php");
    exit();
}

$User_ID = $_SESSION["User_ID"];
$user_check = mysqli_query($conn, "SELECT * FROM table_user WHERE User_ID = '" . mysqli_real_escape_string($conn, $User_ID) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($user_check);

// SECURITY GATE: Deny access if not admin and not explicitly permitted
if ($currentUser['Role'] !== 'admin' && empty($currentUser['has_eval_access'])) {
    http_response_code(403);
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>403 Access Denied</h2><p>You do not have permission to view or submit evaluations.</p><a href='indexs.php'>Return Home</a></div>");
}

$success_msg = "";
$error_msg = "";

$indicators = [
    "Reports to meetings on time",
    "Uses time efficiently",
    "Good knowledge of SSLG initiatives",
    "Organizes and works in a professional manner",
    "Willingly accepts work assignments",
    "Willingly accepts work assignments not directly related to duties",
    "Performs duties with little or no supervision",
    "Performs duties well under pressure",
    "Meets deadlines punctually",
    "Communicates clearly during meetings",
    "Communicates clearly on social media outlets",
    "Works well with team members without friction",
    "Accepts constructive criticism without unfavorable response",
    "Demonstrates effective leadership skills"
];

// Handle Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_eval'])) {
    $evaluatee_id = intval($_POST['evaluatee_id']);
    $evaluator_name = mysqli_real_escape_string($conn, $_POST['evaluator_name']);
    $assessment_date = mysqli_real_escape_string($conn, $_POST['assessment_date']);
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);
    
    $ratings = [];
    $total_sum = 0;
    $count = count($indicators);

    foreach ($indicators as $idx => $indicator) {
        $val = isset($_POST['ind_' . $idx]) ? intval($_POST['ind_' . $idx]) : 0;
        $ratings[$indicator] = $val;
        $total_sum += $val;
    }

    $final_score = round($total_sum / $count, 2);
    $ratings_json = mysqli_real_escape_string($conn, json_encode($ratings));

    $insert = mysqli_query($conn, "INSERT INTO evaluations (evaluatee_id, evaluator_name, assessment_date, ratings, total_score, comments) 
                                   VALUES ('$evaluatee_id', '$evaluator_name', '$assessment_date', '$ratings_json', '$final_score', '$comments')");

    if ($insert) {
        $success_msg = "Evaluation successfully submitted with an overall score of $final_score!";
    } else {
        $error_msg = "Failed to save evaluation.";
    }
}

// Fetch all members to evaluate
$members = mysqli_query($conn, "SELECT User_ID, full_name, Username, office, position FROM table_user ORDER BY full_name ASC");
// Fetch past evaluations
$past_evals = mysqli_query($conn, "SELECT e.*, u.full_name, u.office, u.position FROM evaluations e JOIN table_user u ON e.evaluatee_id = u.User_ID ORDER BY e.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSLG Evaluation Sheet | ACSCI</title>
    <link rel="icon" type="image/png" href="/pic/sslg-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --maroon: #800000; --white: #ffffff; --light-bg: #f8f9fa; --border: #e2e8f0; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-bg); color: #333; padding-bottom: 60px; }
        .navbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .navbar a { text-decoration: none; color: var(--maroon); font-weight: 700; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .eval-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border); overflow: hidden; }
        .eval-header { background: #a3a8b0; color: #000; padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .eval-header h2 { font-weight: 800; font-style: italic; font-size: 22px; grid-column: span 2; }
        .form-row { display: flex; flex-direction: column; gap: 4px; }
        .form-row label { font-size: 13px; font-weight: 600; }
        .form-row input, .form-row select { padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); font-size: 14px; background: #fff; }
        .eval-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .eval-table th { background: #4a5568; color: #fff; font-size: 13px; padding: 10px; text-align: center; border: 1px solid #718096; }
        .eval-table th:first-child { text-align: left; width: 45%; }
        .eval-table td { padding: 10px; border: 1px solid #cbd5e1; font-size: 13px; text-align: center; }
        .eval-table td:first-child { text-align: left; font-weight: 500; }
        .score-box { font-weight: 700; color: #0f172a; }
        .total-banner { background: #5c0909; color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; font-weight: 700; }
        .total-banner span.val { color: #f87171; font-size: 20px; }
        .comments-section { padding: 20px; }
        .comments-section h3 { font-size: 16px; margin-bottom: 10px; text-align: center; font-style: italic; }
        .comments-section textarea { width: 100%; height: 120px; padding: 12px; border: 1px solid var(--border); border-radius: 8px; outline: none; font-size: 14px; }
        .btn-submit { background: var(--maroon); color: #fff; padding: 12px 28px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: block; margin: 20px auto 0 auto; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="indexs.php">ACSCI SSLG</a>
    <div>
        <span>User: <strong><?php echo htmlspecialchars($currentUser['Username']); ?></strong></span>
        <?php if ($currentUser['Role'] === 'admin'): ?>
            <a href="admin_dashboard.php" style="margin-left: 15px;">Admin Dashboard</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <form method="POST" action="evaluations.php" class="eval-card">
        <div class="eval-header">
            <h2>SSLG Evaluation Sheet</h2>
            
            <div class="form-row">
                <label>Evaluatee (Officer / Member):</label>
                <select name="evaluatee_id" id="evalSelect" required onchange="updateOfficerDetails()">
                    <option value="">Select Student...</option>
                    <?php while ($m = mysqli_fetch_assoc($members)): ?>
                        <option value="<?php echo $m['User_ID']; ?>" 
                                data-office="<?php echo htmlspecialchars($m['office'] ?? ''); ?>" 
                                data-position="<?php echo htmlspecialchars($m['position'] ?? ''); ?>">
                            <?php echo htmlspecialchars($m['full_name'] ?: $m['Username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <label>Evaluator Name:</label>
                <input type="text" name="evaluator_name" value="<?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['Username']); ?>" required>
            </div>

            <div class="form-row">
                <label>Department / Office:</label>
                <input type="text" id="officerDept" readonly placeholder="Auto-filled">
            </div>

            <div class="form-row">
                <label>Position / Role:</label>
                <input type="text" id="officerPos" readonly placeholder="Auto-filled">
            </div>

            <div class="form-row">
                <label>Date of Assessment:</label>
                <input type="date" name="assessment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <table class="eval-table">
            <thead>
                <tr>
                    <th>Performance Indicator</th>
                    <th>Excellent = 10</th>
                    <th>Good = 8</th>
                    <th>Fair = 5</th>
                    <th>Poor = 3</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($indicators as $idx => $ind): ?>
                <tr>
                    <td><?php echo $ind; ?></td>
                    <td><input type="radio" name="ind_<?php echo $idx; ?>" value="10" checked onchange="calcTotal()"></td>
                    <td><input type="radio" name="ind_<?php echo $idx; ?>" value="8" onchange="calcTotal()"></td>
                    <td><input type="radio" name="ind_<?php echo $idx; ?>" value="5" onchange="calcTotal()"></td>
                    <td><input type="radio" name="ind_<?php echo $idx; ?>" value="3" onchange="calcTotal()"></td>
                    <td class="score-box" id="score_<?php echo $idx; ?>">10</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-banner">
            <div>Total Evaluation Score (Average)</div>
            <div>Score: <span class="val" id="totalScoreDisplay">10.00</span></div>
        </div>

        <div class="comments-section">
            <h3>Comments & Recommendation</h3>
            <textarea name="comments" placeholder="Provide constructive feedback regarding duties, conduct, and growth areas..." required></textarea>
            <button type="submit" name="submit_eval" class="btn-submit">Submit Evaluation Sheet</button>
        </div>
    </form>
</div>

<script>
function updateOfficerDetails() {
    const select = document.getElementById('evalSelect');
    const selected = select.options[select.selectedIndex];
    document.getElementById('officerDept').value = selected.getAttribute('data-office') || 'N/A';
    document.getElementById('officerPos').value = selected.getAttribute('data-position') || 'N/A';
}

function calcTotal() {
    let sum = 0;
    const totalIndicators = <?php echo count($indicators); ?>;
    for (let i = 0; i < totalIndicators; i++) {
        const radios = document.getElementsByName('ind_' + i);
        let rowScore = 0;
        for (const r of radios) {
            if (r.checked) {
                rowScore = parseInt(r.value);
                break;
            }
        }
        document.getElementById('score_' + i).innerText = rowScore;
        sum += rowScore;
    }
    const avg = (sum / totalIndicators).toFixed(2);
    document.getElementById('totalScoreDisplay').innerText = avg;
}
calcTotal();
</script>
</body>
</html>
