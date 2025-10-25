<?php
require_once 'data_config.php';

header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;
$role = $_GET['role'] ?? 'Member';

$conn = getDBConnection();

$stats = [];

if ($role === 'Admin') {
    // Admin stats - all data
    $sql = "SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(total_amount) as total_spent
    FROM booking";

    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats = $row;
    }

    // Get total tournaments
    $tournament_sql = "SELECT COUNT(*) as tournaments_joined FROM tournament";
    $tournament_result = $conn->query($tournament_sql);
    if ($tournament_result && $t_row = $tournament_result->fetch_assoc()) {
        $stats['tournaments_joined'] = $t_row['tournaments_joined'];
    }

} else {
    // Member stats - only their data
    $sql = "SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(total_amount) as total_spent
    FROM booking
    WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $stats = $row;
    }
    $stmt->close();

    // Get member's tournaments
    $tournament_sql = "SELECT COUNT(*) as tournaments_joined 
                      FROM tournament_player 
                      WHERE player_id = ?";
    $stmt2 = $conn->prepare($tournament_sql);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $tournament_result = $stmt2->get_result();

    if ($tournament_result && $t_row = $tournament_result->fetch_assoc()) {
        $stats['tournaments_joined'] = $t_row['tournaments_joined'];
    }
    $stmt2->close();
}

echo json_encode([
    'success' => true,
    'stats' => $stats
]);

$conn->close();
?>