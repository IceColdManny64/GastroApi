<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";

$input = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

if (!isset($input['email'], $input['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$email = $input['email'];
$password = $input['password'];

// Validar si ya existe
$check = $conn->prepare("SELECT id FROM gastroLogin WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "email already exists"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO gastroLogin (email, password) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $password);

if ($stmt->execute()) {
    echo json_encode(["store" => "success"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database insert failed"]);
}
