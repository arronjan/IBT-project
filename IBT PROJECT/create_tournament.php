<?php
require_once 'data_config.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Start session first
session_start();

// Log session info
error_log("Session data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in. Session user_id not found.']);
    exit;
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only admins can create tournaments. Your role: ' . ($_SESSION['role'] ?? 'none')]);
    exit;
}

// Get and decode JSON data
$rawData = file_get_contents('php://input');
error_log("Raw data: " . $rawData);

$data = json_decode($rawData, true);
error_log("Decoded data: " . print_r($data, true));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Tournament name is required']);
    exit;
}

if (empty($data['start_date'])) {
    echo json_encode(['success' => false, 'message' => 'Start date is required']);
    exit;
}

if (empty($data['end_date'])) {
    echo json_encode(['success' => false, 'message' => 'End date is required']);
    exit;
}

if (empty($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Status is required']);
    exit;
}

try {
    $conn = getDBConnection();

    // Check if connection is valid
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $stmt = $conn->prepare("INSERT INTO tournament (name, start_date, end_date, status) VALUES (?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ssss",
        $data['name'],
        $data['start_date'],
        $data['end_date'],
        $data['status']
    );

    if ($stmt->execute()) {
        $tournament_id = $stmt->insert_id;
        error_log("Tournament created successfully with ID: " . $tournament_id);

        echo json_encode([
            'success' => true,
            'message' => 'Tournament created successfully',
            'tournament_id' => $tournament_id
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Error creating tournament: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>