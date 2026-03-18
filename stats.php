<?php
require_once 'Database.php';
require_once 'functions.php';

setCorsHeaders();

$db = (new Database())->getConnection();
if (!$db) sendJsonResponse(500, false, "Database connection failed.");

$method = $_SERVER['REQUEST_METHOD'];

// Only Organizer can manage stats
requireOrganizer($db);

if ($method === 'GET') {
    // Advanced Requirement: List of past events, check-ins, percentage of participation. Filter by date (from/to)
    $dal = $_GET['dal'] ?? null;
    $al = $_GET['al'] ?? null;
    
    // Base query to get past events and left join with registrations
    $query = "
        SELECT 
            e.evento_id, e.titolo, e.data, e.descrizione,
            COUNT(i.iscrizione_id) as total_iscritti,
            SUM(CASE WHEN i.checkin_effettuato = true THEN 1 ELSE 0 END) as total_checkin
        FROM eventi e
        LEFT JOIN iscrizioni i ON e.evento_id = i.evento_id
        WHERE date(e.data) < current_date
    ";
    
    $params = [];
    
    if ($dal) {
        $query .= " AND date(e.data) >= :dal";
        $params[':dal'] = $dal;
    }
    if ($al) {
        $query .= " AND date(e.data) <= :al";
        $params[':al'] = $al;
    }
    
    $query .= " GROUP BY e.evento_id ORDER BY e.data DESC";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add percentage
    foreach ($stats as &$stat) {
        $total = (int)$stat['total_iscritti'];
        $checkin = (int)($stat['total_checkin'] ?? 0);
        $stat['percentage'] = $total > 0 ? round(($checkin / $total) * 100, 2) : 0;
    }
    
    sendJsonResponse(200, true, "Stats retrieved.", $stats);
}
else {
    sendJsonResponse(405, false, "Method not allowed.");
}
?>
