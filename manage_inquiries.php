<?php
session_start();
require_once 'db.php';

// 1. Security Check: Ensure only Admins can access
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// 2. Audit Logging
if (function_exists('recordAudit')) {
    recordAudit($pdo, 'VIEW_INQUIRIES_MANAGEMENT', null, null, 'Admin opened the User Inquiries management console');
}

// 3. Handle Filtering Conditions
$whereClauses = [];
$params = [];

if (!empty($_GET['type_filter'])) {
    $whereClauses[] = "inquiry_type = :type_filter";
    $params[':type_filter'] = $_GET['type_filter'];
}

if (!empty($_GET['rating_filter'])) {
    $whereClauses[] = "rating = :rating_filter";
    $params[':rating_filter'] = intval($_GET['rating_filter']);
}

$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {
    // Fetch unique inquiry categories for our filter dropdown element
    $categories = $pdo->query("SELECT DISTINCT inquiry_type FROM contact_inquiries WHERE inquiry_type IS NOT NULL AND inquiry_type != ''")->fetchAll(PDO::FETCH_COLUMN);

    // Fetch the list of user inquiries based on parameters
    $sql = "SELECT contact_id, full_name, email, inquiry_type, message, rating, ip_address 
            FROM contact_inquiries 
            $whereSql 
            ORDER BY contact_id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Inquiries - SmartCity GIS</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        /* Contextual layouts custom built for specific operational parameters */
        .filter-bar {
            background-color: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
        }
        .filter-control {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #f8fafc;
            min-width: 180px;
            font-size: 0.9rem;
            color: #000000;
        }
        
        /* Orange styling specifically applied to Category Type filter control */
        select#type_filter {
            color: #c2410c;
            border-color: #fdba74;
            background-color: #fff7ed;
            font-weight: 500;
        }
        select#type_filter option {
            color: #c2410c;
            background-color: #ffffff;
        }
        
        .filter-control option {
            color: #000000;
            background-color: #ffffff;
        }
        
        .btn-filter, .btn-reset {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            font-weight: 500;
            height: 38px;
        }
        .btn-filter { background-color: #2563eb; color: #ffffff; }
        .btn-filter:hover { background-color: #1d4ed8; }
        .btn-reset { background-color: #e2e8f0; color: #334155; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-reset:hover { background-color: #cbd5e1; }
        
        .text-muted { color: #64748b; font-size: 0.85rem; }
        .rating-stars { color: #f59e0b; font-weight: bold; letter-spacing: 1px; }
        
        /* Unified Category Tag Styles */
        .category-badge {
            background-color: #fff7ed; 
            color: #c2410c; 
            border: 1px solid #ffedd5;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .btn-view {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-view:hover { background-color: #e2e8f0; }

        /* Dialog overlay layer configuration */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }
        .modal-box {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 15px; right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }
        .modal-close:hover { color: #334155; }
        .modal-header-meta {
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }
        .modal-body-text {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #cbd5e1;
            white-space: pre-wrap;
            color: #334155;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_complaints.php">📋 Manage Complaints</a></li>
            <li><a href="manage_teams.php">👥 Manage Teams</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="map_admin.php">📍 GIS Map View</a></li>
            <li><a href="manage_inquiries.php" class="active">✉️ User Inquiries</a></li>
            <li><a href="audit_logs.php">📜 Audit Logs</a></li>
            <li><a href="reports.php">📊 System Reports</a></li>
            <li><a href="Priority_settings.php">⚙️ Priority Settings</a></li>
            <li><a href="logout_admin.php" style="margin-top: 50px; color: #fca5a5;">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>User Inquiries & Feedback</h1>
            <div class="user-info">Logged in as <strong>Admin</strong></div>
        </div>

        <form method="GET" action="manage_inquiries.php" class="filter-bar">
            <div class="filter-group">
                <label for="type_filter">Category Type</label>
                <select name="type_filter" id="type_filter" class="filter-control">
                    <option value="">-- All Types --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['type_filter']) && $_GET['type_filter'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="rating_filter">Rating Score</label>
                <select name="rating_filter" id="rating_filter" class="filter-control">
                    <option value="">-- All Ratings --</option>
                    <?php for($i=5; $i>=1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($_GET['rating_filter']) && $_GET['rating_filter'] == $i) ? 'selected' : ''; ?>>
                            <?php echo str_repeat('★', $i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn-filter">⚡ Filter</button>
            <a href="manage_inquiries.php" class="btn-reset">Reset</a>
        </form>

        <div class="content-box">
            <h4>Inquiries Database Log (<?php echo count($inquiries); ?> results found)</h4>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Metadata</th>
                        <th>Category Type</th>
                        <th>Message String Excerpt</th>
                        <th>Satisfaction</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($inquiries) > 0): foreach ($inquiries as $row): ?>
                    <tr>
                        <td>#<?php echo $row['contact_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-muted" style="text-decoration: underline;"><?php echo htmlspecialchars($row['email']); ?></a>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($row['inquiry_type'] ?: 'Uncategorized'); ?>
                            </span>
                        </td>
                        <td>
                            <div style="max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($row['message']); ?>
                            </div>
                            <span class="text-muted" style="font-size:0.75rem; font-family: monospace;">IP: <?php echo htmlspecialchars($row['ip_address'] ?? 'Unknown'); ?></span>
                        </td>
                        <td>
                            <span class="rating-stars">
                                <?php 
                                    $rating = intval($row['rating'] ?? 0); 
                                    echo str_repeat('★', $rating) . str_repeat('☆', max(0, 5 - $rating)); 
                                ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-view" onclick="openMessageModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                🔍 View Message
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center; padding: 30px;">No user inquiries matched your active search query parameters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="messageModal">
        <div class="modal-box">
            <span class="modal-close" onclick="closeMessageModal()">&times;</span>
            <h3 id="modalTitle" style="margin-top:0; color:#0f172a;">User Feedback Details</h3>
            
            <div class="modal-header-meta">
                <p style="margin: 5px 0;"><strong>From:</strong> <span id="modalName"></span> (<span id="modalEmail"></span>)</p>
                <p style="margin: 5px 0;"><strong>Category:</strong> <span id="modalCategory" class="category-badge"></span></p>
                <p style="margin: 5px 0;"><strong>System Rating:</strong> <span id="modalRating" class="rating-stars"></span></p>
            </div>

            <p style="font-weight:600; margin-bottom:8px; color:#475569;">Submitted Message Body:</p>
            <div class="modal-body-text" id="modalMessage"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('messageModal');

        function openMessageModal(data) {
            document.getElementById('modalName').textContent = data.full_name;
            document.getElementById('modalEmail').textContent = data.email;
            document.getElementById('modalCategory').textContent = data.inquiry_type || 'Uncategorized';
            
            // Build dynamic display stars elements within dialog matrix safely
            const currentRating = parseInt(data.rating) || 0;
            const goldStars = '★'.repeat(currentRating);
            const emptyStars = '☆'.repeat(Math.max(0, 5 - currentRating));
            document.getElementById('modalRating').textContent = goldStars + emptyStars;
            
            document.getElementById('modalMessage').textContent = data.message;
            
            modal.style.display = 'flex';
        }

        function closeMessageModal() {
            modal.style.display = 'none';
        }

        // Close when clicking outside box boundary zones
        window.onclick = function(event) {
            if (event.target === modal) {
                closeMessageModal();
            }
        }
    </script>
</body>
</html>