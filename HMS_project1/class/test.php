<?php
require_once "../class/service.php";
require_once "../config/db.php";

$database = new Database();
$db = $database->connect();

//Service
$service = new Service($db);


?>