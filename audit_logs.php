<?php
session_start();
require_once 'db.php';

/* =====================================================
   ADMIN AUTHENTICATION
===================================================== */
if (
    !isset($_SESSION['role']) ||
    strtolower(trim($_SESSION['role'])) !== 'admin'
) {
    header("Location: login_admin.php");
    exit();
}

/* =====================================================
   FILTER TYPE
===================================================== */
$log_type = $_GET['type'] ?? 'all';

/* =====================================================
   HELPER: DYNAMIC COLOR TAGS FOR ACTIONS
===================================================== */
function getTagStyle($action, $hasMergeData = false) {
    $action = strtolower(trim($action));
    
    // Force immediate merge layout style if database records a spatial connection
    if ($hasMergeData) {
        return 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;';
    }

    return match($action) {
        'delete', 'remove', 'drop' => 'background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;', 
        'update', 'edit', 'modify' => 'background: #fffbeb; color: #92400e; border: 1px solid #fef3c7;', 
        'insert', 'create', 'add'   => 'background: #f0fdf4; color: #166534; border: 1px solid #dcfce7;', 
        'login', 'logout'          => 'background: #f8fafc; color: #334155; border: 1px solid #e2e8f0;', 
        'merge', 'link', 'combine' => 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;', 
        default                    => 'background: #f0f9ff; color: #0369a1; border: 1px solid #e0f2fe;'  
    };
}

