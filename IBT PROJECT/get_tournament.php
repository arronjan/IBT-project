<?php
require_once 'data_config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // AUTO-UPDATE TOURNAMENT STATUSES BASED ON DATES
    $today = date('Y-m-d');

    // Update upcoming to ongoing if start date has arrived
    $conn->query("UPDATE tournament SET status = 'ongoing' 
                  WHERE status = 'upcoming' 
                  AND start_date <= '$today' 
                  AND end_date >= '$today'");

    // Update ongoing to completed if end date has passed
    $conn->query("UPDATE tournament SET status = 'completed' 
                  WHERE status = 'ongoing' 
                  AND end_date < '$today'");

    // Get all tournaments
    $sql = "SELECT * FROM tournament ORDER BY 
        CASE 
            WHEN status = 'ongoing' THEN 1
            WHEN status = 'upcoming' THEN 2
            WHEN status = 'completed' THEN 3
            ELSE 4
        END,
        start_date DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $tournaments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get participants for this tournament
            $participants = [];

            $participants_sql = "SELECT 
                tp.player_id,
                p.player_name as user_name
            FROM tournament_player tp
            LEFT JOIN player p ON tp.player_id = p.player_id
            WHERE tp.tournament_id = ?";

            $stmt = $conn->prepare($participants_sql);
            if ($stmt) {
                $stmt->bind_param("i", $row['tournament_id']);
                $stmt->execute();
                $participants_result = $stmt->get_result();

                while ($participant = $participants_result->fetch_assoc()) {
                    $participants[] = $participant;
                }
                $stmt->close();
            }

            $row['participants'] = $participants;
            $tournaments[] = $row;
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'tournaments' => $tournaments
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>