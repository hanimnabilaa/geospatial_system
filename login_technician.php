<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Technician Portal</title>
    <style>
        /* ==========================================================================
           1. CORE BASE STYLES & TECHNICIAN DISPATCH THEME VARIABLES
           ========================================================================== */
        :root {
            --tech-orange: #f97316;     /* Construction/Field Alert Orange */
            --tech-dark: #7c2d12;       /* Deep Amber/Burnt Orange Shadow */
            --grey: #6b7280;
            --white: #ffffff;
            
            /* Technician Specific Industrial Gradient Colors */
            --tech-grad-start: #0f1115;  /* Deep Carbon Charcoal */
            --tech-grad-end: #29170e;    /* Industrial Burnt Umber */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            /* Dual-Tone Charcoal-to-Amber Gradient Layered with Engineering Micro-Dots */
            background-color: var(--tech-grad-start);
            background-image: 
                linear-gradient(135deg, rgba(15, 17, 21, 0.97) 0%, rgba(41, 23, 14, 0.92) 100%),
                radial-gradient(rgba(249, 115, 22, 0.05) 1px, transparent 1px);
            background-size: 100% 100%, 24px 24px;
            
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
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.45); /* Deep shadow to separate from background */
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--tech-orange); /* Industrial Orange Safety Accent */
        }

        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h2 { color: var(--tech-dark); font-size: 1.6rem; font-weight: 700; }
        .auth-header p { color: var(--grey); font-size: 0.9rem; margin-top: 5px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 0.9rem; }
        .form-group input { 
            width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; 
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { 
            outline: none; 
            border-color: var(--tech-orange); 
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15); /* Soft amber glowing ring */
        }

        .btn-submit {
            width: 100%; background-color: var(--tech-orange); color: var(--white); padding: 12px; 
            border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; 
            transition: background 0.2s, transform 0.1s;
        }
        .btn-submit:hover { background-color: var(--tech-dark); }
        .btn-submit:active { transform: scale(0.99); }

        .auth-footer { text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--grey); }
        .auth-footer a { color: var(--tech-orange); text-decoration: none; font-weight: bold; }
        .auth-footer a:hover { text-decoration: underline; }
        
        .back-home { display: block; text-align: center; margin-top: 15px; font-size: 0.85rem; color: var(--grey); text-decoration: none; }
        .back-home:hover { color: var(--tech-dark); }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Welcome Back Technician!</h2>
            <p>Login to your SmartCity GIS Technician account</p>
        </div>

        <form action="auth_tech.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-submit">Login to Dispatch</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register_technician.php">Register here</a>
        </div>
        <a href="dashboard_tech.php" class="back-home">← Back to Dashboard</a>
    </div>

</body>
</html>