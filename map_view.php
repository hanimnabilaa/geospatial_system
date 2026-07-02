<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GIS Map View | SmartCity GIS</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --dark-blue: #0f172a;
            --blue: #3b82f6;
            --white: #ffffff;
        }
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Inter', sans-serif; background: #f8fafc; }
        #map { height: calc(100vh - 60px); width: 100%; }
        .header { 
            height: 60px; background: var(--dark-blue); color: white; 
            display: flex; align-items: center; padding: 0 20px; justify-content: space-between;
        }
        .info.legend {
            padding: 10px; background: rgba(255,255,255,0.9);
            box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 8px; line-height: 24px;
        }
        .legend i { width: 14px; height: 14px; float: left; margin-right: 8px; margin-top: 5px; border-radius: 50%; }
        /* Success Toast Styling */
.toast { 
    position: fixed; 
    top: 20px; 
    right: 20px; 
    background: #10b981; 
    color: white; 
    padding: 16px 24px; 
    border-radius: 8px; 
    z-index: 9999; 
    display: none; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-weight: 600;
}
    </style>
</head>
<body>
    <div id="toast" class="toast">✅ Success! Work order has been updated.</div>

<div class="header">
    <div style="font-weight:bold; color: var(--blue);">SMARTCITY <span style="color:white">GIS</span></div>
    <a href="dashboard_tech.php" style="color:white; text-decoration:none; font-size: 0.9rem;">← Back to Dashboard</a>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // 1. Setup the Map
    var map = L.map('map').setView([2.314, 102.318], 14); 

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // 2. Fetch Data
    fetch('get_map_data.php')
        .then(response => response.json())
        .then(data => {
            console.log("Database Data:", data); // Check F12 Console to see if this prints!

            if (!data || data.length === 0) {
                alert("No reports found in the database.");
                return;
            }

            var markerBounds = [];

            data.forEach(issue => {
                // IMPORTANT: Ensure these names match your SQL column names exactly
                const lat = parseFloat(issue.latitude);
                const lng = parseFloat(issue.longitude);

                if (isNaN(lat) || isNaN(lng)) {
                    console.warn("Skipping report due to invalid coordinates:", issue);
                    return;
                }

                // Severity Colors
                let color = "#10b981"; // Low
                if (issue.severity === "High") color = "#ef4444";
                if (issue.severity === "Medium") color = "#f59e0b";

                markerBounds.push([lat, lng]);

                // Create Marker
                L.circleMarker([lat, lng], {
                    radius: 10,
                    fillColor: color,
                    color: "#fff",
                    weight: 2,
                    fillOpacity: 0.9
                })
                .addTo(map)
                // Locate this line inside your data.forEach loop in map_view.php:
.bindPopup(`
    <div style="min-width: 160px;">
        <strong style="font-size: 14px;">${issue.description}</strong><br>
        <hr style="margin: 5px 0; border:0; border-top:1px solid #eee;">
        <b>Status:</b> ${issue.status_name}<br>
        <b>Severity:</b> <span style="color:${color}">${issue.severity}</span><br>
        <b>Date:</b> ${new Date(issue.date_reported).toLocaleDateString()}<br><br>
        
        <a href="update_complaint.php?id=${issue.complaint_id}&from=map" 
           style="display:block; text-align:center; background:#3b82f6; color:white; padding:6px; border-radius:4px; text-decoration:none; font-weight:bold;">
           Update Task
        </a>
    </div>
`);
            });

            // 3. Zoom to show all markers
            if (markerBounds.length > 0) {
                map.fitBounds(markerBounds, { padding: [50, 50] });
            }
        })
        .catch(err => {
            console.error('Critical Error:', err);
            alert("Check Console (F12) - The PHP file might have an error.");
        });

    // Legend Logic
    var legend = L.control({position: 'bottomright'});
    legend.onAdd = function (map) {
        var div = L.DomUtil.create('div', 'info legend'),
            grades = ["High Priority", "Medium Priority", "Low Priority"],
            colors = ["#ef4444", "#f59e0b", "#10b981"];

        div.innerHTML = '<strong style="display:block; margin-bottom:5px;">Legend (30 Days)</strong>';
        for (var i = 0; i < grades.length; i++) {
            div.innerHTML += '<i style="background:' + colors[i] + '"></i> ' + grades[i] + '<br>';
        }
        return div;
    };
    legend.addTo(map);
    // Check for the 'updated' message in the URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('msg') === 'updated') {
    const toast = document.getElementById('toast');
    toast.style.display = 'block';
    
    // Hide after 3 seconds and clean the URL
    setTimeout(() => {
        toast.style.display = 'none';
        // This removes "?msg=updated" from the address bar without reloading
        window.history.replaceState({}, document.title, window.location.pathname);
    }, 3000);
}
</script>
</body>
</html>