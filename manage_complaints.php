<?php
session_start();
require_once 'db.php';

// 1. Security Check
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$selected_month = isset($_GET['month']) ? $_GET['month'] : '';

// --- UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint'])) {
    $cid = $_POST['complaint_id'];
    $sev = $_POST['severity'];
    $status = $_POST['status_id'];
    $remarks = $_POST['completion_remarks'];
    $date_resolved = ($status == 3) ? date('Y-m-d H:i:s') : null;
    
    try {
        $updateSql = "UPDATE complaint SET severity = ?, status_id = ?, completion_remarks = ?, date_resolved = ? WHERE complaint_id = ?";
        $pdo->prepare($updateSql)->execute([$sev, $status, $remarks, $date_resolved, $cid]);
        header("Location: manage_complaints.php?msg=updated" . ($selected_month ? "&month=$selected_month" : ""));
        exit();
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// --- FETCH DATA ---
try {
    $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    // ✅ FIXED: Changed cm.complaint_id to cm.duplicate_complaint_id in the ON clause
    $sql = "SELECT c.complaint_id, c.description, c.severity, c.impact,
                   c.date_reported, c.user_id, c.location_id, c.infrastructure_type_id,
                   c.status_id, c.image_url, c.date_resolved, c.completion_remarks,
                   c.proof_image, md.district_name AS district,
                   GROUP_CONCAT(DISTINCT u.user_name SEPARATOR ', ') as technician_name,
                   COUNT(DISTINCT cm.merge_id) as merge_count,
                   GROUP_CONCAT(DISTINCT DATE_FORMAT(cm.merge_date, '%d %b %Y, %h:%i %p') ORDER BY cm.merge_date DESC SEPARATOR '||') as merge_dates
            FROM complaint c
            LEFT JOIN location l ON c.location_id = l.location_id
            LEFT JOIN melaka_districts md ON l.district_id = md.district_id
            LEFT JOIN work_order wo ON c.complaint_id = wo.complaint_id
            LEFT JOIN team_member tm ON wo.team_id = tm.team_id
            LEFT JOIN user u ON tm.user_id = u.user_id
            LEFT JOIN complaint_merge cm ON c.complaint_id = cm.duplicate_complaint_id
            WHERE c.complaint_id NOT IN (SELECT duplicate_complaint_id FROM complaint_merge)";

    if ($selected_month != '') {
        $sql .= " AND DATE_FORMAT(c.date_reported, '%Y-%m') = :month";
    }

    $sql .= " GROUP BY c.complaint_id ORDER BY (COUNT(DISTINCT cm.merge_id)) DESC, c.date_reported DESC";

    $stmt = $pdo->prepare($sql);
    if ($selected_month != '') { $stmt->bindParam(':month', $selected_month); }
    $stmt->execute();
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $monthQuery = $pdo->query("SELECT DATE_FORMAT(date_reported, '%Y-%m') as m_val,
                               DATE_FORMAT(date_reported, '%M %Y') as m_txt
                               FROM complaint
                               GROUP BY DATE_FORMAT(date_reported, '%Y-%m')
                               ORDER BY MIN(date_reported) DESC");
    $months = $monthQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        /* CSS helpers for new information indicators */
        .urgent-tag { background: #fee2e2; color: #991b1b; padding: 3px 8px; font-size: 0.75rem; border-radius: 4px; font-weight: bold; border: 1px solid #fca5a5; display: inline-block; margin-top: 4px; }
        .date-sub { font-size: 0.78rem; color: #64748b; margin-top: 2px; display: block; }
        .merge-list { font-size: 0.8rem; background: #f0fdf4; border: 1px dashed #bbf7d0; padding: 8px; border-radius: 6px; margin-top: 10px; color: #166534; }
        .merge-list ul { margin: 5px 0 0 15px; padding: 0; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_complaints.php" class="active">📋 Manage Complaints</a></li>
            <li><a href="manage_teams.php">👥 Manage Teams</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="map_admin.php">📍 GIS Map View</a></li>
            <li><a href="manage_inquiries.php">✉️ User Inquiries</a></li>
            <li><a href="audit_logs.php">📜 Audit Logs</a></li>
            <li><a href="reports.php">📊 System Reports</a></li>
            <li><a href="Priority_settings.php">⚙️ Priority Settings</a></li>
            <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="header">
            <h1>Complaint Management</h1>
            <form method="GET">
                <select name="month" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                    <option value="">All Time</option>
                    <?php foreach ($months as $m): ?>
                        <option value="<?= $m['m_val'] ?>" <?= ($selected_month == $m['m_val']) ? 'selected' : '' ?>>
                            <?= $m['m_txt'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">✔ Update successful.</div>
        <?php endif; ?>

        <div class="content-box">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Issue Description</th>
                        <th>District</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $row): 
                        $statusText = [1 => 'Pending', 2 => 'In Progress', 3 => 'Resolved'];
                        $pillClass = ($row['status_id'] == 3) ? 'bg-green' : (($row['severity'] == 'High' || $row['merge_count'] >= 3) ? 'bg-red' : 'bg-yellow');
                        
                        // Total reports combined (Original + Merged duplicates)
                        $total_reports = $row['merge_count'] + 1; 
                    ?>
                    <tr>
                        <td>#<?= $row['complaint_id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars(substr($row['description'], 0, 50)) ?>...</strong>
                            <span class="date-sub">📅 Reported on: <?= date('d M Y, h:i A', strtotime($row['date_reported'])) ?></span>
                            
                            <?php if($row['merge_count'] > 0): ?>
                                <span class="badge badge-merge" style="background-color: #2563eb; color:white;">🔥 Total Reports: <?= $total_reports ?></span>
                                <?php if($total_reports >= 3): ?>
                                    <span class="urgent-tag">🚨 CRITICAL URGENT (Multi-User Reports)</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['district'] ?? 'N/A') ?></td>
                        <td style="color: var(--grey); font-size: 0.8rem;">
                            👤 <?= htmlspecialchars($row['technician_name'] ?? 'Unassigned') ?>
                        </td>
                        <td><span class="status-pill <?= $pillClass ?>"><?= $statusText[$row['status_id']] ?? 'Unknown' ?></span></td>
                        <td><button class="btn-manage" onclick='openEditModal(<?= json_encode($row) ?>)'>Manage</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 style="color: var(--dark-blue); border-bottom: 1px solid #eee; padding-bottom: 10px;">
                Manage Complaint #<span id="m-id-display"></span>
            </h3>
            
            <form method="POST">
                <input type="hidden" name="complaint_id" id="m-id">
                <div class="modal-grid">
                    <div>
                        <img id="m-image" src="" alt="Evidence" style="width: 100%; border-radius: 8px; border: 1px solid #eee;">
                        <div style="margin-top: 15px; padding: 10px; background: #f8fafc; border-radius: 6px;">
                            <small style="color: var(--grey);">Initial Date:</small> <strong id="m-date-display"></strong><br>
                            <small style="color: var(--grey);">District:</small> <strong id="m-district-display"></strong><br>
                            <small style="color: var(--grey);">Technician:</small> <strong id="m-tech-display"></strong>
                        </div>
                        
                        <div id="m-merge-container" class="merge-list" style="display:none;">
                            <strong>🔗 Merged Duplicates Audit Logs:</strong>
                            <ul id="m-merge-dates-list"></ul>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Priority Severity</label>
                            <select name="severity" id="m-severity">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Update Status</label>
                            <select name="status_id" id="m-status">
                                <option value="1">Pending</option>
                                <option value="2">In Progress</option>
                                <option value="3">Resolved</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Resolution Remarks</label>
                            <textarea name="completion_remarks" id="m-remarks" rows="4" placeholder="Enter notes about the fix..."></textarea>
                        </div>
                        <button type="submit" name="update_complaint" class="btn-manage" style="width: 100%; padding: 12px; font-weight: bold;">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(data) {
            document.getElementById('m-id').value = data.complaint_id;
            document.getElementById('m-id-display').textContent = data.complaint_id;
            document.getElementById('m-severity').value = data.severity;
            document.getElementById('m-status').value = data.status_id;
            document.getElementById('m-remarks').value = data.completion_remarks || "";
            document.getElementById('m-district-display').textContent = data.district || "N/A";
            document.getElementById('m-tech-display').textContent = data.technician_name || "Unassigned";
            document.getElementById('m-image').src = data.image_url || 'https://via.placeholder.com/400x300?text=No+Evidence+Image';
            
            // Format original reporting date output 
            if(data.date_reported) {
                let formattedDate = new Date(data.date_reported).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
                document.getElementById('m-date-display').textContent = formattedDate;
            }

            // Handle displaying the distinct sub-dates for all merged evidence
            const mergeContainer = document.getElementById('m-merge-container');
            const mergeList = document.getElementById('m-merge-dates-list');
            mergeList.innerHTML = ''; 

            if(data.merge_count > 0 && data.merge_dates) {
                mergeContainer.style.display = 'block';
                // Split string array generated from GROUP_CONCAT
                const datesArray = data.merge_dates.split('||');
                datesArray.forEach(dateStr => {
                    let li = document.createElement('li');
                    li.textContent = "Reported again on: " + dateStr;
                    mergeList.appendChild(li);
                });
            } else {
                mergeContainer.style.display = 'none';
            }

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(e) { if (e.target.className === 'modal') closeModal(); }
    </script>
</body>
</html>