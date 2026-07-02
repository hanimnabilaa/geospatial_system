<?php
session_start();
require_once 'db.php';

// --- REGISTRATION LOGIC ---
if (isset($_POST['name']) && isset($_POST['confirm_password'])) {
    $name     = $_POST['name'];
    $ic        = $_POST['ic'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $pass      = $_POST['password'];
    $confPass = $_POST['confirm_password'];
    $role      = $_POST['role'];

    if ($pass !== $confPass) {
        die("Passwords do not match. <a href='register.php'>Go back</a>");
    }

    try {
        $sql = "INSERT INTO user 
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

        echo "<script>alert('Account created! Please login.'); window.location='login.php';</script>";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Email already registered. <a href='register.php'>Try another</a>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}

// --- LOGIN LOGIC (Fixed for Citizens Only) ---
else if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = $_POST['email'];
    $pass  = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM USER WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $pass === $user['password']) {

            // --- NEW: ROLE VALIDATION ---
            // This ensures only users with the 'Citizen' role can enter this portal
            if (trim($user['role']) !== 'Citizen') {
                echo "<script>alert('Access Denied: This portal is for Citizens only.'); window.location='login.php';</script>";
                exit();
            }

            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role']      = $user['role'];

            header("Location: index.php");
            exit();

        } else {
            echo "<script>alert('Invalid email or password'); window.location='login.php';</script>";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
