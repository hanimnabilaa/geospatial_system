<?php
session_start();
require_once 'db.php';
// Assuming recordAudit() is defined in db.php or a functions file

// --- AUTH CHECK ---
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$error = null;
$success = null;

// --- LOGIC 1: REGISTER TECHNICIANS & ASSIGN TEAM LEAD STATUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_team'])) {
    $u_id = $_POST['user_id'];
    $t_id = $_POST['team_id'];
    $is_lead = isset($_POST['is_lead']) ? 1 : 0; 

    try {
        $pdo->beginTransaction();
        
        $stmtCheckTeam = $pdo->prepare("SELECT team_id FROM maintenance_team WHERE team_id = ?");
        $stmtCheckTeam->execute([$t_id]);
        
        if ($stmtCheckTeam->rowCount() === 0) {
            $stmtCreateTeam = $pdo->prepare("INSERT INTO maintenance_team (team_id, team_name, specialist_type, coverage_area) VALUES (?, ?, 'General', 'N/A')");
            $stmtCreateTeam->execute([$t_id, "Team " . $t_id]);
        }

        if ($is_lead === 1) {
            $demoteLeads = $pdo->prepare("UPDATE team_member SET is_lead = 0 WHERE team_id = ?");
            $demoteLeads->execute([$t_id]);
        }

        $del = $pdo->prepare("DELETE FROM team_member WHERE user_id = ?");
        $del->execute([$u_id]);

        $ins = $pdo->prepare("INSERT INTO team_member (team_id, user_id, is_lead) VALUES (?, ?, ?)");
        $ins->execute([$t_id, $u_id, $is_lead]);

        $roleText = $is_lead ? "Team Lead" : "Technician";
        recordAudit($pdo, 'TEAM_ASSIGNMENT_UPDATE', $u_id, null, "Admin reassigned user (ID: $u_id) to Team $t_id as $roleText");

        $pdo->commit();
        header("Location: manage_teams.php?msg=team_updated");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Team Setup Failed: " . $e->getMessage();
    }
}

// --- LOGIC 2: ASSIGN WORK ORDER TO TEAM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_team'])) {
    $complaint_id = $_POST['complaint_id'];
    $team_id = $_POST['team_id'];
    $date_assigned = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();
        
        $sqlWO = "INSERT INTO work_order (complaint_id, team_id, date_assigned, status_id) VALUES (?, ?, ?, 2)";
        $pdo->prepare($sqlWO)->execute([$complaint_id, $team_id, $date_assigned]);
        
        $sqlC = "UPDATE complaint SET status_id = 2 WHERE complaint_id = ?";
        $pdo->prepare($sqlC)->execute([$complaint_id]);

        recordAudit($pdo, 'WORK_ORDER_ASSIGNED', null, $complaint_id, "Complaint #$complaint_id assigned to Team $team_id");
        
        $pdo->commit();
        header("Location: manage_teams.php?msg=assigned");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Assignment failed: " . $e->getMessage();
    }
}

