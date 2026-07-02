<?php
session_start();
require_once 'db.php';

// --- REGISTRATION LOGIC --- (Kept as is)
if (isset($_POST['name']) && isset($_POST['confirm_password'])) {
    $name     = $_POST['name'];
    $ic       = $_POST['ic'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $pass     = $_POST['password'];
    $confPass = $_POST['confirm_password'];
    $role     = $_POST['role'];

    if ($pass !== $confPass) {
        die("Passwords do not match. <a href='register_technician.php'>Go back</a>");
    }

    try {
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

        echo "<script>alert('Account created! Please login.'); window.location='login_technician.php';</script>";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Email already registered. <a href='register_technician.php'>Try another</a>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}

// --- MODIFIED LOGIN LOGIC ---
else if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = $_POST['email'];
    $pass  = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM USER WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $pass === $user['password']) {

            // --- ROLE VALIDATION START ---
            // If the user is trying to log in via the technician portal
            // but their role in the DB is NOT technician, deny access.
            if ($user['role'] !== 'Technician') {
                echo "<script>alert('Access Denied: This portal is for Technicians only.'); window.location='login_technician.php';</script>";
                exit();
            }
            // --- ROLE VALIDATION END ---

            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role']      = $user['role'];

            // Redirect to technician-specific dashboard
            header("Location: dashboard_tech.php");
            exit();

        } else {
            echo "<script>alert('Invalid email or password'); window.location='login_technician.php';</script>";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>