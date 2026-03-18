<?php
require_once '../Database.php';
require_once '../functions.php';

setCorsHeaders();

$db = (new Database())->getConnection();
if (!$db) sendJsonResponse(500, false, "Database connection failed.");

$method = $_SERVER['REQUEST_METHOD'];
// Any logged in user can access these endpoints
$user = authenticateWithToken($db);

if ($method === 'GET') {
    // Return all registrations for the current user
    $query = "
        SELECT i.iscrizione_id, i.checkin_effettuato, i.ora_checkin, e.evento_id, e.titolo, e.data, e.descrizione 
        FROM iscrizioni i
        JOIN eventi e ON i.evento_id = e.evento_id
        WHERE i.utente_id = :utente_id
        ORDER BY e.data ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':utente_id', $user['utente_id']);
    $stmt->execute();
    $iscrizioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(200, true, "Registrations retrieved.", $iscrizioni);
}
elseif ($method === 'POST') {
    $data = getJsonInput();
    $evento_id = $data['evento_id'] ?? null;
    
    if (!$evento_id) {
        sendJsonResponse(400, false, "Evento ID is required.");
    }
    
    // Check if event exists and if it is in the future (up to the day before)
    $stmt = $db->prepare("SELECT data FROM eventi WHERE evento_id = :evento_id");
    $stmt->bindParam(':evento_id', $evento_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(404, false, "Event not found.");
    }
    
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    $eventDate = new DateTime($evento['data']);
    $today = new DateTime('today'); // Midnight today
    
    if ($eventDate <= $today) {
        sendJsonResponse(400, false, "Cannot register. Event date must be at least tomorrow.");
    }
    
    // Check if duplicate
    $checkStmt = $db->prepare("SELECT iscrizione_id FROM iscrizioni WHERE utente_id = :utente_id AND evento_id = :evento_id");
    $checkStmt->bindParam(':utente_id', $user['utente_id']);
    $checkStmt->bindParam(':evento_id', $evento_id);
    $checkStmt->execute();
    if ($checkStmt->rowCount() > 0) {
        sendJsonResponse(400, false, "Already registered for this event.");
    }

    // Insert
    $insertStmt = $db->prepare("INSERT INTO iscrizioni (utente_id, evento_id) VALUES (:utente_id, :evento_id)");
    $insertStmt->bindParam(':utente_id', $user['utente_id']);
    $insertStmt->bindParam(':evento_id', $evento_id);
    
    if ($insertStmt->execute()) {
        sendJsonResponse(201, true, "Registration successful.");
    } else {
        sendJsonResponse(500, false, "Failed to register.");
    }
}
elseif ($method === 'DELETE') {
    $evento_id = $_GET['evento_id'] ?? null;
    
    if (!$evento_id) {
        $data = getJsonInput(); // fallback body
        $evento_id = $data['evento_id'] ?? null;
    }

    if (!$evento_id) {
        sendJsonResponse(400, false, "Evento ID is required.");
    }

    // Check if event is in the future
    $stmt = $db->prepare("SELECT data FROM eventi WHERE evento_id = :evento_id");
    $stmt->bindParam(':evento_id', $evento_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        $eventDate = new DateTime($evento['data']);
        $today = new DateTime('today');
        
        if ($eventDate <= $today) {
            sendJsonResponse(400, false, "Cannot unregister. Event date must be at least tomorrow.");
        }
    }

    $deleteStmt = $db->prepare("DELETE FROM iscrizioni WHERE utente_id = :utente_id AND evento_id = :evento_id");
    $deleteStmt->bindParam(':utente_id', $user['utente_id']);
    $deleteStmt->bindParam(':evento_id', $evento_id);
    
    if ($deleteStmt->execute()) {
        sendJsonResponse(200, true, "Unregistered successfully.");
    } else {
        sendJsonResponse(500, false, "Failed to unregister.");
    }
}
else {
    sendJsonResponse(405, false, "Method not allowed.");
}
?>