// --- FETCH DATA ---
try {
    $all_techs = $pdo->query("SELECT u.user_id, u.user_name, tm.team_id, tm.is_lead 
                             FROM user u 
                             LEFT JOIN team_member tm ON u.user_id = tm.user_id 
                             WHERE LOWER(TRIM(u.role)) = 'technician'")->fetchAll(PDO::FETCH_ASSOC);

    $pending_complaints = $pdo->query("SELECT c.*, md.district_name AS district, it.type_name as infra_type 
                                      FROM complaint c 
                                      LEFT JOIN location l ON c.location_id = l.location_id 
                                      LEFT JOIN melaka_districts md ON l.district_id = md.district_id
                                      LEFT JOIN infrastructure_type it ON c.infrastructure_type_id = it.infrastructure_type_id
                                      WHERE c.complaint_id NOT IN (SELECT complaint_id FROM work_order) 
                                      AND c.status_id = 1")->fetchAll(PDO::FETCH_ASSOC);

    $teams = $pdo->query("SELECT team_id, team_name, specialist_type, coverage_area FROM maintenance_team ORDER BY team_id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $active_work = $pdo->query("SELECT wo.work_order_id, wo.complaint_id, wo.team_id, wo.date_assigned, 
                                       IF(wo.status_id = 3, 'Completed', 'In Progress') AS work_status, 
                                       c.description as complaint_name, mt.specialist_type, mt.coverage_area
                               FROM work_order wo 
                               JOIN complaint c ON wo.complaint_id = c.complaint_id 
                               LEFT JOIN maintenance_team mt ON wo.team_id = mt.team_id
                               WHERE wo.status_id != 3 
                               ORDER BY wo.date_assigned DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .badge-lead { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .match-glow { background-color: #f0fdf4 !important; border-left: 4px solid #22c55e !important; }
        
        /* Directory Summary Styles */
        .directory-group { margin-bottom: 8px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; background: #fff; }
        .directory-group summary { background: #f8fafc; padding: 12px 15px; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: background-color 0.2s ease; }
        .directory-group summary:hover { background-color: #f1f5f9; }
        .directory-group[open] summary { background-color: #f0fdf4; border-bottom: 1px solid #e2e8f0; }
        .directory-content { padding: 12px 15px; }
        .district-row { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #f1f5f9; }
        .district-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .team-inline-card { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 4px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_complaints.php">📋 Manage Complaints</a></li>
            <li><a href="manage_teams.php" class="active">👥 Manage Teams</a></li>
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
            <h1>Field Team Management</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">✔ Action completed successfully.</div>
        <?php endif; ?>

        <div class="content-box" style="margin-bottom: 25px; border-left: 4px solid var(--blue, #3b82f6);">
            <h3 style="margin-bottom: 15px; color: var(--dark-blue, #1e3a8a); display: flex; align-items: center; justify-content: space-between;">
                <span>⚙️ Available Maintenance Teams Summary</span>
                <small style="font-size: 0.8rem; font-weight: normal; color: #64748b;">Click rows to expand</small>
            </h3>
            
            <?php if (empty($teams)): ?>
                <p style="color: #64748b; font-size: 0.9rem;">No maintenance teams registered in the database.</p>
            <?php else: ?>
                <?php
                // Build multi-dimensional grouping array
                $grouped_teams = [];
                foreach ($teams as $t) {
                    $spec = !empty($t['specialist_type']) ? $t['specialist_type'] : 'General';
                    $area = !empty($t['coverage_area']) ? $t['coverage_area'] : 'N/A';
                    $grouped_teams[$spec][$area][] = $t;
                }
                ?>
                <div class="neat-directory">
                    <?php foreach ($grouped_teams as $specialty => $areas): ?>
                        <details class="directory-group">
                            <summary>
                                <span>🛠️ Specialty Type: <strong><?= htmlspecialchars($specialty) ?></strong></span>
                                <span class="badge badge-blue" style="font-size: 0.75rem; padding: 2px 8px; background: #e0f2fe; color: #0369a1; border-radius: 12px;">
                                    <?= count($areas, COUNT_RECURSIVE) - count($areas) ?> Teams Registered
                                </span>
                            </summary>
                            <div class="directory-content">
                                <?php foreach ($areas as $district => $team_list): ?>
                                    <div class="district-row">
                                        <h4 style="font-size: 0.85rem; color: #475569; margin: 0 0 8px 0; font-weight: 600;">
                                            📍 Coverage Area: <span style="color: #2563eb;"><?= htmlspecialchars($district) ?></span>
                                        </h4>
                                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                            <?php foreach ($team_list as $team): ?>
                                                <div class="team-inline-card">
                                                    <span style="font-weight: 700; color: #0f172a;">Team <?= $team['team_id'] ?></span>
                                                    <span style="color: #cbd5e1;">|</span>
                                                    <span style="color: #475569;"><?= htmlspecialchars($team['team_name']) ?></span>
                                                    <span style="width: 7px; height: 7px; background: #22c55e; border-radius: 50%; display: inline-block;" title="Active"></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <h3 style="margin-bottom: 15px; color: var(--dark-blue);">1. Technician Roster & Team Leadership Assignment</h3>
            <table>
                <thead>
                    <tr>
                        <th>Technician Name</th>
                        <th>Current Team & Role</th>
                        <th>Change Assignment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_techs as $tech): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($tech['user_name']) ?></strong></td>
                        <td>
                            <?php if($tech['team_id']): ?>
                                <span class="badge badge-blue">Team <?= $tech['team_id'] ?></span>
                                <?php if($tech['is_lead']): ?>
                                    <span class="badge badge-lead">👑 Team Lead</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-grey">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; align-items: center; gap:15px;">
                                <input type="hidden" name="user_id" value="<?= $tech['user_id'] ?>">
                                
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <label style="font-size: 0.85rem; color: var(--grey);">Team:</label>
                                    <select name="team_id" required style="font-size: 0.85rem; padding: 4px;">
                                        <option value="">-- Choose Team --</option>
                                        <?php foreach ($teams as $t): ?>
                                            <option value="<?= $t['team_id'] ?>" <?= ($tech['team_id'] == $t['team_id']) ? 'selected' : '' ?>>
                                                Team <?= $t['team_id'] ?> (<?= htmlspecialchars($t['specialist_type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="is_lead" id="lead_<?= $tech['user_id'] ?>" <?= $tech['is_lead'] ? 'checked' : '' ?>>
                                    <label for="lead_<?= $tech['user_id'] ?>" style="font-size: 0.85rem; cursor: pointer;">Appoint Lead</label>
                                </div>

                                <button type="submit" name="setup_team" class="btn-action">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="grid-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            
            <div class="content-box">
                <h3 style="margin-bottom: 15px; color: var(--dark-blue);">2. Unassigned Complaints</h3>
                <?php if (empty($pending_complaints)): ?>
                    <p style="color: var(--grey); font-size: 0.9rem;">No pending complaints requiring assignment.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Issue Context</th>
                                <th>Assign Match</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_complaints as $c): ?>
                            <tr>
                                <td>
                                    <small style="color: var(--blue); font-weight: bold;">#<?= $c['complaint_id'] ?></small> 
                                    <span style="font-size:0.75rem; background:#fee2e2; color:#991b1b; padding:2px 6px; border-radius:4px; margin-left:5px; font-weight:bold;"><?= htmlspecialchars($c['severity']) ?></span>
                                    <br>
                                    <strong><?= htmlspecialchars(substr($c['description'], 0, 45)) ?>...</strong>
                                    <div style="font-size:0.8rem; color:#475569; margin-top:4px;">
                                        📍 <strong>District:</strong> <?= htmlspecialchars($c['district'] ?? 'N/A') ?><br>
                                        🛠 <strong>Type:</strong> <?= htmlspecialchars($c['infra_type'] ?? 'General') ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display:flex; flex-direction:column; gap:5px;">
                                        <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
                                        <select name="team_id" required style="font-size: 0.85rem; padding: 4px;">
                                            <option value="">Select Target Team</option>
                                            <?php 
                                            foreach ($teams as $t): 
                                                $isMatch = ($t['specialist_type'] === $c['infra_type'] && $t['coverage_area'] === $c['district']);
                                            ?>
                                                <option value="<?= $t['team_id'] ?>" <?= $isMatch ? 'selected class="match-glow"' : '' ?>>
                                                    <?= $isMatch ? '🎯 ' : '' ?>Team <?= $t['team_id'] ?> (<?= htmlspecialchars($t['specialist_type']) ?> - <?= htmlspecialchars($t['coverage_area']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_team" class="btn-action">Deploy Team</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="content-box">
                <h3 style="margin-bottom: 15px; color: var(--dark-blue);">3. Active Work Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Assigned Team Info</th>
                            <th>Complaint Details</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($active_work)): ?>
                            <tr><td colspan="3" style="text-align:center; color: var(--grey); padding: 20px;">No active assignments.</td></tr>
                        <?php else: ?>
                            <?php foreach ($active_work as $aw): ?>
                            <tr>
                                <td>
                                    <strong>Team <?= $aw['team_id'] ?></strong>
                                    <div style="font-size: 0.75rem; color:#64748b;">
                                        💼 <?= htmlspecialchars($aw['specialist_type'] ?? 'General') ?><br>
                                        📍 <?= htmlspecialchars($aw['coverage_area'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; font-weight: 500;">
                                        <?= htmlspecialchars(substr($aw['complaint_name'], 0, 40)) ?>...
                                    </div>
                                    <small style="color: var(--grey);">Assigned: <?= date('d M, H:i', strtotime($aw['date_assigned'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-warn"><?= htmlspecialchars($aw['work_status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>