/* =====================================================
   FETCH AUDIT LOGS
===================================================== */
try {
    $typeStmt = $pdo->query("
        SELECT DISTINCT action_type
        FROM audit_log
        WHERE action_type IS NOT NULL
        ORDER BY action_type ASC
    ");
    $types = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

    /* =====================================================
       MAIN QUERY - FIXED COMPLAINT MERGE LOOKUP
    ===================================================== */
    // ✅ FIXED: Changed cm.complaint_id to cm.newcomplaint_id based on your schema dump
    $sql = "
        SELECT
            a.*,
            u.user_name,
            c.description AS complaint_ref,
            cm.distance_meter AS merge_distance,
            cm.duplicate_complaint_id AS resolved_child_id
        FROM audit_log a
        LEFT JOIN user u
            ON a.user_id = u.user_id
        LEFT JOIN complaint c
            ON a.complaint_id = c.complaint_id
        LEFT JOIN complaint_merge cm
            ON a.complaint_id = cm.newcomplaint_id
    ";

    $params = [];

    if ($log_type !== 'all') {
        $sql .= " WHERE a.action_type = :type ";
        $params[':type'] = $log_type;
    }

    $sql .= "
        ORDER BY a.action_date DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Audit Log Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .merge-alert-badge {
            background-color: #2563eb;
            color: #ffffff;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 4px;
        }
        .merge-flow-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: monospace;
            background: #f8fafc;
            padding: 6px;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
        }
        .gis-distance-tag {
            display: inline-block;
            margin-top: 4px;
            font-size: 0.75rem;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<nav class="sidebar">
    <h2>Admin Panel</h2>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php">🏠 Dashboard</a></li>
        <li><a href="manage_complaints.php">📋 Manage Complaints</a></li>
        <li><a href="manage_teams.php">👥 Manage Teams</a></li>
        <li><a href="manage_users.php">👥 Manage Users</a></li>
        <li><a href="map_admin.php">📍 GIS Map View</a></li>
        <li><a href="manage_inquiries.php">✉️ User Inquiries</a></li>
        <li><a href="audit_logs.php" class="active">📜 Audit Logs</a></li>
        <li><a href="reports.php">📊 System Reports</a></li>
        <li><a href="Priority_settings.php">⚙️ Priority Settings</a></li>
        <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="main-content">

    <div class="header">
        <h1>System Audit Logs</h1>
        
        <form id="filterForm" method="GET" action="">
            <input type="hidden" name="type" id="filterTypeValue" value="<?= htmlspecialchars($log_type) ?>">
            
            <div class="filter-tabs">
                <button type="button" 
                        class="filter-tab-btn <?= ($log_type === 'all') ? 'active' : '' ?>" 
                        onclick="submitFilter('all')">
                    All Activities
                </button>
                <?php foreach ($types as $t): ?>
                    <button type="button" 
                            class="filter-tab-btn <?= ($log_type === $t) ? 'active' : '' ?>" 
                            onclick="submitFilter('<?= htmlspecialchars($t) ?>')">
                        <?= htmlspecialchars($t) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <div class="pdpa-box">
        🛡️ <strong>PDPA Compliance Active:</strong> This ecosystem monitors configuration operations. Access permissions remain exclusively restricted to root structural management layers.
    </div>

    <div class="content-box">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <p>No logged actions matched your selected parameters.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 18%;">Date & Time</th>
                        <th style="width: 15%;">Operator</th>
                        <th style="width: 12%;">Action</th>
                        <th style="width: 25%;">Target Scope</th>
                        <th style="width: 30%;">Data Modifications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): 
                        $actionLower = strtolower(trim($log['action_type']));
                        
                        // ABSOLUTE TRIGGER: If a geospatial link exists in cm, this is a merge row
                        $hasMergeData = (isset($log['merge_distance']) && $log['merge_distance'] !== null);
                        $isMergeAction = in_array($actionLower, ['merge', 'link', 'combine']) || $hasMergeData;
                    ?>
                        <tr style="<?= $isMergeAction ? 'background-color: #f8fafc;' : '' ?>">
                            <td class="timestamp">
                                <?php if (!empty($log['action_date'])): ?>
                                    <?= date('Y-m-d H:i:s', strtotime($log['action_date'])); ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">No Date Recorded</span>
                                <?php endif; ?>
                            </td>

                            <td class="user-tag">
                                <?php if (!empty($log['user_name'])): ?>
                                    <?= htmlspecialchars($log['user_name']); ?>
                                <?php else: ?>
                                    <span class="system-identity">automated engine</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="action-tag" style="<?= getTagStyle($log['action_type'], $hasMergeData) ?>">
                                    <?= $hasMergeData ? 'COMPLAINT MERGE' : htmlspecialchars($log['action_type']); ?>
                                </span>
                            </td>

                            <td>
                                <div class="complaint-wrapper">
                                    <?php if($isMergeAction): ?>
                                        <span class="merge-alert-badge">🔗 Case Merge</span>
                                        <span class="complaint-ref" style="display:block; font-weight:600; color:#1e40af;">
                                            Parent Master Ticket #<?= htmlspecialchars($log['complaint_id'] ?? 'N/A') ?>
                                        </span>
                                        <?php if (!empty($log['complaint_ref'])): ?>
                                            <span style="font-size: 0.8rem; color:#475569; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:220px;" title="<?= htmlspecialchars($log['complaint_ref']) ?>">
                                                <?= htmlspecialchars($log['complaint_ref']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif(!empty($log['complaint_id'])): ?>
                                        <span class="complaint-id-badge">ID: #<?= htmlspecialchars($log['complaint_id']) ?></span>
                                        <span class="complaint-ref" title="<?= htmlspecialchars($log['complaint_ref'] ?? '') ?>">
                                            <?= htmlspecialchars($log['complaint_ref'] ?? 'Context reference empty'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-change-placeholder">— Global Event</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div class="val-container">
                                    <?php if($isMergeAction): ?>
                                        <div class="merge-flow-indicator">
                                            <span style="color:#ef4444; font-weight:bold;">
                                                NEW REPORT ID: #<?= htmlspecialchars($log['resolved_child_id'] ?? 'Pending Allocation') ?>
                                            </span>
                                            <span>➡️ Linked into ➡️</span>
                                            <span style="color:#16a34a; font-weight:bold;">OLD REPORT ID: #<?= htmlspecialchars($log['complaint_id'] ?? 'Unknown') ?></span>
                                        </div>
                                        
                                        <div class="gis-distance-tag">
                                            📍 Proximity Distance: <?= htmlspecialchars($log['merge_distance']) ?> meters
                                        </div>
                                        
                                        <?php if(!empty($log['old_value'])): ?>
                                            <div style="font-size:0.75rem; color:#64748b; margin-top:6px; font-style:italic;">
                                                Payload: <?= htmlspecialchars($log['old_value']) ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <?php if(!empty($log['old_value'])): ?>
                                            <div class="diff-box old-box">
                                                <div class="prefix">Before</div>
                                                <div class="content"><?= htmlspecialchars($log['old_value']); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if(!empty($log['new_value'])): ?>
                                            <div class="diff-box new-box">
                                                <div class="prefix">Now</div>
                                                <div class="content"><?= htmlspecialchars($log['new_value']); ?></div>
                                            </div>
                                        <?php elseif(empty($log['old_value'])): ?>
                                            <span class="no-change-placeholder">No metadata changes saved</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function submitFilter(typeValue) {
    document.getElementById('filterTypeValue').value = typeValue;
    document.getElementById('filterForm').submit();
}
</script>

</body>
</html>