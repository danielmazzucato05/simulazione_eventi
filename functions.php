<?php
function setCorsHeaders() {
    header("Access-Control-Allow-Origin: *"); // For production, restrict to your frontend domain
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

function sendJsonResponse($status, $success, $message, $data = null) {
    http_response_code($status);
    $response = [
        "success" => $success,
        "message" => $message
    ];
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
    exit();
}

function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true);
}

// Function to extract token from Authorization header
function getBearerToken() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    // Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Function to authenticate token and return user data
function authenticateWithToken($conn) {
    $token = getBearerToken();
    if (!$token) {
        sendJsonResponse(401, false, "Access denied. Token missing.");
    }

    $query = "SELECT utente_id, nome, cognome, ruolo FROM utenti WHERE auth_token = :token LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":token", $token);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        sendJsonResponse(401, false, "Access denied. Invalid token.");
    }
}

function requireOrganizer($conn) {
    $user = authenticateWithToken($conn);
    if ($user['ruolo'] !== 'Organizzatore') {
        sendJsonResponse(403, false, "Access denied. Organizer role required.");
    }
    return $user;
}

function requireEmployee($conn) {
    // Both employees and organizers can do employee things generally, but if strict:
    $user = authenticateWithToken($conn);
    return $user;
}
?>
