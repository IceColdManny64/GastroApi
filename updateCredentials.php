<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";

// Asegurar que recibimos datos JSON
$json = file_get_contents('php://input');
$input = json_decode($json, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Solo se permiten solicitudes POST"
    ]);
    exit;
}

// Verificar acción solicitada
if (!isset($input['action'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Acción no especificada"
    ]);
    exit;
}

// Endpoint para actualizar credenciales
if ($input['action'] === 'update_credentials') {
    // Validar campos requeridos
    $requiredFields = ['id', 'new_email', 'new_password'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])){
            echo json_encode([
                "status" => "error",
                "message" => "Falta el campo requerido: $field"
            ]);
            exit;
        }
      }


    // Asignar valores
    $id = (int)$input['id'];
    $new_email = trim($input['new_email']);
    $new_password = trim($input['new_password']);

    // Validar formato de email
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "status" => "error",
            "message" => "Formato de email inválido"
        ]);
        exit;
    }

    try {
        // Actualizar en la base de datos
        $stmt = $conn->prepare("UPDATE gastroLogin SET email = ?, password = ? WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param("ssi", $new_email, $new_password, $id);
        $result = $stmt->execute();

        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "Credenciales actualizadas correctamente",
                "affected_rows" => $stmt->affected_rows
            ]);
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error en la base de datos",
            "error_details" => $e->getMessage()
        ]);
    }
    exit;
}

// Si la acción no es reconocida
echo json_encode([
    "status" => "error",
    "message" => "Acción no reconocida"
]);
?>