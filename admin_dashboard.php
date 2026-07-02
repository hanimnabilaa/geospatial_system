<?php
session_start();
require_once 'db.php';

// 1. Security Check: Ensure only Admins can access
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// --- ADDED AUDIT LOG TRIGGER ---
// Logs that the Admin has accessed the main infrastructure overview
if (function_exists('recordAudit')) {
    recordAudit($pdo, 'ADMIN_DASHBOARD_VIEW', null, null, 'Admin accessed the Infrastructure Overview and KPI dashboard');
}

try {
    // --- KPI CALCULATIONS ---

    // Total Complaints (Excluding duplicates)
    $totalComplaints = $pdo->query("SELECT COUNT(*) FROM complaint c 
        LEFT JOIN complaint_merge d ON c.complaint_id = d.duplicate_complaint_id 
        WHERE d.duplicate_complaint_id IS NULL")->fetchColumn();
    
    // High Priority Count
    $highPriority = $pdo->query("SELECT COUNT(*) FROM complaint WHERE severity = 'High'")->fetchColumn();
    
    // Duplicates Prevented
    $duplicatesFound = $pdo->query("SELECT COUNT(*) FROM complaint_merge")->fetchColumn();

    // SLA Performance: Percentage of resolved complaints
    $resolvedCount = $pdo->query("SELECT COUNT(*) FROM complaint WHERE status_id = 3")->fetchColumn();
    $slaPerformance = ($totalComplaints > 0) ? round(($resolvedCount / $totalComplaints) * 100, 1) : 100;

    // --- DATA FOR TABLES ---

    // ✅ FIXED: Joined melaka_districts table and updated selection to md.district_name
    $sqlRecent = "SELECT c.complaint_id, c.description, c.severity, md.district_name AS district, c.date_reported 
                  FROM complaint c
                  LEFT JOIN location l ON c.location_id = l.location_id
                  LEFT JOIN melaka_districts md ON l.district_id = md.district_id
                  LEFT JOIN complaint_merge d ON c.complaint_id = d.duplicate_complaint_id
                  WHERE d.duplicate_complaint_id IS NULL
                  ORDER BY c.date_reported DESC LIMIT 5";
    $recentTasks = $pdo->query($sqlRecent)->fetchAll(PDO::FETCH_ASSOC);

    // --- Technician Workload Summary ---
    // ✅ FIXED: Updated to read status_id instead of work_status to match DB changes
    $sqlTech = "SELECT 
                u.user_name, 
                -- Active Tasks: Counting anything not finished (Status IDs 1=Pending, 2=In Progress)
                COUNT(DISTINCT CASE 
                    WHEN wo.status_id IN (1, 2) 
                    THEN wo.work_order_id 
                END) as in_progress,
                
                -- Total Completed: Counting anything finished (Status ID 3=Resolved/Completed)
                COUNT(DISTINCT CASE 
                    WHEN wo.status_id = 3 
                    THEN wo.work_order_id 
                END) as completed
                
                FROM user u
                INNER JOIN team_member tm ON u.user_id = tm.user_id
                INNER JOIN work_order wo ON tm.team_id = wo.team_id
                WHERE LOWER(TRIM(u.role)) = 'technician'
                GROUP BY u.user_id, u.user_name 
                ORDER BY in_progress DESC";
    
    $techPerformance = $pdo->query($sqlTech)->fetchAll(PDO::FETCH_ASSOC);

    // --- FIXED: Updated identifier to use 'contact_id' to match your database schema ---
    $sqlInquiries = "SELECT contact_id, full_name, email, inquiry_type, message, rating, ip_address 
                     FROM contact_inquiries 
                     ORDER BY contact_id DESC LIMIT 5";
    $recentInquiries = $pdo->query($sqlInquiries)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Supplementary layout helpers for inline visualization consistency */
        .text-muted { color: #64748b; font-size: 0.85rem; }
        .rating-stars { color: #f59e0b; font-weight: bold; }
        .bg-orange { background-color: #ffedd5; color: #c2410c; }
        
        /* FIXED: Memastikan fon teks kategori aduan berwarna hitam tulen demi standard accessibility */
        .status-pill {
            color: #000000 !important;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active">🏠 Dashboard</a></li>
            <li><a href="manage_complaints.php">📋 Manage Complaints</a></li>
            <li><a href="manage_teams.php">👥 Manage Teams</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
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
            <h1>Infrastructure Overview</h1>
            <div class="user-info">Welcome, <strong>Admin</strong></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Complaints</h3>
                <div class="value"><?php echo number_format($totalComplaints); ?></div>
            </div>
            <div class="stat-card high-priority">
                <h3>High Priority</h3>
                <div class="value"><?php echo $highPriority; ?></div>
            </div>
            <div class="stat-card success">
                <h3>SLA Performance</h3>
                <div class="value"><?php echo $slaPerformance; ?>%</div>
            </div>
            <div class="stat-card">
                <h3>Duplicates Merged</h3>
                <div class="value"><?php echo $duplicatesFound; ?></div>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="content-box">
                <h4>Recent Unique Complaints</h4>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Issue</th>
                            <th>District</th>
                            <th>Priority</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($recentTasks) > 0): foreach ($recentTasks as $task): 
                            $prioClass = ($task['severity'] === 'High') ? 'bg-red' : (($task['severity'] === 'Medium') ? 'bg-yellow' : 'bg-green');
                        ?>
                        <tr>
                            <td>#<?php echo $task['complaint_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($task['description']); ?></strong></td>
                            <td><?php echo htmlspecialchars($task['district'] ?? 'N/A'); ?></td>
                            <td><span class="status-pill <?php echo $prioClass; ?>"><?php echo $task['severity']; ?></span></td>
                            <td><?php echo date('d M', strtotime($task['date_reported'])); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align:center;">No recent complaints found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-box">
                <h4>System Health</h4>
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="content-box" style="margin-bottom: 30px;">
            <h4>Recent Citizen Inquiries & Feedback</h4>
            <table>
                <thead>
                    <tr>
                        <th>User Details</th>
                        <th>Category</th>
                        <th>Message String Log</th>
                        <th>Rating</th>
                        <th>Origin IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($recentInquiries) > 0): foreach ($recentInquiries as $inquiry): 
                        $typeClass = ($inquiry['inquiry_type'] === 'Technical Support') ? 'bg-red' : 
                                     (($inquiry['inquiry_type'] === 'Report GIS Data Error') ? 'bg-orange' : 'bg-blue');
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($inquiry['full_name']); ?></strong><br>
                            <span class="text-muted">Ref: #<?php echo $inquiry['contact_id']; ?></span>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $typeClass; ?>">
                                <?php echo htmlspecialchars($inquiry['inquiry_type']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($inquiry['message']); ?>">
                                <?php echo htmlspecialchars($inquiry['message']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="rating-stars">
                                <?php 
                                    // SAFE CORRECTION FOR DEPRECATION NOTICE
                                    $rating = (int)($inquiry['rating'] ?? 0);
                                    echo str_repeat('★', $rating) . str_repeat('☆', max(0, 5 - $rating)); 
                                ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size: 0.85rem; font-family: monospace;">
                                <?php echo htmlspecialchars($inquiry['ip_address'] ?? '0.0.0.0'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;">No support inquiries or system feedback received yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="content-box">
            <h4>Technician Performance & Workload</h4>
            <table>
                <thead>
                    <tr>
                        <th>Technician Name</th>
                        <th>Active Tasks</th>
                        <th>Total Completed</th>
                        <th>Load Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($techPerformance) > 0): foreach ($techPerformance as $tech): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($tech['user_name']); ?></strong></td>
                        <td><?php echo $tech['in_progress']; ?></td>
                        <td><?php echo $tech['completed']; ?></td>
                        <td>
                            <?php if ($tech['in_progress'] >= 3): ?>
                                <span class="status-pill bg-red">Heavy Load</span>
                            <?php elseif ($tech['in_progress'] > 0): ?>
                                <span class="status-pill bg-yellow">Active</span>
                            <?php else: ?>
                                <span class="status-pill bg-green">Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;">No technicians registered.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'Pending'],
                datasets: [{
                    data: [<?php echo $resolvedCount; ?>, <?php echo ($totalComplaints - $resolvedCount); ?>],
                    backgroundColor: ['#10b981', '#3b82f6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
   
</body>
</html>