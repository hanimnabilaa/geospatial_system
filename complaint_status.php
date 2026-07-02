<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - SmartCity GIS</title>
    <style>
        :root {
            --dark-blue: #1e3a8a;
            --blue: #3b82f6;
            --grey: #6b7280;
            --white: #ffffff;
            --light-bg: #f3f4f6;
            --red: #ef4444;
            --yellow: #f59e0b;
            --green: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--light-bg); color: #333; padding: 20px; }

        .container { max-width: 1000px; margin: 0 auto; }
        
        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        h1 { color: var(--dark-blue); font-size: 1.5rem; }

        .btn-new {
            background-color: var(--blue);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        /* Filter Tabs */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { 
            padding: 8px 16px; 
            background: #e2e8f0; 
            border-radius: 20px; 
            font-size: 0.85rem; 
            cursor: pointer; 
            color: var(--grey);
            font-weight: 600;
        }
        .tab.active { background: var(--dark-blue); color: white; }

        /* Complaint Cards */
        .complaint-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: 120px 1fr 150px;
            gap: 20px;
            align-items: center;
        }

        .complaint-img {
            width: 120px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            background-color: #eee;
        }

        .complaint-info h3 { font-size: 1.1rem; color: var(--dark-blue); margin-bottom: 5px; }
        .complaint-info p { font-size: 0.9rem; color: var(--grey); margin-bottom: 5px; }
        
        .meta-data { display: flex; gap: 15px; font-size: 0.8rem; font-weight: 600; }
        .priority-label { display: flex; align-items: center; gap: 5px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }

        .status-box { text-align: right; }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* Status Colors */
        .status-pending { background: #fee2e2; color: #b91c1c; }
        .status-progress { background: #fef3c7; color: #92400e; }
        .status-resolved { background: #dcfce7; color: #166534; }

        .btn-view {
            color: var(--blue);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .complaint-card { grid-template-columns: 1fr; text-align: center; }
            .complaint-img { margin: 0 auto; width: 100%; height: 150px; }
            .status-box { text-align: center; border-top: 1px solid #eee; pt: 15px; }
            .meta-data { justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1>My Reported Issues</h1>
            <a href="submit_complaint.php" class="btn-new">+ New Report</a>
        </header>

        <div class="tabs">
            <div class="tab active">All</div>
            <div class="tab">Pending</div>
            <div class="tab">In Progress</div>
            <div class="tab">Resolved</div>
        </div>

        <div class="complaint-card">
            <img src="https://via.placeholder.com/120x100?text=Road+Damage" alt="Issue Photo" class="complaint-img">
            <div class="complaint-info">
                <h3>Pothole on Main Road</h3>
                <p>Reported at: Jalan Hang Tuah, Melaka</p>
                <div class="meta-data">
                    <span>ID: #CMP-10293</span>
                    <span class="priority-label">
                        <span class="dot" style="background: var(--red);"></span> High Priority (Score: 4.8)
                    </span>
                </div>
            </div>
            <div class="status-box">
                <div class="status-badge status-progress">In Progress</div>
                <br>
                <a href="complaint_details.php?id=10293" class="btn-view">View Progress Detail →</a>
            </div>
        </div>

        <div class="complaint-card">
            <img src="https://via.placeholder.com/120x100?text=Water+Leak" alt="Issue Photo" class="complaint-img">
            <div class="complaint-info">
                <h3>Water Pipe Leakage</h3>
                <p>Reported at: Taman Tasik Utama</p>
                <div class="meta-data">
                    <span>ID: #CMP-10285</span>
                    <span class="priority-label">
                        <span class="dot" style="background: var(--yellow);"></span> Medium Priority (Score: 2.5)
                    </span>
                </div>
            </div>
            <div class="status-box">
                <div class="status-badge status-pending">Pending</div>
                <br>
                <a href="complaint_details.php?id=10285" class="btn-view">View Progress Detail →</a>
            </div>
        </div>

        <div class="complaint-card">
            <img src="https://via.placeholder.com/120x100?text=Streetlight" alt="Issue Photo" class="complaint-img">
            <div class="complaint-info">
                <h3>Broken Streetlight</h3>
                <p>Reported at: Durian Tunggal</p>
                <div class="meta-data">
                    <span>ID: #CMP-10250</span>
                    <span class="priority-label">
                        <span class="dot" style="background: var(--green);"></span> Low Priority (Score: 1.2)
                    </span>
                </div>
            </div>
            <div class="status-box">
                <div class="status-badge status-resolved">Resolved</div>
                <br>
                <a href="complaint_details.php?id=10250" class="btn-view">View Progress Detail →</a>
            </div>
        </div>

    </div>

</body>
</html>