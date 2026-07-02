<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'technician') {
    header("Location: login_technician.php");
    exit();
}

if (!isset($_GET['id'])) { header("Location: dashboard_tech.php"); exit(); }

$complaint_id = $_GET['id'];
$from_map = (isset($_GET['from']) && $_GET['from'] === 'map');
$back_url = $from_map ? 'map_view.php' : 'dashboard_tech.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status_id'];
    $remarks = $_POST['completion_remarks'] ?? '';
    $image_path = null;

    // --- ENHANCED FILE UPLOAD LOGIC ---
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        
        $file_info = getimagesize($_FILES['proof_image']['tmp_name']); // Check if it's a real image
        if ($file_info !== false) {
            $file_ext = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
            $file_name = "proof_" . $complaint_id . "_" . time() . "." . $file_ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            }
        }
    }

    try {
        // Base SQL
        if ($new_status == 3) {
            $sql = "UPDATE COMPLAINT SET status_id = ?, date_resolved = NOW(), completion_remarks = ?";
            $params = [$new_status, $remarks];
        } else {
            $sql = "UPDATE COMPLAINT SET status_id = ?";
            $params = [$new_status];
        }

        // Append image if one was uploaded
        if ($image_path) {
            $sql .= ", proof_image = ?";
            $params[] = $image_path;
        }

        $sql .= " WHERE complaint_id = ?";
        $params[] = $complaint_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        header("Location: " . $back_url . "?msg=updated");
        exit();
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM COMPLAINT WHERE complaint_id = ?");
    $stmt->execute([$complaint_id]);
    $task = $stmt->fetch();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Task | SmartCity GIS</title>
    <style>
        :root { --primary: #3b82f6; --dark: #0f172a; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .update-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; }
        .info-box { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--primary); font-size: 0.9rem; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); }
        select, textarea, input[type="file"] { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; font-family: inherit; }
        
        .upload-section { display: none; background: #f0f7ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed var(--primary); }
        
        #preview-container { text-align: center; margin-bottom: 15px; display: none; }
        #image-preview { max-width: 100%; height: 150px; border-radius: 8px; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .btn-group { display: flex; gap: 10px; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; flex: 2; font-weight: 600; }
        .btn-cancel { background: #94a3b8; color: white; text-decoration: none; padding: 14px; border-radius: 8px; flex: 1; text-align: center; }
    </style>
</head>
<body>

<div class="update-card">
    <h2 style="margin-top: 0; color: var(--dark);">Update Task</h2>
    
    <div class="info-box">
        <strong>Task:</strong> <?php echo htmlspecialchars($task['description']); ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label>Progress Status:</label>
        <select name="status_id" id="statusSelect" required onchange="toggleSections()">
            <option value="1" <?php if($task['status_id'] == 1) echo 'selected'; ?>>Pending</option>
            <option value="2" <?php if($task['status_id'] == 2) echo 'selected'; ?>>In Progress</option>
            <option value="3" <?php if($task['status_id'] == 3) echo 'selected'; ?>>Completed</option>
        </select>

        <div id="uploadSection" class="upload-section">
            <label>📸 Upload Evidence (Required):</label>
            <input type="file" name="proof_image" id="proof_image" accept="image/*" onchange="previewImage(event)">
            
            <div id="preview-container">
                <p style="font-size: 0.75rem; color: #64748b; margin-top: 0;">Image Preview:</p>
                <img id="image-preview" src="#">
            </div>

            <div id="remarksSection">
                <label>Completion Remarks:</label>
                <textarea name="completion_remarks" placeholder="Explain the resolution..."><?php echo htmlspecialchars($task['completion_remarks'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="btn-group">
            <a href="<?php echo $back_url; ?>" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-save">Update Task</button>
        </div>
    </form>
</div>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('image-preview');
            const container = document.getElementById('preview-container');
            output.src = reader.result;
            container.style.display = "block";
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function toggleSections() {
        const status = document.getElementById('statusSelect').value;
        const uploadSection = document.getElementById('uploadSection');
        const remarksSection = document.getElementById('remarksSection');

        // Show upload for In Progress or Completed
        uploadSection.style.display = (status === '2' || status === '3') ? 'block' : 'none';
        
        // Only show remarks for Completed
        remarksSection.style.display = (status === '3') ? 'block' : 'none';
        
        // Make file required if completing (optional but recommended)
        document.getElementById('proof_image').required = (status === '3');
    }
    toggleSections();
</script>

</body>
</html>