<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";

// Manejo de entrada JSON
$json = file_get_contents('php://input');
$input = json_decode($json, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

// Verificar que el JSON se decodificó correctamente
if ($input === null || !is_array($input)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos JSON inválidos"]);
    exit;
}

// Verificar acción solicitada
if (!isset($input['action']) || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Parámetro 'action' no especificado"]);
    exit;
}

// Endpoint para actualizar credenciales
if ($input['action'] == 'update_credentials') {
    // Validar campos requeridos
    $requiredFields = ['id', 'new_email', 'new_password'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Campos requeridos faltantes: " . implode(', ', $missingFields)
        ]);
        exit;
    }

    // Asignar y sanitizar valores
    $id = (int)$input['id'];
    $new_email = trim($input['new_email']);
    $new_password = trim($input['new_password']);

    // Validar formato de email
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Formato de email inválido"]);
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
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error en la base de datos",
            "error_details" => $e->getMessage()
        ]);
    }
    exit;
}
// Endpoint para verificar email
if ($input['action'] == 'verify_email') {
  if (!isset($input['current_email'])) {
      http_response_code(400);
      echo json_encode(["status" => "error", "message" => "Campo 'current_email' requerido"]);
      exit;
  }

  $email = trim($input['current_email']);

  try {
      $stmt = $conn->prepare("SELECT id FROM gastroLogin WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
          $row = $result->fetch_assoc();
          echo json_encode([
              "status" => "success",
              "exists" => true,
              "id" => $row['id']
          ]);
      } else {
          echo json_encode([
              "status" => "success",
              "exists" => false,
              "message" => "Email no encontrado"
          ]);
      }
  } catch (Exception $e) {
      http_response_code(500);
      echo json_encode([
          "status" => "error",
          "message" => "Error en la base de datos",
          "error_details" => $e->getMessage()
      ]);
  }
  exit;
}

// Endpoint para verificar contraseña actual
if ($input['action'] == 'verify_password') {
  $requiredFields = ['id', 'current_password'];
  $missingFields = [];
  
  foreach ($requiredFields as $field) {
      if (!isset($input[$field])) {
          $missingFields[] = $field;
      }
  }
  
  if (!empty($missingFields)) {
      http_response_code(400);
      echo json_encode([
          "status" => "error",
          "message" => "Campos requeridos faltantes: " . implode(', ', $missingFields)
      ]);
      exit;
  }

  $id = (int)$input['id'];
  $current_password = $input['current_password'];

  try {
      $stmt = $conn->prepare("SELECT password FROM gastroLogin WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
          $row = $result->fetch_assoc();
          $valid = ($row['password'] === $current_password);
          
          echo json_encode([
              "status" => "success",
              "valid" => $valid,
              "message" => $valid ? "Contraseña válida" : "Contraseña incorrecta"
          ]);
      } else {
          echo json_encode([
              "status" => "error",
              "message" => "Usuario no encontrado"
          ]);
      }
  } catch (Exception $e) {
      http_response_code(500);
      echo json_encode([
          "status" => "error",
          "message" => "Error en la base de datos",
          "error_details" => $e->getMessage()
      ]);
  }
  exit;
}

// Si la acción no es reconocida
http_response_code(400);
echo json_encode(["status" => "error", "message" => "Acción no reconocida: " . $input['action']]);
?>