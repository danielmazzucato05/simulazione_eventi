<?php
require_once 'Database.php';
require_once 'functions.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, false, "Method not allowed. Use POST.");
}

$data = getJsonInput();

$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    sendJsonResponse(400, false, "Email and password are required.");
}

try {
    $db = (new Database())->getConnection();
    if (!$db) {
        sendJsonResponse(500, false, "Database connection failed.");
    }

    $stmt = $db->prepare("SELECT utente_id, password_hash, nome, ruolo FROM utenti WHERE email = :email LIMIT 1");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password_hash'])) {
            // Generate a simple auth token
            // In PostgreSQL we could use gen_random_uuid(), but doing it in PHP is fine for our simplified token
            $token = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Save token to DB
            $updateStmt = $db->prepare("UPDATE utenti SET auth_token = :token WHERE utente_id = :id");
            $updateStmt->bindParam(":token", $token);
            $updateStmt->bindParam(":id", $user['utente_id']);
            $updateStmt->execute();

            sendJsonResponse(200, true, "Login successful.", [
                "token" => $token,
                "utente" => [
                    "id" => $user['utente_id'],
                    "nome" => $user['nome'],
                    "ruolo" => $user['ruolo']
                ]
            ]);
        } else {
            sendJsonResponse(401, false, "Invalid password.");
        }
    } else {
        sendJsonResponse(401, false, "User not found.");
    }
} catch(PDOException $e) {
    sendJsonResponse(500, false, "Database error: " . $e->getMessage());
}
?>
