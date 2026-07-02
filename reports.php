<?php
session_start();
require_once 'db.php';

// Memastikan hanya pengguna dengan peranan 'admin' mempunyai akses
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// Merekodkan aktiviti akses laporan ke dalam audit log
recordAudit($pdo, 'ADMIN_REPORT_VIEW', null, null, 'Admin generated system-wide performance reports');

try {
    // ⚙️ OPTIMASI FYP: Memanggil Stored Procedure yang memulangkan Multiple Result Sets
    $stmt = $pdo->query("CALL sp_get_admin_dashboard_reports()");

    // Set Keputusan 1: ANALISIS DAERAH (District Analysis)
    $districtStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->nextRowset();

    // Set Keputusan 2: PENGAGIHAN KETERUKAN (Severity Distribution)
    $priorityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->nextRowset();

    // Set Keputusan 3: PRESTASI PASUKAN (Team Performance)
    $teamStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Menutup cursor pemprosesan database bagi membolehkan query seterusnya berjalan lancar
    $stmt->closeCursor();

    // 📊 KIRAAN KPI SECARA DINAMIK
    $totalComplaints = array_sum(array_column($districtStats, 'total'));
    $totalResolved = array_sum(array_column($districtStats, 'resolved'));

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports | SmartCity GIS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin_styles.css">
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
            <li><a href="audit_logs.php">📜 Audit Logs</a></li>
            <li><a href="reports.php" class="active">📊 System Reports</a></li>
            <li><a href="Priority_settings.php">⚙️ Priority Settings</a></li>
            <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>System Performance Reports</h1>
            <button class="print-btn" onclick="window.print()">🖨️ Export PDF / Print</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Complaints</h3>
                <div class="value"><?php echo $totalComplaints; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: var(--green);">
                <h3>Resolved Cases</h3>
                <div class="value"><?php echo $totalResolved; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: var(--yellow);">
                <h3>Avg. Resolution Rate</h3>
                <div class="value">
                    <?php echo ($totalComplaints > 0) ? round(($totalResolved / $totalComplaints) * 100, 1) : 0; ?>%
                </div>
            </div>
        </div>

        <div class="report-grid">
            <div class="card">
                <h3>Complaint Severity Distribution</h3>
                <canvas id="priorityChart"></canvas>
            </div>
            <div class="card">
                <h3>Team Total Assigned Tasks</h3>
                <canvas id="teamChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h3>District-wise Infrastructure Health</h3>
            <table>
                <thead>
                    <tr>
                        <th>District Name</th>
                        <th>Total Complaints</th>
                        <th>Resolved</th>
                        <th>Resolution Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($districtStats as $ds): 
                        $rate = ($ds['total'] > 0) ? round(($ds['resolved'] / $ds['total']) * 100, 1) : 0;
                        $displayName = $ds['district'] ?? $ds['district_name'] ?? 'Unknown District';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($displayName); ?></strong></td>
                        <td><?php echo $ds['total']; ?></td>
                        <td><?php echo $ds['resolved']; ?></td>
                        <td>
                            <div style="width: 100%; background: #eee; border-radius: 10px; height: 8px;">
                                <div style="width: <?php echo $rate; ?>%; background: var(--blue); height: 100%; border-radius: 10px;"></div>
                            </div>
                            <small><?php echo $rate; ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // 1. Chart Pai / Doughnut untuk Pengagihan Tahap Keterukan Aduan
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($priorityData, 'severity')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($priorityData, 'count')); ?>,
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: { 
                plugins: { 
                    legend: { position: 'bottom' } 
                } 
            }
        });

        // Pemprosesan Selamat Data Pasukan Teknikal
        const rawTeamStats = <?php echo json_encode($teamStats ? $teamStats : []); ?>;

        // Pemetaan nama pasukan secara dinamik
        const teamLabels = rawTeamStats.map(item => item.team_name || item.team_id || 'Team Unknown');
        
        // Membaca total_assigned berbanding total_done supaya nilai sifar (0) tidak merosakkan carta
        const teamDataValues = rawTeamStats.map(item => {
            const val = item.total_assigned ?? item.total_done ?? item.count ?? 0;
            return parseInt(val);
        });

        // 2. Chart Bar untuk Prestasi Penyelesaian Tugasan Pasukan Teknikal
        const teamCtx = document.getElementById('teamChart').getContext('2d');
        if (teamLabels.length > 0) {
            new Chart(teamCtx, {
                type: 'bar',
                data: {
                    labels: teamLabels,
                    datasets: [{
                        label: 'Assigned Tasks',
                        data: teamDataValues,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }]
                },
                options: { 
                    responsive: true,
                    scales: { 
                        y: { 
                            beginAtZero: true,
                            suggestedMax: 5, 
                            ticks: { stepSize: 1 }
                        } 
                    },
                    plugins: { 
                        legend: { display: false } 
                    } 
                }
            });
        } else {
            document.getElementById('teamChart').style.display = 'none';
            const node = document.createElement("p");
            node.setAttribute("style", "color: #64748b; font-size: 0.9rem; font-style: italic;");
            node.appendChild(document.createTextNode("No team activity records available to display."));
            document.getElementById('teamChart').parentNode.appendChild(node);
        }
    </script>
</body>
</html>