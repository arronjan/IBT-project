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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Role: ' . ($_SESSION['role'] ?? 'none')]);
    exit;
}

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Log for debugging
error_log("Update tournament data: " . print_r($data, true));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

if (empty($data['tournament_id']) || empty($data['name']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $conn = getDBConnection();

    $stmt = $conn->prepare("UPDATE tournament SET name = ?, start_date = ?, end_date = ?, status = ? WHERE tournament_id = ?");

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        "ssssi",
        $data['name'],
        $data['start_date'],
        $data['end_date'],
        $data['status'],
        $data['tournament_id']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tournament updated successfully']);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Error updating tournament: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>