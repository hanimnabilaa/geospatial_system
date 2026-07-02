<?php
/* =====================================================
   GLOBAL INACTIVITY TIMEOUT CONFIGURATION (10 Minutes)
===================================================== */
// 10 minit dalam saat = 10 * 60 = 600 saat
$global_lifetime = 600; 

// Mengaktifkan jangka hayat sesi ke dalam browser dan server serentak
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => $global_lifetime,
        'gc_maxlifetime'  => $global_lifetime
    ]);
}

/* =====================================================
   DATABASE CONNECTION CONFIGURATION
===================================================== */
$host = 'localhost';
$dbname = 'maintenance_system'; 
$username = 'root';            
$password = '';                

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a real FYP presentation, showing a clean error is better than a giant stack trace
    die("Database Connection failed. Please check if MySQL is running.");
}

/**
 * FYP Audit Logger
 * captures: Who, What, When, and the change history.
 */
function recordAudit($pdo, $action, $complaint_id = null, $old = null, $new = null) {
    // Fallback to null if user is not logged in (e.g., public registration)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; 
    
    $sql = "INSERT INTO AUDIT_LOG (complaint_id, user_id, action_type, old_value, new_value, action_date) 
            VALUES (:cid, :uid, :act, :old, :new, NOW())";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cid' => $complaint_id,
            ':uid' => $user_id,
            ':act' => $action,
            ':old' => $old,
            ':new' => $new
        ]);
    } catch (PDOException $e) {
        // Log error to server instead of crashing the user's screen
        error_log("Audit Error: " . $e->getMessage());
    }
}
?>