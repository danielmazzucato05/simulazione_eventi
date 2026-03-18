<?php
require_once 'Database.php';
require_once 'functions.php';

setCorsHeaders();

$db = (new Database())->getConnection();
if (!$db) sendJsonResponse(500, false, "Database connection failed.");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Both roles can view events
    authenticateWithToken($db);
    
    // We can filter by ID if provided: /api/eventi/index.php?id=xyz
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM eventi WHERE evento_id = :id");
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($event) {
            sendJsonResponse(200, true, "Event retrieved.", $event);
        } else {
            sendJsonResponse(404, false, "Event not found.");
        }
    } else {
        $stmt = $db->prepare("SELECT * FROM eventi ORDER BY data ASC");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(200, true, "Events retrieved.", $events);
    }
}
elseif ($method === 'POST') {
    // Only Organizer can create
    requireOrganizer($db);
    $data = getJsonInput();
    
    $titolo = filter_var($data['titolo'] ?? '', FILTER_SANITIZE_STRING);
    $data_evento = $data['data'] ?? '';
    $descrizione = filter_var($data['descrizione'] ?? '', FILTER_SANITIZE_STRING);
    
    if (empty($titolo) || empty($data_evento)) {
        sendJsonResponse(400, false, "Titolo and Data are required.");
    }
    
    $query = "INSERT INTO eventi (titolo, data, descrizione) VALUES (:titolo, :data, :descrizione)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':titolo', $titolo);
    $stmt->bindParam(':data', $data_evento);
    $stmt->bindParam(':descrizione', $descrizione);
    
    if ($stmt->execute()) {
        sendJsonResponse(201, true, "Event created.");
    } else {
        sendJsonResponse(500, false, "Failed to create event.");
    }
}
elseif ($method === 'PUT') {
    requireOrganizer($db);
    $data = getJsonInput();
    $evento_id = $data['evento_id'] ?? ($_GET['id'] ?? null);
    
    if (!$evento_id) {
        sendJsonResponse(400, false, "Evento ID is required.");
    }
    
    $titolo = filter_var($data['titolo'] ?? '', FILTER_SANITIZE_STRING);
    $data_evento = $data['data'] ?? '';
    $descrizione = filter_var($data['descrizione'] ?? '', FILTER_SANITIZE_STRING);
    
    $query = "UPDATE eventi SET titolo = :titolo, data = :data, descrizione = :descrizione WHERE evento_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':titolo', $titolo);
    $stmt->bindParam(':data', $data_evento);
    $stmt->bindParam(':descrizione', $descrizione);
    $stmt->bindParam(':id', $evento_id);
    
    if ($stmt->execute()) {
        sendJsonResponse(200, true, "Event updated.");
    } else {
        sendJsonResponse(500, false, "Failed to update event.");
    }
}
elseif ($method === 'DELETE') {
    requireOrganizer($db);
    $evento_id = $_GET['id'] ?? null;
    
    if (!$evento_id) {
        $data = getJsonInput(); // sometimes sent in body
        $evento_id = $data['evento_id'] ?? null;
    }
    
    if (!$evento_id) {
        sendJsonResponse(400, false, "Evento ID is required.");
    }
    
    $query = "DELETE FROM eventi WHERE evento_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $evento_id);
    
    if ($stmt->execute()) {
        sendJsonResponse(200, true, "Event deleted.");
    } else {
        sendJsonResponse(500, false, "Failed to delete event.");
    }
}
else {
    sendJsonResponse(405, false, "Method not allowed.");
}
?>
