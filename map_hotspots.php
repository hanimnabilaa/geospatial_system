<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'technician') {
    header("Location: login_technician.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priority Hotspots | SmartCity GIS</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --dark-blue: #0f172a; --blue: #3b82f6; }
        
        /* FIX: Ensure parent containers take up 100% height explicitly */
        body, html { 
            margin: 0; 
            padding: 0; 
            height: 100%; 
            width: 100%;
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
            overflow: hidden; 
        }
        
        #map { 
            height: calc(100vh - 60px); 
            width: 100%; 
            background: #e5e7eb; 
            display: block;
        }
        
        .header { 
            height: 60px; 
            background: var(--dark-blue); 
            color: white; 
            display: flex; 
            align-items: center; 
            padding: 0 20px; 
            justify-content: space-between;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

<div class="header">
    <div style="font-weight:bold; color: var(--blue);">SMARTCITY <span style="color:white">HOTSPOTS (MELAKA)</span></div>
    <a href="dashboard_tech.php" style="color:white; text-decoration:none; font-size: 0.9rem;">← Back to Dashboard</a>
</div>

<div id="map"></div>

<!-- Leaflet Core JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- 💡 FIX: Switched from unstable raw GitHub link to a secure, verified production CDNjs link -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>

<script>
    // 1. DEFINE MELAKA BOUNDARIES (The "Geofence")
    const melakaBounds = L.latLngBounds(
        L.latLng(2.050, 101.950), // Southwest corner
        L.latLng(2.500, 102.600)  // Northeast corner
    );

    // 2. INITIALIZE MAP WITH RESTRICTIONS
    var map = L.map('map', {
        maxBounds: melakaBounds,        // Restricts where the user can pan
        maxBoundsViscosity: 1.0,        // Makes the boundary "solid"
        minZoom: 10                     // Prevents zooming out too far
    }).setView([2.314, 102.318], 12); 

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        bounds: melakaBounds           // Only load tiles within Melaka
    }).addTo(map);

    // 3. FETCH AND RENDER
    fetch('get_map_data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP network error! Status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Safety Check: Verify if database array is empty or structurally broken
            if (!data || data.length === 0) {
                console.warn("get_map_data.php returned an empty array or null data.");
                return;
            }

            var heatPoints = data
                .filter(issue => {
                    if (!issue.latitude || !issue.longitude) return false;
                    
                    let lat = parseFloat(issue.latitude);
                    let lng = parseFloat(issue.longitude);
                    
                    // Validate numbers and check against Melaka geofence coordinates
                    return !isNaN(lat) && !isNaN(lng) && lat >= 2.050 && lat <= 2.500 && lng >= 101.950 && lng <= 102.600;
                })
                .map(issue => {
                    let intensity = 0.4;
                    if (issue.severity === "High") intensity = 1.0;
                    if (issue.severity === "Medium") intensity = 0.7;

                    return [parseFloat(issue.latitude), parseFloat(issue.longitude), intensity];
                });

            // Only add heat layer if we actually extracted valid coordinate points
            if (heatPoints.length > 0) {
                var heat = L.heatLayer(heatPoints, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 17,
                    gradient: {0.4: 'blue', 0.6: 'cyan', 0.7: 'lime', 0.8: 'yellow', 1.0: 'red'}
                }).addTo(map);
            } else {
                console.warn("Data rows found, but zero rows matched the Melaka geofence boundaries.");
            }
        })
        .catch(err => {
            console.error('GIS Heatmap Initialization Error:', err);
        });
</script>
</body>
</html>