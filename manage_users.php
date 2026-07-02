<?php
session_start();
require_once 'db.php';

// 1. Sekatan Keselamatan: Pastikan hanya Admin boleh mengakses halaman ini
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// 2. Rekod Log Audit
if (function_exists('recordAudit')) {
    recordAudit($pdo, 'ADMIN_USER_MANAGEMENT_VIEW', null, null, 'Admin accessed the user management control panel');
}

// 3. Pengendalian Proses Padam Pengguna (Jika dicetuskan)
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $pdo->beginTransaction();
        
        // Semak jika pengguna wujud sebelum dipadam
        $stmtCheck = $pdo->prepare("SELECT user_name FROM user WHERE user_id = ?");
        $stmtCheck->execute([$delete_id]);
        $uName = $stmtCheck->fetchColumn();

        if ($uName) {
            $pdo->prepare("DELETE FROM user WHERE user_id = ?")->execute([$delete_id]);
            
            if (function_exists('recordAudit')) {
                recordAudit($pdo, 'USER_DELETION', $delete_id, null, "Admin deleted user account: $uName (ID: $delete_id)");
            }
            $pdo->commit();
            echo "<script>alert('User account deleted successfully.'); window.location='manage_users.php';</script>";
        } else {
            $pdo->rollBack();
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        $error_msg = "Cannot delete user. They might be assigned to an active maintenance team or linked to log history.";
    }
}

// 4. Penapisan Peranan (Role Filtering)
$role_filter = $_GET['role'] ?? 'all';

try {
    // ⚙️ FIX: Menggunakan 'wo.status_id IN (1, 2)' bagi menggantikan 'wo.work_status' yang tidak wujud
    $sqlUsers = "SELECT u.user_id, u.user_name, u.user_email, u.role, u.user_phonenum,
                 COUNT(DISTINCT CASE WHEN wo.status_id IN (1, 2) THEN wo.work_order_id END) as active_tasks
                 FROM user u
                 LEFT JOIN team_member tm ON u.user_id = tm.user_id
                 LEFT JOIN work_order wo ON tm.team_id = wo.team_id";
                 
    if ($role_filter !== 'all') {
        $sqlUsers .= " WHERE LOWER(TRIM(u.role)) = " . $pdo->quote(strtolower(trim($role_filter)));
    }
    
    // ⚙️ FIX: Mengemas kini Group By mengikut lajur pilihan
    $sqlUsers .= " GROUP BY u.user_id, u.user_name, u.user_email, u.role, u.user_phonenum ORDER BY u.user_id DESC";
    $usersList = $pdo->query($sqlUsers)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .filter-container { margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        .filter-btn { padding: 8px 16px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; text-decoration: none; color: #334155; font-size: 0.9rem; }
        .filter-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .action-link { text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; }
        .delete-link { background-color: #fee2e2; color: #ef4444; margin-left: 5px; }
        .delete-link:hover { background-color: #fca5a5; }
        .alert-error { background-color: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fca5a5; }
        .status-pill { color: #000000 !important; font-weight: 500; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_complaints.php">📋 Manage Complaints</a></li>
            <li><a href="manage_teams.php">👥 Manage Teams</a></li>
            <li><a href="manage_users.php" class="active">👥 Manage Users</a></li>
            <li><a href="map_admin.php">📍 GIS Map View</a></li>
            <li><a href="manage_inquiries.php">✉️ User Inquiries</a></li>
            <li><a href="audit_logs.php">📜 Audit Logs</a></li>
            <li><a href="reports.php">📊 System Reports</a></li>
            <li><a href="Priority_settings.php">⚙️ Priority Settings</a></li>
            <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>User Account Control Panel</h1>
            <div class="user-info">Logged in as: <strong>Admin</strong></div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert-error">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="filter-container">
            <span>Filter Role:</span>
            <a href="manage_users.php?role=all" class="filter-btn <?php echo $role_filter === 'all' ? 'active' : ''; ?>">All Accounts</a>
            <a href="manage_users.php?role=citizen" class="filter-btn <?php echo $role_filter === 'citizen' ? 'active' : ''; ?>">Citizens</a>
            <a href="manage_users.php?role=technician" class="filter-btn <?php echo $role_filter === 'technician' ? 'active' : ''; ?>">Technicians</a>
            <a href="manage_users.php?role=admin" class="filter-btn <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">Admins</a>
        </div>

        <div class="content-box">
            <h4>Registered System Accounts (<?php echo count($usersList); ?>)</h4>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Contact / Email</th>
                        <th>System Role</th>
                        <th>Workload Status (Tech Only)</th>
                        <th>Management Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usersList) > 0): foreach ($usersList as $user): 
                        // Set warna badge mengikut peranan
                        $roleClass = 'bg-blue';
                        if (strtolower(trim($user['role'] ?? '')) === 'admin') $roleClass = 'bg-red';
                        if (strtolower(trim($user['role'] ?? '')) === 'technician') $roleClass = 'bg-orange';
                    ?>
                    <tr>
                        <td>#<?php echo $user['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['user_name'] ?? 'N/A'); ?></strong></td>
                        <td>
                            📞 <?php echo htmlspecialchars($user['user_phonenum'] ?? '—'); ?><br>
                            ✉️ <?php echo htmlspecialchars($user['user_email'] ?? '—'); ?>
                        </td>
                        <td><span class="status-pill <?php echo $roleClass; ?>"><?php echo ucfirst(htmlspecialchars($user['role'] ?? '')); ?></span></td>
                        <td>
                            <?php if (strtolower(trim($user['role'] ?? '')) === 'technician'): ?>
                                <?php if ($user['active_tasks'] >= 3): ?>
                                    <span class="status-pill bg-red">Heavy (<?php echo $user['active_tasks']; ?> Tasks)</span>
                                <?php elseif ($user['active_tasks'] > 0): ?>
                                    <span class="status-pill bg-yellow">Active (<?php echo $user['active_tasks']; ?> Tasks)</span>
                                <?php else: ?>
                                    <span class="status-pill bg-green">Available</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="manage_users.php?delete_id=<?php echo $user['user_id']; ?>" 
                               class="action-link delete-link" 
                               onclick="return confirm('CRITICAL WARNING: Are you sure you want to permanently delete user [<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>]? This operation cannot be undone.');">
                                🗑️ Delete Account
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No registered accounts matched the selected role filter.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>