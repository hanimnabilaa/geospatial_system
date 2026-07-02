<?php
session_start();
require_once 'db.php';

/* =====================================================
   SECURITY CHECK
===================================================== */
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'technician') {
    header("Location: login_technician.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =====================================================
   PDPA DATA MASKING FUNCTIONS
===================================================== */
function maskPhone($phone) {
    if (!$phone) return 'N/A';
    return substr($phone, 0, 3) . '****' . substr($phone, -3);
}

function maskEmail($email) {
    if (!$email) return 'N/A';
    $parts = explode('@', $email);
    if (strlen($parts[0]) <= 3) {
        return substr($parts[0], 0, 1) . '***@' . $parts[1];
    }
    return substr($parts[0], 0, 3) . '***@' . $parts[1];
}

try {

    /* =====================================================
       GET TEAM ID & LEAD STATUS (Menggunakan MAINTENANCE_TEAM)
       KEMAS KINI: 'district' ditukar kepada 'coverage_area'
    ===================================================== */
    $teamStmt = $pdo->prepare("
        SELECT tm.team_id, tm.is_lead, t.specialist_type, t.coverage_area
        FROM team_member tm
        LEFT JOIN maintenance_team t ON tm.team_id = t.team_id
        WHERE tm.user_id = ?
    ");

    $teamStmt->execute([$user_id]);

    $teamRow = $teamStmt->fetch(PDO::FETCH_ASSOC);

    $my_team_id      = $teamRow['team_id'] ?? null;
    $is_lead         = $teamRow['is_lead'] ?? 0; // 1 = Lead, 0 = Technician Biasa
    $team_specialist = $teamRow['specialist_type'] ?? 'General';
    $team_coverage   = $teamRow['coverage_area'] ?? 'N/A';

    /* =====================================================
       GET TEAM LEAD INFO (Untuk dihubungi oleh ahli pasukan)
    ===================================================== */
    $lead_info = null;
    if ($my_team_id && !$is_lead) {
        $leadStmt = $pdo->prepare("
            SELECT u.user_name, u.user_phonenum 
            FROM team_member tm
            JOIN user u ON tm.user_id = u.user_id
            WHERE tm.team_id = ? AND tm.is_lead = 1
            LIMIT 1
        ");
        $leadStmt->execute([$my_team_id]);
        $lead_info = $leadStmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       KPI COUNTERS
    ===================================================== */
    $statuses = [1 => 'kpiPending', 2 => 'kpiProgress', 3 => 'kpiDone'];
    foreach ($statuses as $id => $varName) {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM COMPLAINT c
            JOIN WORK_ORDER wo ON c.complaint_id = wo.complaint_id
            WHERE c.status_id = ? AND wo.team_id = ?
        ");
        $countStmt->execute([$id, $my_team_id]); // FIXED: Menggunakan $id yang betul
        $$varName = $countStmt->fetchColumn();
    }

    /* =====================================================
       MAIN TASK QUERY (Ditapis mengikut Team ID)
    ===================================================== */
    // ✅ FIXED: Replaced l.district with md.district_name and added LEFT JOIN to melaka_districts
    $sql = "
        SELECT DISTINCT
            c.*,
            u.user_name, u.user_phonenum, u.user_email,
            l.address, 
            md.district_name AS district,
            wo.date_assigned
        FROM COMPLAINT c
        INNER JOIN WORK_ORDER wo ON c.complaint_id = wo.complaint_id
        LEFT JOIN USER u ON c.user_id = u.user_id
        LEFT JOIN LOCATION l ON c.location_id = l.location_id
        LEFT JOIN melaka_districts md ON l.district_id = md.district_id
        WHERE wo.team_id = :team_id
        AND c.status_id != 3
        AND NOT EXISTS (
            SELECT 1 FROM complaint_merge d WHERE d.duplicate_complaint_id = c.complaint_id
        )
        ORDER BY FIELD(c.severity, 'High', 'Medium', 'Low'), c.date_reported ASC
    ";

    $stmtTasks = $pdo->prepare($sql);
    $stmtTasks->execute(['team_id' => $my_team_id]);
    $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard | SmartCity GIS</title>
    <link rel="stylesheet" href="tech_styles.css">
</head>
<body>

<div id="toast" class="toast">✅ Work order updated successfully!</div>

<nav class="sidebar">
    <h2>SmartCity GIS</h2>
    <ul class="sidebar-menu">
        <li><a href="dashboard_tech.php" class="active">Assigned Tasks</a></li>
        <li><a href="map_view.php">Navigation Map</a></li>
        <li><a href="map_hotspots.php">Priority Hotspots</a></li>
        <li><a href="work_reports.php">Work Reports</a></li>
        <li style="margin-top: 40px;"><a href="logout_tech.php" style="color: #fca5a5;">Logout</a></li>
    </ul>
</nav>

<main class="main-content">
<header class="header">
    <div>
        <h1 style="color: var(--dark-blue);">Technician Dashboard</h1>
        <p style="color: var(--grey); font-size: 0.9rem;">
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Technician'); ?></strong>
            <?php if($my_team_id): ?>
                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 10px; font-weight: bold;">
                    TEAM #<?php echo $my_team_id; ?> (<?php echo htmlspecialchars($team_specialist . ' - ' . $team_coverage); ?>)
                </span>
                <?php if($is_lead): ?>
                    <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 5px; font-weight: bold;">TEAM LEAD</span>
                <?php endif; ?>
            <?php endif; ?>
        </p>

        <?php if($lead_info): ?>
            <div style="margin-top:10px; padding:10px; background:#e0f2fe; border-left:5px solid #0284c7; border-radius:6px; font-size:0.85rem; color:#0369a1; max-width:700px;">
                📞 <strong>Your Team Leader :</strong> <?php echo htmlspecialchars($lead_info['user_name']); ?> | 
                <a href="tel:<?php echo $lead_info['user_phonenum']; ?>" style="color:#0284c7; font-weight:bold; text-decoration:underline;">Hubungi: <?php echo $lead_info['user_phonenum']; ?></a>
            </div>
        <?php endif; ?>

      <div style="margin-top:10px; padding:12px; background:#fef3c7; border-left:5px solid #f59e0b; border-radius:6px; font-size:0.85rem; color:#92400e; max-width:700px;">
    <?php if($is_lead): ?>
        🔓 <strong>Lead Access Active:</strong> You have full access to view customer contact numbers for coordination and calling purposes.
    <?php else: ?>
        🔒 <strong>PDPA Compliance Active:</strong> Sensitive customer information is masked. Please contact your Team Lead if you require further details.
    <?php endif; ?>
</div>
</div>
</header>

<div class="stats-grid">
    <div class="stat-card pending"><h3>Pending</h3><div class="value"><?php echo sprintf("%02d", $kpiPending); ?></div></div>
    <div class="stat-card progress"><h3>In Progress</h3><div class="value"><?php echo sprintf("%02d", $kpiProgress); ?></div></div>
    <div class="stat-card done"><h3>Completed</h3><div class="value"><?php echo sprintf("%02d", $kpiDone); ?></div></div>
</div>

<div class="content-box">
    <h4>Work Orders Assigned to Your Team</h4>
    <table>
        <thead>
            <tr>
                <th>Severity</th>
                <th>Issue</th>
                <th>District</th>
                <th>Assigned Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($tasks) > 0): ?>
            <?php foreach ($tasks as $task):
                $prioClass = ($task['severity'] === 'High') ? 'bg-red' : (($task['severity'] === 'Medium') ? 'bg-yellow' : 'bg-green');
            ?>
            <tr>
                <td><span class="status-pill <?php echo $prioClass; ?>"><?php echo htmlspecialchars($task['severity'] ?? 'Low'); ?></span></td>
                <td><strong><?php echo htmlspecialchars($task['description'] ?? 'No Description'); ?></strong></td>
                <td><?php echo htmlspecialchars($task['district'] ?? 'N/A'); ?></td>
                <td><?php echo !empty($task['date_assigned']) ? date('d M, Y', strtotime($task['date_assigned'])) : 'N/A'; ?></td>
                <td>
                    <button type="button" class="btn-action" onclick='showDetails(<?php 
                        // LOGIK PDPA DYNAMIC BERDASARKAN STATUS IS_LEAD
                        $phoneData = $is_lead ? ($task["user_phonenum"] ?? "N/A") : maskPhone($task["user_phonenum"] ?? "");
                        $emailData = $is_lead ? ($task["user_email"] ?? "N/A") : maskEmail($task["user_email"] ?? "");
                        
                        echo json_encode([
                            "name" => $task["user_name"] ?? "Anonymous",
                            "phone" => $phoneData,
                            "email" => $emailData,
                            "loc" => $task["location_id"] ?? "N/A",
                            "address" => $task["address"] ?? "Address not found",
                            "district" => $task["district"] ?? "N/A",
                            "desc" => $task["description"] ?? "No description",
                            "id" => $task["complaint_id"],
                            "is_lead" => $is_lead
                        ]); 
                    ?>)'>Details</button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center; padding:30px;">No active tasks assigned to your team.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</main>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Complaint Information</h3>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="detail-row"><span class="detail-label">Customer:</span> <span id="det-name"></span></div>
            
            <div class="detail-row">
                <span class="detail-label">Phone:</span> 
                <span id="det-phone"></span>
                <span id="call-btn-container" style="margin-left: 10px;"></span>
            </div>
            
            <div class="detail-row"><span class="detail-label">Email:</span> <span id="det-email"></span></div>
            <hr style="margin:10px 0;">
            <div class="detail-row"><span class="detail-label">District:</span> <span id="det-district"></span></div>
            <div class="detail-row"><span class="detail-label">Address:</span> <span id="det-address"></span></div>
            <div class="detail-row"><span class="detail-label">Location ID:</span> <span id="det-loc"></span></div>
            <div class="detail-row" style="border:none; margin-top:10px;"><span class="detail-label">Issue Description:</span></div>
            <p id="det-desc" style="padding:10px; background:#f9fafb; border-radius:5px; border:1px solid #eee;"></p>
        </div>
        <div style="margin-top:25px; display:flex; gap:10px;">
            <a id="det-link" href="#" class="btn-action">Update Status</a>
            <button class="btn-action" style="background: var(--grey);" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
function showDetails(data) {
    document.getElementById('det-name').textContent = data.name;
    document.getElementById('det-phone').textContent = data.phone;
    document.getElementById('det-email').textContent = data.email;
    document.getElementById('det-district').textContent = data.district;
    document.getElementById('det-address').textContent = data.address;
    document.getElementById('det-loc').textContent = data.loc;
    document.getElementById('det-desc').textContent = data.desc;
    document.getElementById('det-link').href = 'update_complaint.php?id=' + data.id;

    // Ciri Tambahan: Sediakan butang klik untuk terus call jika user ialah Lead
    const callContainer = document.getElementById('call-btn-container');
    callContainer.innerHTML = ''; 
    if (data.is_lead === 1 && data.phone !== 'N/A') {
        const callBtn = document.createElement('a');
        callBtn.href = 'tel:' + data.phone;
        callBtn.textContent = '📞 Call Customer';
        callBtn.style = 'background:#15803d; color:white; padding:2px 8px; border-radius:4px; font-size:0.75rem; text-decoration:none; font-weight:bold;';
        callContainer.appendChild(callBtn);
    }

    document.getElementById('detailsModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('detailsModal')) { closeModal(); }
}

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
const toast = document.getElementById('toast');
toast.style.display = 'block';
setTimeout(() => {
    toast.style.display = 'none';
    window.history.replaceState({}, document.title, window.location.pathname);
}, 3000);
<?php endif; ?>
</script>

</body>
</html>