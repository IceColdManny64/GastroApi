<?php
$host = "fdb1027.runhosting.com";
$user = "4626429_apidb";
$password = "DBpalaAPI15";
$database = "4626429_apidb";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}
?>