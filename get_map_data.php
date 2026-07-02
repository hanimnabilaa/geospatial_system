<?php
// get_map_data.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    // Define Melaka Bounding Box Constants
    $minLat = 2.050; $maxLat = 2.500;
    $minLng = 101.950; $maxLng = 102.600;

    // We join COMPLAINT with LOCATION to get coordinates
    // Added WHERE clause to filter strictly by Melaka boundaries
    $sql = "SELECT 
                c.complaint_id, 
                c.description, 
                l.latitude, 
                l.longitude, 
                c.severity, 
                s.status_name, 
                c.date_reported
            FROM COMPLAINT c
            JOIN location l ON c.location_id = l.location_id
            JOIN status s ON c.status_id = s.status_id
            LEFT JOIN COMPLAINT_MERGE m ON c.complaint_id = m.duplicate_complaint_id
            WHERE m.duplicate_complaint_id IS NULL
            AND l.latitude BETWEEN :minLat AND :maxLat
            AND l.longitude BETWEEN :minLng AND :maxLng";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':minLat' => $minLat,
        ':maxLat' => $maxLat,
        ':minLng' => $minLng,
        ':maxLng' => $maxLng
    ]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>