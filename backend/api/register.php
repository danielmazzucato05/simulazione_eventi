<?php
require_once '../Database.php';
require_once '../functions.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, false, "Method not allowed. Use POST.");
}

$data = getJsonInput();

$nome = strip_tags($data['nome'] ?? '');
$cognome = strip_tags($data['cognome'] ?? '');
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $data['password'] ?? '';
$ruolo = $data['ruolo'] ?? 'Dipendente';

if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
    sendJsonResponse(400, false, "All fields are required (nome, cognome, email, password).");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(400, false, "Invalid email format.");
}

if (!in_array($ruolo, ['Dipendente', 'Organizzatore'])) {
    sendJsonResponse(400, false, "Invalid role. Must be 'Dipendente' or 'Organizzatore'.");
}

try {
    $db = (new Database())->getConnection();
    if (!$db) {
        sendJsonResponse(500, false, "Database connection failed.");
    }

    // Check if email exists
    $stmt = $db->prepare("SELECT utente_id FROM utenti WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(400, false, "Email already registered.");
    }

    // Insert new user
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $query = "INSERT INTO utenti (nome, cognome, email, password_hash, ruolo) VALUES (:nome, :cognome, :email, :password_hash, :ruolo)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":cognome", $cognome);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password_hash", $hashedPassword);
    $stmt->bindParam(":ruolo", $ruolo);

    if ($stmt->execute()) {
        sendJsonResponse(201, true, "User registered successfully.");
    } else {
        sendJsonResponse(500, false, "Failed to register user.");
    }
} catch(PDOException $e) {
    sendJsonResponse(500, false, "Database error: " . $e->getMessage());
}
?>
