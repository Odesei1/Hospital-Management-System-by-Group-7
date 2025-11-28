<?php
session_start();
require_once "../Config/database.php";

$database = new Database();
$conn = $database->connect();

$errorMsg = '';
$successMsg = '';
$step = 1; // Step 1 = patient registration, Step 2 = account creation

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // -----------------------------
        // STEP 1: Patient Registration
        // -----------------------------
        $fname   = trim($_POST['fname'] ?? '');
        $mname   = trim($_POST['mname'] ?? '');
        $lname   = trim($_POST['lname'] ?? '');
        $dob     = trim($_POST['dob'] ?? '');
        $gender  = trim($_POST['gender'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!$fname || !$lname || !$email) {
            $errorMsg = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Invalid email address.";
        } else {
            try {
                // Check if email exists
                $check = $conn->prepare("SELECT pat_id FROM patient WHERE pat_email = ?");
                $check->execute([$email]);
                if ($check->fetch()) {
                    $errorMsg = "This email is already registered.";
                } else {
                    // Insert patient
                    $insertPatient = $conn->prepare("
                        INSERT INTO patient 
                        (pat_first_name, pat_middle_init, pat_last_name, pat_dob, pat_gender, pat_contact_num, pat_email, pat_address)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertPatient->execute([$fname, $mname, $lname, $dob, $gender, $contact, $email, $address]);
                    $pat_id = $conn->lastInsertId();

                    // Move to Step 2 (account creation)
                    $_SESSION['pat_id'] = $pat_id;
                    $_SESSION['pat_name'] = $fname . ' ' . $lname;
                    $step = 2;
                }
            } catch (Exception $e) {
                $errorMsg = "Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 2) {
        // -----------------------------
        // STEP 2: Account Creation
        // -----------------------------
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';
        $pat_id   = $_SESSION['pat_id'] ?? 0;

        if (!$username || !$password || !$confirm) {
            $errorMsg = "Please fill in all required fields.";
            $step = 2;
        } elseif ($password !== $confirm) {
            $errorMsg = "Passwords do not match.";
            $step = 2;
        } else {
            try {
                // Check if username exists
                $check = $conn->prepare("SELECT user_id FROM user WHERE user_name = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $errorMsg = "This username is already taken.";
                    $step = 2;
                } else {
                    // Insert user account (you should hash passwords in production!)
                    $insertUser = $conn->prepare("
                        INSERT INTO user (user_name, user_password, pat_id, user_is_superadmin)
                        VALUES (?, ?, ?, 0)
                    ");
                    $insertUser->execute([$username, $password, $pat_id]);

                    $successMsg = "🎉 Account created successfully! You can now log in.";
                    unset($_SESSION['pat_id'], $_SESSION['pat_name']);
                }
            } catch (Exception $e) {
                $errorMsg = "Error: " . $e->getMessage();
                $step = 2;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Registration - BioBridge</title>
<link rel="icon" type="image/png" href="../Assets/BioBridge_Medical_Center_Logo.png">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-sky-50 to-white flex items-center justify-center min-h-screen">
<div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl border-t-4 border-sky-600">
    <h1 class="text-2xl font-bold text-center text-sky-700 mb-4">🩺 Patient Registration</h1>

    <?php if ($errorMsg): ?>
      <p class="text-red-500 text-sm text-center mb-4 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    <?php elseif ($successMsg): ?>
      <p class="text-green-600 text-sm text-center mb-4 font-medium"><?= htmlspecialchars($successMsg) ?></p>
    <?php endif; ?>

    <?php if ($step == 1): ?>
    <!-- Step 1: Patient Registration -->
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="step" value="1">
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
            <input type="text" name="fname" required class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Middle Initial</label>
            <input type="text" name="mname" class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
            <input type="text" name="lname" required class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Date of Birth</label>
            <input type="date" name="dob" class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Gender</label>
            <select name="gender" class="w-full border p-2 rounded">
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
            </select></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Contact Number</label>
            <input type="text" name="contact" class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" required class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
            <input type="text" name="address" class="w-full border p-2 rounded"></div>
        <div class="col-span-2 mt-4">
            <button type="submit" class="w-full bg-sky-700 hover:bg-sky-800 text-white py-2 rounded-lg font-medium transition">
                Next: Create Account
            </button>
        </div>
    </form>

    <?php elseif ($step == 2): ?>
    <!-- Step 2: Account Creation -->
    <h2 class="text-lg font-semibold text-sky-700 mb-4 text-center">
        Create Account for <?= htmlspecialchars($_SESSION['pat_name'] ?? '') ?>
    </h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="step" value="2">
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
            <input type="text" name="username" required class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full border p-2 rounded"></div>
        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="confirm" required class="w-full border p-2 rounded"></div>
        <div class="col-span-2 mt-4">
            <button type="submit" class="w-full bg-sky-700 hover:bg-sky-800 text-white py-2 rounded-lg font-medium transition">
                Create Account
            </button>
        </div>
    </form>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="../index.php" class="text-sky-600 hover:underline text-sm">← Back to Home Page</a>
    </div>
</div>
</body>
</html>
