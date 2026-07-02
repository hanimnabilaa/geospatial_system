<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Portal</title>
    <style>
        /* ==========================================================================
           1. CORE BASE STYLES & ADMIN CONSOLE THEME VARIABLES
           ========================================================================== */
        :root {
            --admin-cyan: #06b6d4;      /* High-tech Cyber Cyan */
            --text-dark: #111827;       /* Midnight Grey for Titles */
            --grey: #6b7280;
            --white: #ffffff;
            --error-red: #ef4444;       /* Alert Red for errors */
            
            /* Admin Specific Deep Gradient Colors */
            --admin-grad-start: #020617; /* Near Obsidian Black */
            --admin-grad-end: #0f172a;   /* Deep Tactical Slate/Teal */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            /* Enhanced Dual-Tone Gradient Backdrop layered with High-Tech Radar Grid Matrix */
            background-color: var(--admin-grad-start);
            background-image: 
                linear-gradient(135deg, rgba(2, 6, 23, 0.98) 0%, rgba(15, 23, 42, 0.93) 100%),
                linear-gradient(rgba(6, 182, 212, 0.04) 2px, transparent 2px),
                linear-gradient(90deg, rgba(6, 182, 212, 0.04) 2px, transparent 2px);
            background-size: 100% 100%, 60px 60px, 60px 60px;
            
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }

        /* ==========================================================================
           2. UI CONTAINER & INPUT COMPONENTS
           ========================================================================== */
        .auth-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5); /* Deepened shadow to separate from dark gradient */
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--admin-cyan); /* Cyber Cyan Accent Border */
        }

        .auth-header { text-align: center; margin-bottom: 25px; }
        .auth-header h2 { color: var(--text-dark); font-size: 1.6rem; font-weight: 700; }
        .auth-header p { color: var(--grey); font-size: 0.9rem; margin-top: 5px; }

        /* Error Message Box Styling */
        .error-msg {
            background-color: #fef2f2;
            color: var(--error-red);
            border: 1px solid #fca5a5;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 0.9rem; }
        .form-group input { 
            width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; 
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { 
            outline: none; 
            border-color: var(--admin-cyan); 
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15); /* Soft glowing cyan focus ring */
        }

        .btn-submit {
            width: 100%; background-color: var(--admin-cyan); color: var(--white); padding: 12px; 
            border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; 
            transition: background 0.2s, transform 0.1s;
        }
        .btn-submit:hover { background-color: #0891b2; /* Darker cyan hover */ }
        .btn-submit:active { transform: scale(0.99); }

        .back-home { display: block; text-align: center; margin-top: 20px; font-size: 0.85rem; color: var(--grey); text-decoration: none; }
        .back-home:hover { color: var(--admin-cyan); }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Welcome Back Admin!</h2>
            <p>Let's Work Together!</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                🔒 Invalid credentials or access denied.
            </div>
        <?php endif; ?>

        <form action="auth_admin.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter admin email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter security password">
            </div>

            <button type="submit" class="btn-submit">Login to Console</button>
        </form>

        <a href="index.php" class="back-home">← Return to Main Portal</a>
    </div>

</body>
</html>