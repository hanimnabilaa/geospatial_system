<?php 
session_start(); 

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Check if the role is exactly 'Citizen'
// We use strtolower and trim to prevent issues with spacing or capitalization
if (strtolower(trim($_SESSION['role'])) !== 'citizen') {
    // If they are a technician, they should go to their own dashboard
    if (strtolower(trim($_SESSION['role'])) === 'technician') {
        header("Location: dashboard_tech.php");
    } else {
        // Otherwise, send back to login
        header("Location: login.php?error=unauthorized");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geospatial-Aware Infrastructure Management System</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="logo">SmartCity GIS</div>
        <nav>
            <a href="index.php">Home</a>
            <a href="map.php">Map View</a>
            
            <?php if (isset($_SESSION['user_name'])) { ?>
                <a href="view_status.php" style="color: var(--yellow); font-weight: bold;">My Reports</a>
             <a href="contactus.php">Contact Us</a>
                
                <span style="color:white; margin-left:20px; background:var(--green); padding:5px 10px; border-radius:5px;">
                    👋 <?php echo $_SESSION['user_name']; ?>
                </span>

                <a href="logout.php" class="btn-login">Logout</a>
            <?php } else { ?>
                <a href="register.php">Register</a>
                <a href="login.php" class="btn-login">Login</a>
            <?php } ?>
        </nav>
    </header>

    <section class="hero">
        <h1>Geospatial-Aware Infrastructure Management</h1>
        <p>A smart platform for citizens to report infrastructure problems. Featuring automated priority scoring and geospatial duplicate detection.</p>
        <a href="submit_complaint.php" class="hero-btn">Report an Issue Now</a>
    </section>

    <div class="container">
        <h2 class="section-title">Core System Capabilities</h2>
        
        <div class="grid">
            <div class="card">
                <h3>📍 Spatial Deduplication</h3>
                <p>Advanced mapping ensures duplicate reports within 20m are automatically merged, preventing redundant technician dispatches.</p>
            </div>

            <div class="card">
                <h3>⚖️ Priority Scoring</h3>
                <p>Complaints are dynamically prioritized using weighted formulas based on severity and impact modifier.</p>
            </div>

            <div class="card" style="border-top-color: var(--green);">
                <h3>📑 Status Tracking</h3>
                <p>Monitor the live status of your reports—from "Pending" to "Resolved"—with full transparency on priority scores.</p>
                <?php if (isset($_SESSION['user_name'])) { ?>
                    <a href="view_status.php" style="color: var(--blue); text-decoration: none; font-weight: bold; display: block; margin-top: 10px;">Check My Status →</a>
                <?php } ?>
            </div>

            <div class="card">
                <h3>🔒 PDPA-Compliant</h3>
                <p>Comprehensive data masking and secure audit logs track every status change, ensuring security and privacy.</p>
            </div>

            <div class="card">
                <h3>📊 Visual Dashboards</h3>
                <p>Data-rich visualization for technicians and admins, featuring map hotspots and SLA performance tracking.</p>
            </div>
        </div>
    </div>

    <section class="roles-section">
        <div class="container">
            <h2 class="section-title">Designed for Every Stakeholder</h2>
            <div class="role-badges">
                <div class="badge badge-citizen">👤 Citizens (Report)</div>
                <div class="badge badge-tech">🔧 Technicians (Fix)</div>
                <div class="badge badge-admin">🛡️ Admins (Manage)</div>
            </div>
        </div>
    </section>

    <div class="container" style="text-align: center; margin-top: -30px;">
        <h3 style="color: var(--dark-blue); margin-bottom: 15px;">Priority Classifications</h3>
        <span style="margin: 0 15px;"><span class="priority-indicator" style="background-color: var(--red);"></span> High (> 4)</span>
        <span style="margin: 0 15px;"><span class="priority-indicator" style="background-color: var(--yellow);"></span> Medium (2-4)</span>
        <span style="margin: 0 15px;"><span class="priority-indicator" style="background-color: var(--green);"></span> Low (< 2)</span>
    </div>

    <footer>
        <p>&copy; 2026 Geospatial-Aware Infrastructure Management System. All rights reserved.</p>
    </footer>

</body>
</html>