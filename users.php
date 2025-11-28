<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";

$method = $_SERVER['REQUEST_METHOD'];

// Manejar preflight request (OPTIONS) si es necesario, similar a recipe.php
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method === 'GET') {
    // Caso 1: Consultar usuario por ID específico
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Preparamos la consulta para evitar inyecciones SQL
        $stmt = $conn->prepare("SELECT * FROM gastroLogin WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            if ($data) {
                // Devolvemos los datos del usuario
                // Nota: Por seguridad, en un entorno real deberías excluir la contraseña
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "User not found"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database query failed"]);
        }
        
        $stmt->close();
    } 
    // Caso 2: Consultar tabla completa (todos los usuarios)
    else {
        $result = $conn->query("SELECT * FROM gastroLogin");
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database query failed"]);
        }
    }
} else {
    // Si intentan usar POST, PUT, DELETE, etc. en este archivo
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
}

$conn->close();
?>