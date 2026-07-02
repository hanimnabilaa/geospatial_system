<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - SmartCity GIS</title>
    <style>
        :root {
            --dark-blue: #1e3a8a;
            --blue: #3b82f6;
            --grey: #6b7280;
            --white: #ffffff;
            --light-bg: #f3f4f6;
            --red: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--light-bg); color: #333; padding-bottom: 50px; }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        
        .profile-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .profile-header {
            background: var(--dark-blue);
            color: white;
            padding: 40px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            border: 4px solid rgba(255,255,255,0.2);
        }

        .profile-nav {
            display: flex;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-item {
            padding: 15px 25px;
            cursor: pointer;
            font-weight: 600;
            color: var(--grey);
            border-bottom: 3px solid transparent;
        }

        .nav-item.active {
            color: var(--blue);
            border-bottom-color: var(--blue);
        }

        .profile-body { padding: 30px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: var(--dark-blue); }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #f8fafc;
        }

        .form-group input[readonly] { color: var(--grey); cursor: not-allowed; }

        .btn-save {
            background-color: var(--blue);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-save:hover { background-color: var(--dark-blue); }

        /* Privacy Section Styling */
        .privacy-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .privacy-box h4 { color: #166534; margin-bottom: 10px; }
        .privacy-box p { font-size: 0.85rem; color: #166534; line-height: 1.4; }

        .danger-zone {
            border: 1px solid #fee2e2;
            padding: 20px;
            border-radius: 8px;
            background: #fff5f5;
        }

        .btn-delete { color: var(--red); background: none; border: 1px solid var(--red); padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .btn-delete:hover { background: var(--red); color: white; }
    </style>
</head>
<body>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">N</div>
                <div>
                    <h2>Nurhanim Nabila</h2>
                    <p style="opacity: 0.8;">Citizen Account • Joined Jan 2026</p>
                </div>
            </div>

            <div class="profile-nav">
                <div class="nav-item active">Personal Info</div>
                <div class="nav-item">Security</div>
                <div class="nav-item">Privacy Settings</div>
            </div>

            <div class="profile-body">
                <form action="update_profile.php" method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="Nurhanim Nabila Binti Ab Razak">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="hanim@example.com" readonly>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="012-3456789">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Assigned Role</label>
                        <input type="text" value="Citizen" readonly>
                    </div>

                    <button type="submit" class="btn-save">Update Profile</button>
                </form>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-body">
                <div class="privacy-box">
                    <h4>🔒 PDPA Compliance & Data Privacy</h4>
                    <p>Your geospatial data is used exclusively for infrastructure management. In accordance with our <strong>Data Masking Module</strong>, your specific residential address is never shown to technicians; they only see the location coordinates of the reported infrastructure issue.</p>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-body">
                <h4 style="color: var(--red); margin-bottom: 15px;">Danger Zone</h4>
                <div class="danger-zone">
                    <p style="font-size: 0.85rem; margin-bottom: 15px;">Once you delete your account, all your reported history and personal data will be purged from our active records within 30 days.</p>
                    <button class="btn-delete">Deactivate Account</button>
                </div>
            </div>
        </div>

        <p style="text-align: center;"><a href="index.php" style="color: var(--grey); text-decoration: none; font-size: 0.9rem;">← Back to Home</a></p>
    </div>

</body>
</html>