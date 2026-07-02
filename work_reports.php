<?php
session_start();
require_once 'db.php';

// Access Control
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'technician') {
    header("Location: login_technician.php");
    exit();
}

// Logs that the technician is reviewing their work history/SLA performance
recordAudit($pdo, 'VIEW_PERFORMANCE_REPORT', null, null, 'Technician ID ' . $_SESSION['user_id'] . ' accessed SLA history');

try {
    // 💡 FIXED: Added JOINs to work_order and team_member to filter strictly by the logged-in user
    $sql = "SELECT 
                c.complaint_id, 
                c.description, 
                c.date_reported, 
                c.date_resolved, 
                c.completion_remarks,
                c.proof_image,
                l.address, 
                md.district_name AS district,
                TIMESTAMPDIFF(HOUR, c.date_reported, c.date_resolved) as hours_taken
            FROM complaint c
            JOIN location l ON c.location_id = l.location_id
            LEFT JOIN melaka_districts md ON l.district_id = md.district_id
            JOIN work_order wo ON c.complaint_id = wo.complaint_id
            JOIN team_member tm ON wo.team_id = tm.team_id
            WHERE c.status_id = 3 
              AND tm.user_id = :user_id
            ORDER BY c.date_resolved DESC";

    $stmt = $pdo->prepare($sql);
    // Bind the current logged-in technician's ID from the session
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Performance | SmartCity GIS</title>
    <style>
        :root {
            --dark-blue: #1e3a8a;
            --blue: #3b82f6;
            --grey: #6b7280;
            --light-bg: #f3f4f6;
            --white: #ffffff;
            --red: #ef4444;      /* Delayed */
            --yellow: #f59e0b;    /* Warning */
            --green: #10b981;     /* On-Time */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--light-bg); display: flex; min-height: 100vh; }

        /* Sidebar Navigation - Matched to Dashboard */
        .sidebar {
            width: 260px;
            background-color: var(--dark-blue);
            color: var(--white);
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar h2 { font-size: 1.2rem; margin-bottom: 30px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .sidebar-menu { list-style: none; flex-grow: 1; }
        .sidebar-menu li { margin-bottom: 15px; }
        .sidebar-menu a { 
            color: #cbd5e1; text-decoration: none; display: flex; align-items: center; 
            padding: 10px; border-radius: 5px; transition: 0.3s; 
        }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--blue); color: white; }

        /* Main Content */
        .main-content { flex: 1; padding: 30px; margin-left: 260px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }

        /* KPI Cards */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 20px; margin-bottom: 30px; 
        }
        .stat-card { 
            background: var(--white); padding: 20px; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid var(--blue);
        }
        .stat-card h3 { font-size: 0.85rem; color: var(--grey); text-transform: uppercase; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: var(--dark-blue); margin-top: 5px; }

        /* Table Box */
        .content-box { 
            background: var(--white); padding: 25px; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .content-box h4 { margin-bottom: 20px; color: var(--dark-blue); font-size: 1.1rem; border-bottom: 2px solid var(--light-bg); padding-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 12px; background: #f8fafc; color: var(--grey); font-size: 0.85rem; text-transform: uppercase; }
        table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; vertical-align: middle; }

        .status-pill { 
            padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; color: white; display: inline-block;
        }
        .bg-green { background-color: var(--green); }
        .bg-red { background-color: var(--red); }

        .proof-img { 
            width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;
        }

        .btn-print {
            background-color: var(--dark-blue); color: white; padding: 10px 20px; 
            border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .btn-print:hover { background-color: var(--blue); }

        /* PRINT STYLES - Ensures PDF looks clean */
        @media print {
            .sidebar, .btn-print, .no-print { display: none !important; }
            .main-content { margin-left: 0; padding: 0; }
            body { background-color: white; }
            .content-box { box-shadow: none; border: 1px solid #eee; }
            .header h1 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

    <nav class="sidebar no-print">
        <h2>SmartCity GIS</h2>
        <ul class="sidebar-menu">
            <li><a href="dashboard_tech.php">Assigned Tasks</a></li>
            <li><a href="map_view.php">Navigation Map</a></li>
            <li><a href="map_hotspots.php">Priority Hotspots</a></li>
            <li><a href="work_reports.php" class="active">Work Reports</a></li>
             <li style="margin-top: 40px;"><a href="logout_tech.php" style="color: #fca5a5;">Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="header">
            <div>
                <h1 style="color: var(--dark-blue);">SLA & Maintenance History</h1>
                <p class="no-print" style="color: var(--grey); font-size: 0.9rem;">Review completed tasks and performance metrics.</p>
            </div>
            <div class="no-print" style="display: flex; gap: 10px; align-items: center;">
                <span class="status-pill bg-green">Active Session</span>
                <button class="btn-print" onclick="window.print()">Save as PDF</button>
            </div>
        </header>

        <div class="stats-grid no-print">
            <div class="stat-card">
                <h3>Resolved Issues</h3>
                <div class="value"><?php echo sprintf("%02d", count($history)); ?></div>
            </div>
            <div class="stat-card">
                <h3>Avg. Turnaround</h3>
                <div class="value">
                    <?php 
                        $total_hours = array_sum(array_column($history, 'hours_taken'));
                        echo (count($history) > 0) ? round($total_hours / count($history), 1) : 0; 
                    ?> <span style="font-size: 1rem;">Hours</span>
                </div>
            </div>
        </div>

        <div class="content-box">
            <h4>Completed Maintenance Log</h4>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Proof</th>
                        <th>Location & Issue</th>
                        <th>Resolution</th>
                        <th>SLA Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): 
                        $is_delayed = ($row['hours_taken'] > 48);
                    ?>
                    <tr>
                        <td><strong>#<?php echo $row['complaint_id']; ?></strong></td>
                        <td>
                            <?php if (!empty($row['proof_image'])): ?>
                                <img src="<?php echo $row['proof_image']; ?>" class="proof-img">
                            <?php else: ?>
                                <small style="color: var(--grey);">N/A</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['address']); ?></strong>
                            <?php if (!empty($row['district'])): ?>
                                <span style="color: var(--blue); font-size: 0.8rem; font-weight: 600;">(<?php echo htmlspecialchars($row['district']); ?>)</span>
                            <?php endif; ?><br>
                            <small style="color: var(--grey);"><?php echo htmlspecialchars($row['description']); ?></small>
                        </td>
                        <td>
                            <strong><?php echo $row['hours_taken']; ?> Hours</strong><br>
                            <small><em>"<?php echo htmlspecialchars($row['completion_remarks'] ?? 'No remarks'); ?>"</em></small>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $is_delayed ? 'bg-red' : 'bg-green'; ?>">
                                <?php echo $is_delayed ? 'DELAYED' : 'ON-TIME'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 40px; color: var(--grey);">No completed records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>