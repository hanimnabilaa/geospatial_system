<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Geospatial-Aware Infrastructure Management System</title>
    <style>
        /* ==========================================================================
           1. CORE BASE STYLES & DEFAULT ROOT VARIABLES
           ========================================================================== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            transition: background 0.4s ease; /* Smooth backdrop switching animation */
        }

        /* ==========================================================================
           2. THEME DEFINITIONS (Changes colors & geospatial pattern types)
           ========================================================================== */

        /* 👤 CITIZEN THEME: Civic Blue + Standard Mapping Grid */
        body.theme-citizen {
            --primary: #3b82f6;        /* Vibrant Blue */
            --primary-dark: #1e3a8a;   /* Deep Blue */
            --grey: #6b7280;
            --white: #ffffff;
            
            background-color: #0f172a;
            background-image: 
                linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 58, 138, 0.9) 100%),
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 100% 100%, 30px 30px, 30px 30px;
        }

        /* 🔧 TECHNICIAN THEME: Industrial Emerald + Engineering Micro-Dots */
        body.theme-technician {
            --primary: #10b981;        /* Construction Emerald */
            --primary-dark: #064e3b;   /* Dark Forest Green */
            --grey: #6b7280;
            --white: #ffffff;
            
            background-color: #0b1310;
            background-image: 
                linear-gradient(135deg, rgba(11, 19, 16, 0.96) 0%, rgba(6, 78, 59, 0.9) 100%),
                radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 100% 100%, 24px 24px;
        }

        /* 🛡️ ADMIN THEME: Command Center Cyan + High-Tech Wide Radar Blocks */
        body.theme-admin {
            --primary: #06b6d4;        /* Cyber Cyan */
            --primary-dark: #111827;   /* Charcoal Command Dark */
            --grey: #6b7280;
            --white: #ffffff;
            
            background-color: #030712;
            background-image: 
                linear-gradient(135deg, rgba(3, 7, 18, 0.98) 0%, rgba(17, 28, 36, 0.95) 100%),
                linear-gradient(rgba(6, 182, 212, 0.03) 2px, transparent 2px),
                linear-gradient(90deg, rgba(6, 182, 212, 0.03) 2px, transparent 2px);
            background-size: 100% 100%, 60px 60px, 60px 60px;
        }

        /* ==========================================================================
           3. APPLICATION INTERFACE UI COMPONENTS
           ========================================================================== */
        .auth-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--primary); /* Updates dynamically based on chosen role */
        }

        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h2 { color: var(--primary-dark); font-size: 1.6rem; }
        .auth-header p { color: var(--grey); font-size: 0.9rem; margin-top: 5px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 0.9rem; }
        .form-group input { 
            width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; 
            transition: border-color 0.2s;
        }
        .form-group input:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .btn-submit {
            width: 100%; background-color: var(--primary); color: var(--white); padding: 12px; 
            border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; 
            transition: background 0.2s, transform 0.1s;
        }
        .btn-submit:hover { background-color: var(--primary-dark); }
        .btn-submit:active { transform: scale(0.99); }

        .auth-footer { text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--grey); }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: bold; }
        .auth-footer a:hover { text-decoration: underline; }
        
        .back-home { display: block; text-align: center; margin-top: 15px; font-size: 0.85rem; color: var(--grey); text-decoration: none; }
        .back-home:hover { color: var(--primary-dark); }
    </style>
</head>

<body class="theme-citizen">

    <div class="auth-container">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Login to your SmartCity GIS account</p>
        </div>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-submit">Login</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        <a href="index.php" class="back-home">← Back to Home</a>
    </div>

</body>
</html>