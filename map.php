<?php 
session_start(); 

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Check if the role is exactly 'Citizen'
if (strtolower(trim($_SESSION['role'])) !== 'citizen') {
    if (strtolower(trim($_SESSION['role'])) === 'technician') {
        header("Location: dashboard_tech.php");
    } else {
        header("Location: login.php?error=unauthorized");
    }
    exit();
}

// DATABASE CONNECTION
// Replace this mock data array with your real database query fetch:
// e.g., SELECT latitude, longitude, title, status, priority_score FROM complaints WHERE status != 'Resolved'
$complaints_json = json_encode([
    [
        "lat" => -6.2088, "lng" => 106.8456, 
        "title" => "Major Water Pipe Leak", "status" => "Pending", "priority" => 4.5
    ],
    [
        "lat" => -6.2100, "lng" => 106.8420, 
        "title" => "Severe Pothole", "status" => "In Progress", "priority" => 3.2
    ],
    [
        "lat" => -6.2050, "lng" => 106.8470, 
        "title" => "Broken Streetlight", "status" => "Resolved", "priority" => 1.8
    ]
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map View - SmartCity GIS</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- Leaflet CSS for Map Rendering -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <style>
        /* Map-specific Layout Styling */
        .map-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 30px;
        }
        #map {
            height: 520px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 1;
        }
        .map-instructions {
            background: #f4f7f6;
            padding: 15px;
            border-left: 5px solid var(--blue, #2196F3);
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .priority-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">SmartCity GIS</div>
        <nav>
            <a href="index.php">Home</a>
            <a href="map.php" class="active">Map View</a>
            
            <?php if (isset($_SESSION['user_name'])) { ?>
                <a href="view_status.php" style="color: var(--yellow); font-weight: bold;">My Reports</a>
                <a href="contactus.php">Contact Us</a>
                
                <span style="color:white; margin-left:20px; background:var(--green); padding:5px 10px; border-radius:5px;">
                    👋 <?php echo $_SESSION['user_name']; ?>
                </span>

                <a href="logout.php" class="btn-login">Logout</a>
            <?php } ?>
        </nav>
    </header>

    <div class="container map-container">
        <div>
            <h2>📍 Citizen Infrastructure Map</h2>
            <p>Explore reported issues around your neighborhood. Use this live map to check status updates or verify if an issue has already been reported.</p>
        </div>

        <div class="map-instructions">
            <strong>💡 Spatial Deduplication Alert:</strong> 
            Before submitting a new ticket, check if a marker already exists for your issue. New reports within a **20-meter radius** of an existing live issue will be automatically merged into the master ticket to avoid redundant dispatches.
        </div>

        <!-- Map Workspace -->
        <div id="map"></div>

        <!-- Priority Legend Mapping -->
        <div style="text-align: center; margin-top: 10px; background: #fafafa; padding: 15px; border-radius: 8px;">
            <h4 style="margin-bottom: 10px;">Marker Color Key (Priority Rating):</h4>
            <span style="margin: 0 15px;"><span class="priority-dot" style="background:red;"></span> High (> 4)</span>
            <span style="margin: 0 15px;"><span class="priority-dot" style="background:orange;"></span> Medium (2-4)</span>
            <span style="margin: 0 15px;"><span class="priority-dot" style="background:green;"></span> Low (< 2)</span>
        </div>
        
        <div style="text-align: center; margin-bottom: 40px;">
            <a href="submit_complaint.php" class="hero-btn" style="display: inline-block; padding: 12px 30px;">Report an Issue Now</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Geospatial-Aware Infrastructure Management System. All rights reserved.</p>
    </footer>

    <!-- Leaflet JavaScript library -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // 1. Initialize Map view (Default coordinates centered. e.g., Jakarta region)
        var map = L.map('map').setView([-6.2088, 106.8456], 14);

        // 2. Load and display OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // 3. Inject PHP dataset array directly into JS
        var complaints = <?php echo $complaints_json; ?>;

        // 4. Color assignment function matches backend priority rules
        function getPriorityColor(priority) {
            return priority > 4   ? 'red' :
                   priority >= 2  ? 'orange' :
                                    'green';
        }

        // 5. Populate markers onto the map
        complaints.forEach(function(complaint) {
            var markerColor = getPriorityColor(complaint.priority);
            
            // Using CircleMarkers for sleek GIS look and dynamic colors
            var circle = L.circleMarker([complaint.lat, complaint.lng], {
                color: markerColor,
                fillColor: markerColor,
                fillOpacity: 0.5,
                radius: 10
            }).addTo(map);

            // Pop-up details optimized for general public (PDPA compliant: hiding submitter info)
            var popupContent = `
                <div style="font-family: Arial, sans-serif; min-width: 180px;">
                    <h4 style="margin:0 0 5px 0; color:#333;">${complaint.title}</h4>
                    <p style="margin:0 0 5px 0;"><b>Status:</b> ${complaint.status}</p>
                    <p style="margin:0;"><b>Priority Score:</b> ${complaint.priority}</p>
                    <hr style="margin: 8px 0; border:0; border-top:1px solid #eee;">
                    <small style="color:#666;">20m radius duplicate prevention rule applies here.</small>
                </div>
            `;
            
            circle.bindPopup(popupContent);
        });

        // 6. Request device geolocation to center map directly over the Citizen's area
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                
                map.setView([userLat, userLng], 15);
                
                // Drop pin on citizen's live position
                L.marker([userLat, userLng]).addTo(map)
                    .bindPopup('<b>Your Current Location</b>')
                    .openPopup();
                
                // Visual education guide: Show a blue shade highlighting a 20-meter zone around user
                L.circle([userLat, userLng], {
                    color: '#2196F3',
                    fillColor: '#2196F3',
                    fillOpacity: 0.1,
                    radius: 20
                }).addTo(map);

            }, function() {
                console.log("Location access denied by user.");
            });
        }
    </script>
</body>
</html>