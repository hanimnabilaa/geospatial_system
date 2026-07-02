<?php
session_start();
require_once 'db.php';

/**
 * SMART CITY GIS - COMPLAINT PROCESSING MODULE
 * Optimized with Native PHP Geo-Deduplication to bypass MySQL Trigger Error 1442
 */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['user_id'])) {
        die("User session expired. Please log in again.");
    }

    $user_id = $_SESSION['user_id'];
    
    // 1. DATA CAPTURE
    $lat = $_POST['latitude'] ?? null;
    $lng = $_POST['longitude'] ?? null;
    $address = $_POST['address'] ?? '';
    $district_name = $_POST['district'] ?? ''; 
    $desc = $_POST['description'] ?? '';
    $infrastructure_type_id = $_POST['infrastructure_type_id'] ?? null;
    $status_id = $_POST['status_id'] ?? 1; // Default: 1 (New / Pending)
    $severity_label = $_POST['severity'] ?? 'Medium'; 
    $impact_label = $_POST['impact'] ?? 'Local';

    // 2. VALIDATION BOUNDARIES (MELAKA GIS LIMITS)
    if (!$lat || !$lng) {
        die("<script>alert('Error: Please pin a location on the map.'); window.history.back();</script>");
    }

    $minLat = 2.050; $maxLat = 2.500;
    $minLng = 101.950; $maxLng = 102.600;

    if ($lat < $minLat || $lat > $maxLat || $lng < $minLng || $lng > $maxLng) {
        die("<script>alert('Security Error: Location is outside Melaka boundaries.'); window.location='submit_complaint.php';</script>");
    }

    try {
        $pdo->beginTransaction();

        // --- STEP 0: LOOKUP DISTRICT ID ---
        $district_id = null;
        if (!empty($district_name)) {
            $distStmt = $pdo->prepare("SELECT district_id FROM melaka_districts WHERE LOWER(district_name) = LOWER(?) LIMIT 1");
            $distStmt->execute([trim($district_name)]);
            $district_id = $distStmt->fetchColumn() ?: null;
        }

        // --- STEP 1: GEOSPATIAL DEDUPLICATION CHECK ---
        // Mencari aduan aktif jenis infrastruktur sama dalam radius ~50 meter menggunakan formula Haversine
        $duplicate_found = false;
        $parent_complaint_id = null;
        
        $geoSql = "SELECT c.complaint_id, 
                    (6371000 * acos(cos(radians(?)) * cos(radians(l.latitude)) * cos(radians(l.longitude) - radians(?)) + sin(radians(?)) * sin(radians(l.latitude)))) AS distance 
                   FROM complaint c
                   JOIN location l ON c.location_id = l.location_id
                   WHERE c.infrastructure_type_id = ? AND c.status_id IN (1, 2)
                   HAVING distance <= 50 
                   ORDER BY distance ASC LIMIT 1";

        $geoStmt = $pdo->prepare($geoSql);
        $geoStmt->execute([$lat, $lng, $lat, $infrastructure_type_id]);
        $existing_case = $geoStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_case) {
            $duplicate_found = true;
            $parent_complaint_id = $existing_case['complaint_id'];
            $status_id = 4; // Auto-set status kepada ID 4 (Contoh: Duplicate / Merged)
            $desc = "[DUPLICATE OF #$parent_complaint_id] " . $desc;
        }

        // --- STEP 2: INSERT LOCATION ---
        $sqlLoc = "INSERT INTO location (latitude, longitude, address, district_id) VALUES (?, ?, ?, ?)";
        $pdo->prepare($sqlLoc)->execute([$lat, $lng, $address, $district_id]);
        $location_id = $pdo->lastInsertId();

        // --- STEP 3: IMAGE UPLOAD MANAGEMENT ---
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $folder = "uploads/";
            if (!file_exists($folder)) mkdir($folder, 0777, true);
            $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['image']['name']));
            $targetPath = $folder . $fileName;
            move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
            $imagePath = $targetPath;
        }

        // --- STEP 4: INSERT COMPLAINT ---
        // Jika aduan ini pendua, ia akan terus disimpan dengan status_id = 4 (Duplicate) secara selamat!
        $sqlComp = "INSERT INTO complaint 
        (description, severity, impact, date_reported, user_id, location_id, infrastructure_type_id, status_id, image_url, updated_by)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
        
        $pdo->prepare($sqlComp)->execute([
            $desc, $severity_label, $impact_label, $user_id, $location_id, $infrastructure_type_id, $status_id, $imagePath, $user_id
        ]);
        $complaint_id = $pdo->lastInsertId();

        // Commit DB changes
        $pdo->commit();
        
        // --- STEP 5: RECORD AUDIT LOG ---
        if ($duplicate_found) {
            $log_details = "Geo-Deduplication Triggered: Complaint #$complaint_id flagged as duplicate of #$parent_complaint_id due to spatial proximity.";
            recordAudit($pdo, 'COMPLAINT_DEDUPLICATION', $complaint_id, null, $log_details);
        } else {
            $log_details = "New complaint #$complaint_id submitted at $address under district $district_name. Severity: $severity_label.";
            recordAudit($pdo, 'COMPLAINT_SUBMISSION', $complaint_id, null, $log_details);
        }

        echo "<script>
            alert('" . ($duplicate_found ? "Aduan bertindih dikesan. Laporan anda telah digabungkan ke kes sedia ada (#$parent_complaint_id)." : "Complaint submitted successfully!") . "'); 
            window.location='index.php';
        </script>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: submit_complaint.php");
    exit;
}
?>