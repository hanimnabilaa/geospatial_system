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
    // Fetch all complaints for this user, including priority scores and status names
    $sql = "SELECT 
                c.complaint_id, 
                c.description, 
                c.date_reported, 
                c.image_url,
                it.type_name, 
                s.status_name,
                ws.priority_score,
                l.address,
                l.district
            FROM COMPLAINT c
            JOIN infrastructure_type it ON c.infrastructure_type_id = it.infrastructure_type_id
            JOIN status s ON c.status_id = s.status_id
            JOIN LOCATION l ON c.location_id = l.location_id
            LEFT JOIN weight_score ws ON c.complaint_id = ws.complaint_id
            WHERE c.user_id = ?
            ORDER BY c.date_reported DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error fetching reports: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="status-style.css"> ```
    <meta charset="UTF-8">
    <title>My Reports - SmartCity GIS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; }
        .container { max-width: 1000px; margin: auto; }
        h1 { color: #1e3a8a; }
        .report-card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
        }
        .report-img { width: 150px; height: 150px; object-fit: cover; border-radius: 4px; background: #eee; }
        .report-info { flex: 1; }
        .badge { 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 0.85rem; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .priority-box { 
            text-align: center; 
            background: #1e3a8a; 
            color: white; 
            padding: 10px; 
            border-radius: 6px;
            min-width: 80px;
        }
        .meta { color: #666; font-size: 0.9rem; margin-top: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h1>My Reported Issues</h1>
    <p>Track the progress of your infrastructure reports below.</p>
    <hr><br>

    <?php if (empty($reports)): ?>
        <p>You haven't submitted any reports yet. <a href="submit_complaint.php">Report an issue here.</a></p>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <div class="report-card">
                <img src="<?= $report['image_url'] ? $report['image_url'] : 'assets/no-image.png' ?>" class="report-img" alt="Issue">

                <div class="report-info">
                    <div style="display:flex; justify-content: space-between; align-items: flex-start;">
                        <h3><?= htmlspecialchars($report['type_name']) ?></h3>
                        <span class="badge status-<?= strtolower(str_replace(' ', '', $report['status_name'])) ?>">
                            <?= htmlspecialchars($report['status_name']) ?>
                        </span>
                    </div>
                    
                    <p class="meta">📍 <?= htmlspecialchars($report['address']) ?>, <?= htmlspecialchars($report['district']) ?></p>
                    <p style="margin: 10px 0;"><?= htmlspecialchars($report['description']) ?></p>
                    <p class="meta">Reported on: <?= date('d M Y, h:i A', strtotime($report['date_reported'])) ?></p>
                </div>

                <div class="priority-box">
                    <small>Score</small>
                    <div style="font-size: 1.5rem; font-weight: bold;"><?= $report['priority_score'] ?? 'N/A' ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="index.php">← Back to Map</a>
    </div>
</div>

</body>
</html>