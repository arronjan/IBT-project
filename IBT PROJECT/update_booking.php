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

// Log for debugging
error_log("Update booking data: " . print_r($data, true));

if (!$data || !isset($data['booking_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$booking_id = $data['booking_id'];
$status = $data['status'];

// Validate status
$valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $status]);
    exit;
}

try {
    $conn = getDBConnection();

    // First check if booking exists
    $check_stmt = $conn->prepare("SELECT booking_id, status FROM booking WHERE booking_id = ?");
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        $check_stmt->close();
        $conn->close();
        exit;
    }

    $current_booking = $result->fetch_assoc();
    $check_stmt->close();

    // Update booking status
    $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE booking_id = ?");

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("si", $status, $booking_id);

    if ($stmt->execute()) {
        error_log("Booking updated successfully. ID: $booking_id, Status: $status");
        echo json_encode([
            'success' => true,
            'message' => 'Booking status updated successfully',
            'previous_status' => $current_booking['status'],
            'new_status' => $status
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Error updating booking: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>