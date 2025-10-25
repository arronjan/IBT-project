<?php
require_once 'data_config.php';

header('Content-Type: application/json');

$tournament_id = $_GET['id'] ?? null;

if (!$tournament_id) {
    echo json_encode(['success' => false, 'message' => 'Tournament ID is required']);
    exit;
}

try {
    $conn = getDBConnection();

    // Get tournament
    $stmt = $conn->prepare("SELECT * FROM tournament WHERE tournament_id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Tournament not found']);
        exit;
    }

    $tournament = $result->fetch_assoc();
    $stmt->close();

    // Get participants
    $participants_sql = "SELECT 
        tp.player_id,
        p.player_name as user_name
    FROM tournament_player tp
    LEFT JOIN player p ON tp.player_id = p.player_id
    WHERE tp.tournament_id = ?";

    $stmt2 = $conn->prepare($participants_sql);
    $stmt2->bind_param("i", $tournament_id);
    $stmt2->execute();
    $participants_result = $stmt2->get_result();

    $participants = [];
    while ($participant = $participants_result->fetch_assoc()) {
        $participants[] = $participant;
    }
    $stmt2->close();

    $tournament['participants'] = $participants;

    $conn->close();

    echo json_encode([
        'success' => true,
        'tournament' => $tournament
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>