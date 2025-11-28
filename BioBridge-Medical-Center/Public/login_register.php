<?php
session_start();
require_once  "../Config/database.php";
require_once  "../Class/user.php";

$database = new Database();
$conn = $database->connect();

/* ==============================================================  
   🧩 REGISTER (Plain Password)
   ============================================================== */
if (isset($_POST['register'])) {
    $name = trim($_POST['username']);
    $password = trim($_POST['password']); // ✅ plain password

    // Check if username/email already exists
    $stmt = $conn->prepare("SELECT user_name FROM user WHERE user_name = ?");
    $stmt->execute([$name]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['register_error'] = 'Username is already taken.';
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }

    // Determine which table this email belongs to
    $role = null;
    $roleId = null;

    // Check Doctor
    $check = $conn->prepare("SELECT doc_id FROM doctor WHERE doc_email = ?");
    $check->execute([$name]);
    if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
        $role = 'doctor';
        $roleId = $row['doc_id'];
    }

    // Check Staff
    if (!$role) {
        $check = $conn->prepare("SELECT staff_id FROM staff WHERE staff_email = ?");
        $check->execute([$name]);
        if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
            $role = 'staff';
            $roleId = $row['staff_id'];
        }
    }

    // Check Patient
    if (!$role) {
        $check = $conn->prepare("SELECT pat_id FROM patient WHERE pat_email = ?");
        $check->execute([$name]);
        if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
            $role = 'patient';
            $roleId = $row['pat_id'];
        }
    }

    // Insert new user (plain password)
    if ($role === 'doctor') {
        $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, doc_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$name, $password, $roleId]);
    } elseif ($role === 'staff') {
        $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, staff_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$name, $password, $roleId]);
    } elseif ($role === 'patient') {
        $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, pat_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$name, $password, $roleId]);
    } else {
        $_SESSION['register_error'] = 'Account not found in records.';
        $_SESSION['active_form'] = 'register';
        header("Location: ../BioBridge-Medical-Center-HMS-main/BioBridge-Medical-Center/index.php");
        exit();
    }

    $_SESSION['register_success'] = ucfirst($role) . ' account successfully registered!';
    header("Location: ../BioBridge-Medical-Center-HMS-main/BioBridge-Medical-Center/index.php");
    exit();
}

/* ==============================================================  
   🔐 LOGIN (Plain Password)
   ============================================================== */
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM user WHERE user_name = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ Plain password check
    if ($user && $password === $user['user_password']) {

        // Detect role
        if ($user['user_is_superadmin'] == 1) {
            $role = 'superadmin';
        } elseif (!is_null($user['doc_id'])) {
            $role = 'doctor';
        } elseif (!is_null($user['staff_id'])) {
            $role = 'staff';
        } elseif (!is_null($user['pat_id'])) {
            $role = 'patient';
        } else {
            $role = 'unknown';
        }

        // ✅ Session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['user_name']; 
        $_SESSION['role'] = $role;

        if ($role === 'patient') $_SESSION['pat_id'] = $user['pat_id'];
        if ($role === 'doctor') $_SESSION['doc_id'] = $user['doc_id'];
        if ($role === 'staff') $_SESSION['staff_id'] = $user['staff_id'];

        // Update login time
        $update = $conn->prepare("UPDATE user SET user_last_login = NOW() WHERE user_id = ?");
        $update->execute([$user['user_id']]);

        // Redirect by role
        switch ($role) {
            case 'superadmin':
                header("Location: /HMS_project1/public/dashboard.php");
                break;
            case 'doctor':
                header("Location: doctor_appointments_management.php");
                break;
            case 'staff':
                header("Location: staff_dashboard.php");
                break;
            case 'patient':
                header("Location: patient_appointments.php");
                break;
            default:
                header("Location: access_denied.php");
        }
        exit();
    } else {
        $_SESSION['login_error'] = "Incorrect username or password.";
        header("Location: ../index.php");
        exit();
    }
}
?>
