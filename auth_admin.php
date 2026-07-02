<?php
session_start();
require_once 'db.php';

// --- REGISTRATION LOGIC ---
if (isset($_POST['name']) && isset($_POST['confirm_password'])) {
    $name     = $_POST['name'];
    $ic       = $_POST['ic'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $pass     = $_POST['password'];
    $confPass = $_POST['confirm_password'];
    $role     = $_POST['role'];

    if ($pass !== $confPass) {
        die("Passwords do not match. <a href='register_admin.php'>Go back</a>");
    }

    try {
        // NOTE: In a real app, use password_hash($pass, PASSWORD_DEFAULT) here
        $sql = "INSERT INTO USER 
                (user_name, user_ic, user_email, user_phonenum, password, role) 
                VALUES 
                (:name, :ic, :email, :phone, :pass, :role)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'  => $name,
            ':ic'    => $ic,
            ':email' => $email,
            ':phone' => $phone,
            ':pass'  => $pass, 
            ':role'  => $role
        ]);

        echo "<script>alert('Account created! Please login.'); window.location='login_admin.php';</script>";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Error: Email or IC already registered.";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}

// --- LOGIN LOGIC ---
else if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $pass  = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM USER WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists and password matches
        if ($user && $pass === $user['password']) {

            // --- ROLE VALIDATION ---
            // Ensure only 'Admin' roles can pass through this specific login handler
            if (strtolower(trim($user['role'])) !== 'admin') {
                echo "<script>alert('Access Denied: You do not have Admin privileges.'); window.location='login.php';</script>";
                exit();
            }

            // Set Session Variables
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role']      = $user['role'];

            // Success Redirect
            header("Location: admin_dashboard.php");
            exit();

        } else {
            echo "<script>alert('Invalid email or password'); window.location='login_admin.php';</script>";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>