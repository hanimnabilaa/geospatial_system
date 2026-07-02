<?php
session_start();
require_once 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT 
                c.complaint_id, 
                c.description, 
                c.date_reported, 
                c.image_url,
                it.type_name, 
                COALESCE(ms.status_name, s.status_name) AS status_name,
                c.status_id, 
                ws.priority_score,
                l.address,
                md.district_name AS district,
                cm.newcomplaint_id AS master_complaint_id,
                CASE WHEN cm.duplicate_complaint_id IS NOT NULL THEN 1 ELSE 0 END AS is_merged
            FROM complaint c
            JOIN infrastructure_type it ON c.infrastructure_type_id = it.infrastructure_type_id
            JOIN status s ON c.status_id = s.status_id
            JOIN location l ON c.location_id = l.location_id
            LEFT JOIN melaka_districts md ON l.district_id = md.district_id
            LEFT JOIN weight_score ws ON c.complaint_id = ws.complaint_id
            
            LEFT JOIN complaint_merge cm ON c.complaint_id = cm.duplicate_complaint_id
            LEFT JOIN complaint mc ON cm.newcomplaint_id = mc.complaint_id
            LEFT JOIN status ms ON mc.status_id = ms.status_id
            
            WHERE c.user_id = ?
            ORDER BY c.date_reported DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error fetching reports: " . $e->getMessage());
}

function getPriorityInfo($score) {
    if ($score > 4) {
        return ['label' => 'High', 'color' => '#ef4444'];
    } elseif ($score >= 2) {
        return ['label' => 'Medium', 'color' => '#f59e0b'];
    } else {
        return ['label' => 'Low', 'color' => '#10b981'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - SmartCity GIS</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="status_style.css">
    <style>
        body {
            background-color: var(--dark-blue);
            background: linear-gradient(rgba(30, 58, 138, 0.95), rgba(30, 58, 138, 0.90));
            min-height: 100vh;
            padding: 0;
        }
        
        .container {
            padding-top: 40px;
            padding-bottom: 60px;
            max-width: 1000px;
            margin: auto;
        }

        .page-title {
            color: var(--white);
            margin-bottom: 30px;
            text-align: center;
        }

        .report-card {
            background: var(--white);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
        }

        .merge-indicator {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 8px 12px;
            border-radius: 0 8px 8px 0;
            margin-top: 10px;
            font-size: 0.85rem;
            color: #1e40af;
        }
        
        .badge-merged {
            background-color: #6b7280;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 5px;
        }

        /* 🎨 KELAS CSS STATUS YANG DIUBAH SUAI KELIHATAN LEBIH JELAS (Kalis Case-Sensitivity) */
        .status-new, .status-pending { 
            background-color: #fef3c7 !important; 
            color: #d97706 !important; 
            border: 1px solid #fcd34d !important; 
        }
        .status-resolved, .status-solve, .status-solved { 
            background-color: #d1fae5 !important; 
            color: #065f46 !important; 
            border: 1px solid #6ee7b7 !important; 
        }
        .status-duplicate, .status-merged { 
            background-color: #e5e7eb !important; 
            color: #374151 !important; 
            border: 1px solid #9ca3af !important; 
        }
        
        .badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">SmartCity GIS</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="submit_complaint.php">Report Issue</a>
        <a href="logout.php" class="btn-login">Logout</a>
    </nav>
</header>

<div class="container">
    <h1 class="page-title">My Infrastructure Reports</h1>

    <?php if (empty($reports)): ?>
        <div class="report-card" style="justify-content: center; text-align: center;">
            <div>
                <p style="margin-bottom: 15px; color: #4b5563;">You haven't submitted any reports yet.</p>
                <a href="submit_complaint.php" class="hero-btn" style="display:inline-block; font-size: 0.9rem;">Start a Report</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $report): 
            $p = getPriorityInfo($report['priority_score'] ?? 0);
            
            // 🛠️ PEMETAAN KELAS STATUS SECARA UNIFORM (strtolower)
            $rawStatus = strtolower(trim($report['status_name']));
            
            if ($report['is_merged'] == 1 || $report['status_id'] == 4 || $rawStatus == 'duplicate') {
                $statusClass = 'status-duplicate';
                $displayStatusName = 'Duplicate / Merged';
            } elseif ($rawStatus == 'resolved' || $rawStatus == 'solve' || $rawStatus == 'solved' || $report['status_id'] == 3) {
                $statusClass = 'status-resolved';
                $displayStatusName = 'Resolved';
            } else {
                $statusClass = 'status-pending';
                $displayStatusName = $report['status_name'];
            }
        ?>
            <div class="report-card">
                <img src="<?= $report['image_url'] ?: 'assets/no-image.png' ?>" class="report-img" alt="Report Image" style="width:150px; height:150px; object-fit:cover; border-radius:8px;">

                <div class="report-info" style="flex-grow: 1;">
                    <div style="display:flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                        <span class="priority-pill" style="background: <?= $p['color'] ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                            <?= $p['label'] ?> Priority
                        </span>
                        <h3 style="margin:0; color: #1e3a8a;"><?= htmlspecialchars($report['type_name']) ?></h3>
                    </div>
                    
                    <p class="meta" style="color: #666; font-size: 0.9rem;">
                        📍 <?= htmlspecialchars($report['address']) ?> <?= !empty($report['district']) ? ' (' . htmlspecialchars($report['district']) . ')' : '' ?>
                    </p>
                    <p style="font-size: 0.95rem; margin: 10px 0; color: #4b5563; line-height: 1.4;">
                        <?= nl2br(htmlspecialchars($report['description'])) ?>
                    </p>
                    <p class="meta" style="color: #666; font-size: 0.85rem;">Submitted: <?= date('d M Y', strtotime($report['date_reported'])) ?></p>

                    <?php if ($report['is_merged'] == 1): ?>
                        <div class="merge-indicator">
                            ℹ️ This issue has been linked with an existing report at this location (Case reference: <strong>#<?= $report['master_complaint_id'] ?></strong>). We are tracking updates via this combined case.
                        </div>
                    <?php endif; ?>
                </div>

                <div style="text-align: right; min-width: 130px;">
                    <?php if ($report['is_merged'] == 1): ?>
                        <div class="badge-merged">Linked / Merged</div>
                    <?php endif; ?>
                    <div class="badge <?= $statusClass ?>">
                        <?= htmlspecialchars($displayStatusName) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 20px; margin-top: 40px;">
    &copy; 2026 SmartCity GIS Portal | Improving Urban Living
</footer>

</body>
</html>