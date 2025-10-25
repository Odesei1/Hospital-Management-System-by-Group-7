<?php
include_once '../config/database.php';
include_once '../class/user.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Example data from form
$user->USER_EMAIL = $_POST['email'];
$user->USER_PASSWORD = $_POST['password'];
$user->USER_IS_SUPERADMIN = 0;

// Determine role
$role = $_POST['role']; // "doctor" or "staff"
if ($role == "doctor") {
    $user->DOC_ID = $_POST['DOC_ID'];
    $user->STAFF_ID = null;
    $user->PAT_ID = null;
} elseif ($role == "staff") {
    $user->STAFF_ID = $_POST['STAFF_ID'];
    $user->DOC_ID = null;
    $user->PAT_ID = null;
}

$result = $user->register();

if ($result == "success") {
    echo json_encode(["message" => "Registration successful!"]);
} elseif ($result == "exists") {
    echo json_encode(["message" => "Email already registered."]);
} else {
    echo json_encode(["message" => "Error creating account."]);
}
?>
