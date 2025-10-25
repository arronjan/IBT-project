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

error_log("Add user request: " . print_r($data, true));

// Validate input
if (!$data || empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];
$role = $data['role'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate role
if (!in_array($role, ['Admin', 'Member'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Validate password length
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    $conn = getDBConnection();

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
    if (!$check_stmt) {
        throw new Exception('Prepare check failed: ' . $conn->error);
    }

    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    $check_stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Prepare insert failed: ' . $conn->error);
    }

    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Also insert into player table
        $player_stmt = $conn->prepare("INSERT INTO player (player_id, player_name, player_email) VALUES (?, ?, ?)");
        if ($player_stmt) {
            $player_stmt->bind_param("iss", $user_id, $name, $email);
            $player_stmt->execute();
            $player_stmt->close();
        }

        error_log("User created successfully: ID=$user_id, Role=$role");

        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $user_id,
            'role' => $role
        ]);
        exit;

    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Error creating user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>