<?php
session_start();
require_once 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint - SmartCity GIS</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet Control Geocoder CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --dark-blue: #1e3a8a;
            --blue: #3b82f6;
            --grey: #6b7280;
            --white: #ffffff;
            --light-bg: #f3f4f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { 
            background-color: var(--light-bg); 
            color: #333; 
            padding-bottom: 40px; 
        }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .form-card { background: var(--white); padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        h1 { color: var(--dark-blue); margin-bottom: 10px; font-size: 1.8rem; }
        p.subtitle { color: var(--grey); margin-bottom: 30px; font-size: 0.95rem; }

        #map { height: 350px; width: 100%; border-radius: 8px; margin-bottom: 20px; border: 2px solid #e2e8f0; z-index: 1; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; }
        
        .coords-info { background: #f8fafc; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; color: var(--dark-blue); border: 1px dashed var(--blue); margin-bottom: 20px; }
        
        .btn-submit { background-color: var(--blue); color: white; padding: 15px 30px; border: none; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; width: 100%; transition: background 0.3s; }
        .btn-submit:hover { background-color: var(--dark-blue); }

        header { margin-bottom: 0; }

        /* Custom adjustments for the search UI layout inside the map container */
        .leaflet-control-geocoder {
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<header>
    <div class="logo">SmartCity GIS</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="view_status.php">My Reports</a>
        <a href="logout.php" class="btn-login">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="form-card">
        <h1>Report Infrastructure Issue</h1>
        <p class="subtitle">Use the magnifying glass icon on the map to search, or click directly to pin the location.</p>

        <form action="process_complaint.php" method="POST" enctype="multipart/form-data">
            <label style="font-weight:600; display:block; margin-bottom:10px;">Step 1: Mark Location on Map</label>
            <div id="map"></div>

            <div class="coords-info">
                📍 <span id="display-coords">Click map or use map search to pin location</span>
            </div>

            <input type="hidden" name="latitude" id="lat" required>
            <input type="hidden" name="longitude" id="lng" required>
            <input type="hidden" name="status_id" value="1">

            <div class="form-grid">
                <div class="form-group">
                    <label>Issue Category</label>
                    <select name="infrastructure_type_id" required>
                        <option value="">-- Select --</option>
                        <option value="1">Road Damage</option>
                        <option value="2">Water Leak</option>
                        <option value="3">Streetlight</option>
                        <option value="4">Drainage</option>
                        <option value="5">Waste</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>District</label>
                    <select name="district" required>
                        <option value="">-- Select District --</option>
                        <option value="Melaka Tengah">Melaka Tengah</option>
                        <option value="Alor Gajah">Alor Gajah</option>
                        <option value="Jasin">Jasin</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Exact Street Address</label>
                    <input type="text" name="address" id="street-address" placeholder="e.g. No 15, Jalan Hang Tuah" required>
                </div>

                <div class="form-group">
                    <label>Severity (Urgency)</label>
                    <select name="severity" required>
                        <option value="Low">Low (No immediate danger)</option>
                        <option value="Medium" selected>Medium (Standard issue)</option>
                        <option value="High">High (Potentially dangerous)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Impact (Scale)</label>
                    <select name="impact" required>
                        <option value="Local" selected>Local (Individual spot)</option>
                        <option value="Wide">Wide (Affects whole street/neighborhood)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Upload Photo</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>

                <div class="form-group full-width">
                    <label>Detailed Description</label>
                    <textarea name="description" rows="4" placeholder="Please provide more details about the issue..." required></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Submit Official Report</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Leaflet Control Geocoder JavaScript Plugin -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
// 1. Define Melaka Bounding Box
const melakaBounds = L.latLngBounds(
    L.latLng(2.050, 101.950), // Southwest
    L.latLng(2.500, 102.600)  // Northeast
);

// 2. Initialize map with bounds
const map = L.map('map', {
    maxBounds: melakaBounds,      
    maxBoundsViscosity: 1.0       
}).setView([2.314, 102.318], 12); 
    
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

map.setMinZoom(10);

let marker;

// Helper function to update marker position and coordinate elements
function updateLocation(latlng, addressText = null) {
    // Validation: Check boundary limits
    if (!melakaBounds.contains(latlng)) {
        alert("This system only accepts reports within Melaka District.");
        return false;
    }

    document.getElementById('lat').value = latlng.lat;
    document.getElementById('lng').value = latlng.lng;
    document.getElementById('display-coords').innerText = 
        `Lat: ${latlng.lat.toFixed(6)}, Lng: ${latlng.lng.toFixed(6)}`;

    if(marker) {
        marker.setLatLng(latlng);
    } else {
        marker = L.marker(latlng).addTo(map);
    }

    // Optional: Autofill street address input field if search results returned a structural string
    if(addressText) {
        document.getElementById('street-address').value = addressText;
    }
    
    return true;
}

// Map Click Listener
map.on('click', function(e){
    updateLocation(e.latlng);
});

// 3. Integrate Geocoder Search Control Unit
const geocoder = L.Control.geocoder({
    defaultMarkGeocode: false, // Turn off plugin's default fallback marker setup
    placeholder: "Search location in Melaka...",
    errorMessage: "Location not found."
})
.on('markgeocode', function(e) {
    const latlng = e.geocode.center;
    const addressName = e.geocode.name;
    
    // Attempt processing the location data
    const success = updateLocation(latlng, addressName);
    
    if (success) {
        map.setView(latlng, 16); // Dynamic close-up zoom structural view
    }
})
.addTo(map);
</script>
</body>
</html>