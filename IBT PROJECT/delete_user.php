<?php
require_once 'data_config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

error_log("Delete user request: " . print_r($data, true));

$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Prevent deleting the main admin user (ID 1)
if ($user_id == 1) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete the main administrator account']);
    exit;
}

// Prevent deleting yourself
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

try {
    $conn = getDBConnection();

    // Check if user has existing bookings
    $check_bookings = $conn->prepare("SELECT COUNT(*) as booking_count FROM booking WHERE user_id = ?");
    $check_bookings->bind_param("i", $user_id);
    $check_bookings->execute();
    $result = $check_bookings->get_result();
    $row = $result->fetch_assoc();
    $booking_count = $row['booking_count'];
    $check_bookings->close();

    if ($booking_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete user with existing bookings. Please cancel their bookings first or keep the user account.'
        ]);
        $conn->close();
        exit;
    }

    // Check if user is in tournaments
    $check_tournaments = $conn->prepare("SELECT COUNT(*) as tournament_count FROM tournament_player WHERE player_id = ?");
    if ($check_tournaments) {
        $check_tournaments->bind_param("i", $user_id);
        $check_tournaments->execute();
        $result2 = $check_tournaments->get_result();
        $row2 = $result2->fetch_assoc();
        $tournament_count = $row2['tournament_count'];
        $check_tournaments->close();

        if ($tournament_count > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete user registered in tournaments. Please remove them from tournaments first.'
            ]);
            $conn->close();
            exit;
        }
    }

    // Delete from player table first (if exists)
    $conn->query("DELETE FROM player WHERE player_id = $user_id");

    // Delete user
    $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            error_log("User deleted successfully: ID $user_id");
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Error deleting user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>