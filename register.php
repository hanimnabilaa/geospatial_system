<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Geospatial-Aware Infrastructure Management System</title>
    <style>
        /* ==========================================================================
           1. CORE BASE STYLES & CITIZEN THEME VARIABLES
           ========================================================================== */
        :root {
            --primary: #3b82f6;        /* Vibrant Civic Blue */
            --primary-dark: #1e3a8a;   /* Deep Trust Blue */
            --grey: #6b7280;
            --white: #ffffff;
            
            /* Citizen Dynamic Gradient Backdrop Colors */
            --bg-gradient-start: #0f172a; /* Slate Dark Base */
            --bg-gradient-end: #1e3a8a;   /* Deep Blue Mix */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            /* Modern Structural Spatial Grid Pattern Backdrop */
            background-color: var(--bg-gradient-start);
            background-image: 
                linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 58, 138, 0.9) 100%),
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 100% 100%, 30px 30px, 30px 30px;
            
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 30px 20px; 
        }

        /* ==========================================================================
           2. UI CONTAINER & LAYOUT GRID COMPONENTS
           ========================================================================== */
        .auth-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35); /* Depth contrast elevation */
            width: 100%;
            max-width: 520px; /* Slightly wider for the double form row fields */
            border-top: 5px solid var(--primary); /* Vibrant Top Border Line */
        }

        .auth-header { text-align: center; margin-bottom: 25px; }
        .auth-header h2 { color: var(--primary-dark); font-size: 1.6rem; font-weight: 700; }
        .auth-header p { color: var(--grey); font-size: 0.9rem; margin-top: 5px; }

        /* Row grouping grid for structural split elements */
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #333; font-weight: 500; font-size: 0.9rem; }
        .form-group input { 
            width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; 
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); /* Clean focus highlight ring */
        }

        .btn-submit {
            width: 100%; background-color: var(--primary); color: var(--white); padding: 12px; 
            border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; 
            transition: background 0.2s, transform 0.1s; margin-top: 10px;
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
<body>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Create an Account</h2>
            <p>Register as a Citizen to report infrastructure issues</p>
        </div>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Razak">
            </div>
            
            <div class="form-group">
                <label for="ic">Identification Card No</label>
                <input type="text" id="ic" name="ic" required placeholder="e.g., 000123110869">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="e.g., razak@example.com">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required placeholder="e.g., 012-3456789">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Create password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
                </div>
            </div>

            <input type="hidden" name="role" value="Citizen">

            <button type="submit" class="btn-submit">Register Now</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        <a href="index.php" class="back-home">← Back to Home</a>
    </div>

</body>
</html>