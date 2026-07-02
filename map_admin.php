<?php
session_start();
require_once 'db.php';

// 1. Security Check
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    // Fetch all active/unique complaints with coordinates
    // ✅ FIXED: Changed l.district to md.district_name and added the left join for melaka_districts
    $sqlMap = "SELECT c.complaint_id, c.description, c.severity, md.district_name AS district, l.latitude, l.longitude, c.date_reported 
               FROM COMPLAINT c
               LEFT JOIN LOCATION l ON c.location_id = l.location_id
               LEFT JOIN melaka_districts md ON l.district_id = md.district_id
               LEFT JOIN COMPLAINT_MERGE d ON c.complaint_id = d.duplicate_complaint_id
               WHERE d.duplicate_complaint_id IS NULL 
               AND l.latitude IS NOT NULL AND l.longitude IS NOT NULL";
    
    $mapData = $pdo->query($sqlMap)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GIS Map View - SmartCity Admin</title>
    <link rel="stylesheet" href="admin_styles.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        #map-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 160px); /* Dynamically fills the viewport space below headers */
            min-height: 500px;
            background-color: #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        #map {
            width: 100%;
            height: 100%; /* Explicitly instructs leaflet to scale to the viewport wrapper */
            z-index: 1;
        }

        .map-legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: #ffffff;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000; /* Forces layer layout above map rendering space */
            font-size: 0.85rem;
            pointer-events: auto;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
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
            <li><a href="map_admin.php" class="active">📍 GIS Map View</a></li>
            <li><a href="manage_inquiries.php">✉️ User Inquiries</a></li>
            <li><a href="audit_logs.php">📜 Audit Logs</a></li>
            <li><a href="reports.php">📊 System Reports</a></li>
            <li><a href="Priority_settings.php">⚙️ Priority Settings</a></li>
            <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="header">
            <h1>Infrastructure GIS Map</h1>
            <div class="user-info">Active Complaints: <strong><?php echo count($mapData); ?></strong></div>
        </div>

        <div id="map-container">
            <div id="map"></div>
            
            <div class="map-legend">
                <strong>Severity Level</strong>
                <div class="legend-item"><span class="dot" style="background: #ef4444;"></span> High Priority</div>
                <div class="legend-item"><span class="dot" style="background: #f59e0b;"></span> Medium Priority</div>
                <div class="legend-item"><span class="dot" style="background: #10b981;"></span> Low Priority</div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Initialize Map (Centered on default location)
        const map = L.map('map').setView([3.1390, 101.6869], 12); 

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Map Data from PHP
        const complaints = <?php echo json_encode($mapData); ?>;

        complaints.forEach(complaint => {
            // Determine marker color based on severity
            let color = '#10b981'; // Default Low
            if (complaint.severity === 'High') color = '#ef4444';
            else if (complaint.severity === 'Medium') color = '#f59e0b';

            // Create a circle marker
            const marker = L.circleMarker([parseFloat(complaint.latitude), parseFloat(complaint.longitude)], {
                radius: 8,
                fillColor: color,
                color: "#fff",
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);

            // ✅ FIXED: Safely completed the template literal string syntax and closed block
            marker.bindPopup(`
                <div style="font-family: sans-serif; min-width: 160px;">
                    <h4 style="margin:0; color:#1e3a8a;">ID: #${complaint.complaint_id}</h4>
                    <p style="margin: 5px 0; font-size: 13px;"><strong>Issue:</strong> ${complaint.description}</p>
                    <p style="margin: 5px 0; font-size: 13px;"><strong>District:</strong> ${complaint.district || 'N/A'}</p>
                    <p style="margin: 5px 0; font-size: 13px;"><strong>Severity:</strong> <span style="color:${color}; font-weight:bold;">${complaint.severity}</span></p>
                    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 8px 0;">
                    <a href="manage_complaints.php?id=${complaint.complaint_id}" style="color:#3b82f6; text-decoration:none; font-size:12px; font-weight: bold;">View Details →</a>
                </div>
            `);
        });

        // Auto-fit bounds if markers exist
        if (complaints.length > 0) {
            const group = new L.featureGroup(complaints.map(c => L.marker([parseFloat(c.latitude), parseFloat(c.longitude)])));
            map.fitBounds(group.getBounds().pad(0.1));
        }

        // Recalculate size rendering dimensions to prevent broken map grid fragments
        setTimeout(() => {
            map.invalidateSize();
        }, 200);
    </script>
</body>
</html>