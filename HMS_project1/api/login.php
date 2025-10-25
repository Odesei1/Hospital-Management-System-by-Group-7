<?php
include_once '../config/database.php';
include_once '../class/user.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$user->USER_EMAIL = $_POST['email'];
$user->USER_PASSWORD = $_POST['password'];

$result = $user->login();

if ($result == "doctor") {
    echo json_encode(["message" => "Welcome Doctor"]);
} elseif ($result == "staff") {
    echo json_encode(["message" => "Welcome Staff"]);
} else {
    echo json_encode(["message" => "Login failed: " . $result]);
}
?>
