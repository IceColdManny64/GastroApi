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

$stmt = $conn->prepare("SELECT * FROM gastroLogin WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "login" => "success",
        "id"    => intval($row['id']),
        "email" => $row['email']
    ]);
} else {
    http_response_code(401);
    echo json_encode(["login" => "failed", "message" => "Invalid credentials"]);
}
?>