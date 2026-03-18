<?php
require_once '../Database.php';
require_once '../functions.php';

setCorsHeaders();

$db = (new Database())->getConnection();
if (!$db) sendJsonResponse(500, false, "Database connection failed.");

$method = $_SERVER['REQUEST_METHOD'];

// Only Organizer can manage check-ins
requireOrganizer($db);

if ($method === 'GET') {
    // Get all registrations for a specific event
    $evento_id = $_GET['evento_id'] ?? null;
    if (!$evento_id) {
        sendJsonResponse(400, false, "Evento ID is required.");
    }
    
    $query = "
        SELECT i.iscrizione_id, i.checkin_effettuato, i.ora_checkin, u.utente_id, u.nome, u.cognome, u.email 
        FROM iscrizioni i
        JOIN utenti u ON i.utente_id = u.utente_id
        WHERE i.evento_id = :evento_id
        ORDER BY u.cognome ASC, u.nome ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':evento_id', $evento_id);
    $stmt->execute();
    
    $iscritti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(200, true, "Registrations retrieved.", $iscritti);
}
elseif ($method === 'PUT' || $method === 'POST') {
    // Perform check-in
    $data = getJsonInput();
    $iscrizione_id = $data['iscrizione_id'] ?? null;
    $status = isset($data['status']) ? (bool)$data['status'] : true; // Allow toggle if needed, default true
    
    if (!$iscrizione_id) {
        sendJsonResponse(400, false, "Iscrizione ID is required.");
    }
    
    $ora_checkin = $status ? date('Y-m-d H:i:s') : null;
    $statusStr = $status ? 'true' : 'false';
    
    $query = "UPDATE iscrizioni SET checkin_effettuato = :status, ora_checkin = :ora_checkin WHERE iscrizione_id = :id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':status', $statusStr);
    $stmt->bindParam(':ora_checkin', $ora_checkin);
    $stmt->bindParam(':id', $iscrizione_id);
    
    if ($stmt->execute()) {
        sendJsonResponse(200, true, "Check-in updated successfully.");
    } else {
        sendJsonResponse(500, false, "Failed to update check-in.");
    }
}
else {
    sendJsonResponse(405, false, "Method not allowed.");
}
?>
