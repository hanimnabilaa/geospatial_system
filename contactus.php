<?php
session_start();
require_once 'db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$session_id = $_SESSION['user_id'];
$message_sent = false;
$error_msg = "";

// 1. Fetch User Info
try {
    $stmtUser = $pdo->prepare("SELECT user_name, user_email FROM user WHERE user_id = ?");
    $stmtUser->execute([$session_id]);
    $user = $stmtUser->fetch();
    
    if (!$user) {
        die("User profile not found.");
    }
} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}

// 2. Process Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_inquiry'])) {
    $inquiry_type = htmlspecialchars($_POST['inquiry_type']);
    $message      = htmlspecialchars($_POST['message']);
    $rating       = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $full_name    = $user['user_name'];  
    $email        = $user['user_email']; 
    $ip_address   = $_SERVER['REMOTE_ADDR'];

    try {
        $sql = "INSERT INTO contact_inquiries (full_name, email, inquiry_type, message, rating, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$full_name, $email, $inquiry_type, $message, $rating, $ip_address])) {
            $message_sent = true;
            $new_id = $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        $error_msg = "Failed to save feedback: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact & Feedback - SmartCity GIS</title>
    <style>
        :root {
            --dark-blue: #1e3a8a;
            --blue: #3b82f6;
            --white: #ffffff;
            --light-bg: #f3f4f6;
            --verified-bg: #f8fafc;
            --verified-text: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--light-bg); padding-bottom: 40px; }
        
        header { background: var(--dark-blue); color: white; padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; }
        header nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: 500; }

        .container { max-width: 750px; margin: 40px auto; padding: 0 20px; }
        .form-card { background: var(--white); padding: 35px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        h1 { color: var(--dark-blue); margin-bottom: 25px; font-size: 1.8rem; border-bottom: 2px solid var(--light-bg); padding-bottom: 15px; }

        /* Alert Notifications */
        .alert { 
            padding: 15px; border-radius: 8px; margin-bottom: 20px; position: relative; 
            font-weight: 600; display: flex; align-items: center; justify-content: space-between;
            animation: slideDown 0.4s ease-out;
        }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .close-btn { cursor: pointer; font-size: 1.2rem; }

        /* Verified Form Fields Styles */
        .verified-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.85rem; color: var(--dark-blue); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .verified-field { 
            background: var(--verified-bg); 
            border: 1px solid #e2e8f0; 
            padding: 12px; 
            border-radius: 6px; 
            color: var(--verified-text); 
            font-size: 1rem; 
            display: flex; 
            align-items: center;
            justify-content: space-between;
        }
        .verified-tag { font-size: 0.7rem; background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-weight: bold; }

        /* Input Styles */
        select, textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; transition: border 0.3s; }
        select:focus, textarea:focus { outline: none; border-color: var(--blue); }
        textarea { height: 130px; resize: vertical; }

        .rating-group { display: flex; gap: 20px; padding: 10px 0; }
        .rating-option { display: flex; align-items: center; gap: 5px; cursor: pointer; font-weight: bold; }
        
        .btn-submit { background-color: var(--blue); color: white; padding: 16px; border: none; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: var(--dark-blue); transform: translateY(-1px); }
    </style>
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

<div class="container">
    <?php if ($message_sent): ?>
        <div class="alert alert-success" id="notif">
            <span>✅ Inquiry Submitted! Thank You For Your Feedback and Inquiries.</span>
            <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-error" id="notif">
            <span>❌ <?php echo $error_msg; ?></span>
            <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <h1>Contact Support</h1>

        <div class="verified-row">
            <div class="form-group">
                <label>Full Name</label>
                <div class="verified-field">
                    <?php echo htmlspecialchars($user['user_name']); ?>
                    <span class="verified-tag">VERIFIED</span>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <div class="verified-field">
                    <?php echo htmlspecialchars($user['user_email']); ?>
                    <span class="verified-tag">VERIFIED</span>
                </div>
            </div>
        </div>

        <form action="contactus.php" method="POST">
            <div class="form-group">
                <label>Inquiry Category</label>
                <select name="inquiry_type" required>
                    <option value="General Feedback">General System Feedback</option>
                    <option value="Feature Suggestion">Feature Suggestion</option>
                    <option value="Technical Support">Technical Support</option>
                    <option value="Report Data Error">Report GIS Data Error</option>
                </select>
            </div>

            <div class="form-group">
                <label>Overall Experience (1-5 ⭐)</label>
                <div class="rating-group">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <label class="rating-option">
                            <input type="radio" name="rating" value="<?php echo $i; ?>" style="width: auto;" <?php if($i==5) echo 'checked'; ?>> 
                            <?php echo $i; ?>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Detailed Message</label>
                <textarea name="message" placeholder="Type your inquiry or feedback here..." required></textarea>
            </div>

            <button type="submit" name="submit_inquiry" class="btn-submit">Submit</button>
        </form>
    </div>
</div>

<script>
    // Notifications auto-fade after 6 seconds
    setTimeout(function() {
        const notifs = document.querySelectorAll('.alert');
        notifs.forEach(el => {
            el.style.transition = "opacity 0.6s ease";
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 600);
        });
    }, 6000);
</script>

</body>
</html>