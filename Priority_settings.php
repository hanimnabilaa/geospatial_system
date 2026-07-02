<?php
session_start();
require_once 'db.php';

// 1. Security Check: Ensure only Admins can access
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// 2. Audit Event Processing
if (function_exists('recordAudit')) {
    recordAudit($pdo, 'ADMIN_PRIORITY_VIEW', null, null, 'Admin accessed priority threshold settings engine configuration console.');
}

$message = "";
$error = "";

// 3. Handle Form Submissions to update infrastructure weights dynamically
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_infra_weight'])) {
    $infra_id = intval($_POST['infrastructure_type_id']);
    $new_modifier = intval($_POST['weight_modifier']);
    
    try {
        $stmt = $pdo->prepare("UPDATE infrastructure_type SET weight_modifier = ? WHERE infrastructure_type_id = ?");
        $stmt->execute([$new_modifier, $infra_id]);
        $message = "Infrastructure weighting rule modified successfully! Priority algorithms updated.";
        
        // Dynamic Audit entry log mapping
        if (function_exists('recordAudit')) {
            recordAudit($pdo, 'PRIORITY_RULE_UPDATE', null, null, "Updated Infra Type ID $infra_id weight modifier to $new_modifier");
        }
    } catch (PDOException $e) {
        $error = "Failed to update configuration parameter: " . $e->getMessage();
    }
}

try {
    // --- SYSTEM PRIORITY SETTINGS META QUERIES ---
    // Fetch all active infrastructure classifications to feed the live weight adjustment forms
    $infraTypes = $pdo->query("SELECT * FROM infrastructure_type ORDER BY type_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Pull the top prioritized items currently calculated dynamically via the DB Triggers
    $sqlPriorityQueue = "SELECT c.complaint_id, c.description, c.severity, c.impact, 
                                ws.priority_score, it.type_name, md.district_name
                         FROM complaint c
                         INNER JOIN weight_score ws ON c.complaint_id = ws.complaint_id
                         LEFT JOIN infrastructure_type it ON c.infrastructure_type_id = it.infrastructure_type_id
                         LEFT JOIN location l ON c.location_id = l.location_id
                         LEFT JOIN melaka_districts md ON l.district_id = md.district_id
                         WHERE c.status_id != 3
                         ORDER BY ws.priority_score DESC LIMIT 10";
    $priorityQueue = $pdo->query($sqlPriorityQueue)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Engine Context Exception: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priority Settings & Weights - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .text-muted { color: #64748b; font-size: 0.85rem; }
        .alert-success { padding: 12px; background-color: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .alert-danger { padding: 12px; background-color: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .weight-input { width: 80px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-weight: bold; text-align: center; }
        .btn-update { padding: 6px 12px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
        .btn-update:hover { background-color: #1d4ed8; }
        .badge-score { padding: 4px 8px; background-color: #edf2f7; color: #2d3748; border-radius: 4px; font-weight: 700; font-family: monospace; font-size: 1rem; }
        .bg-red { background-color: #fee2e2; color: #991b1b; }
        .bg-yellow { background-color: #fef9c3; color: #854d0e; }
        .bg-green { background-color: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_complaints.php">📋 Manage Complaints</a></li>
            <li><a href="manage_teams.php">👥 Manage Teams</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="map_admin.php">📍 GIS Map View</a></li>
            <li><a href="manage_inquiries.php">✉️ User Inquiries</a></li>
            <li><a href="audit_logs.php">📜 Audit Logs</a></li>
            <li><a href="reports.php">📊 System Reports</a></li>
            <li><a href="Priority_settings.php" class="active">⚙️ Priority Settings</a></li>
            <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dynamic Priority Matrix Configurations</h1>
            <div class="user-info">Role: <strong>System Admin</strong></div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-row">
            <div class="content-box" style="flex: 1 1 40%;">
                <h4>Infrastructure Weight Modifiers</h4>
                <p class="text-muted" style="margin-bottom: 15px;">
                    Adjust values below to control how infrastructure classification types amplify incoming priority calculation rankings.
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>Asset Classification</th>
                            <th>Current Weight Multiplier</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($infraTypes as $type): ?>
                        <tr>
                            <form method="POST" action="">
                                <td><strong><?php echo htmlspecialchars($type['type_name']); ?></strong></td>
                                <td>
                                    <input type="hidden" name="infrastructure_type_id" value="<?php echo $type['infrastructure_type_id']; ?>">
                                    <input type="number" name="weight_modifier" class="weight-input" min="1" max="10" value="<?php echo intval($type['weight_modifier']); ?>">
                                </td>
                                <td>
                                    <button type="submit" name="update_infra_weight" class="btn-update">Save Rule</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px;">
                    <h5>💡 Priority Score Formulation Mapping</h5>
                    <p class="text-muted" style="margin-top: 5px; line-height: 1.4;">
                        The real-time database calculation model trigger updates values automatically using the following formula:<br>
                        <strong>Priority Score = (Infrastructure Modifier × Severity Weight) + Impact Weight</strong>
                    </p>
                </div>
            </div>

            <div class="content-box" style="flex: 1 1 55%;">
                <h4>Active Priority Dispatch Queue (Evaluated via Trigger Engine)</h4>
                <p class="text-muted" style="margin-bottom: 15px;">
                    Live breakdown of unassigned/active tickets calculated dynamically via automated weight algorithms.
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Incident Description</th>
                            <th>Type</th>
                            <th>District</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($priorityQueue) > 0): foreach ($priorityQueue as $row): ?>
                        <tr>
                            <td>#<?php echo $row['complaint_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['description']); ?></strong><br>
                                <span class="text-muted" style="font-size:0.75rem;">Sev: <?php echo htmlspecialchars($row['severity']); ?> | Imp: <?php echo htmlspecialchars($row['impact']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['type_name'] ?? 'General'); ?></td>
                            <td><?php echo htmlspecialchars($row['district_name'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge-score <?php echo ($row['priority_score'] >= 15) ? 'bg-red' : (($row['priority_score'] >= 8) ? 'bg-yellow' : 'bg-green'); ?>">
                                    <?php echo number_format($row['priority_score'], 1); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align:center;">No active items currently tracked inside the critical dispatch queue layout.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>