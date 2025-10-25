<?php
session_start();
require_once 'data_config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$conn = getDBConnection();

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required.']);
    exit;
}

// FIXED: Changed 'users' to 'user'
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Account not found.']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    unset($user['password']);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'user' => $user
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
}

$stmt->close();
$conn->close();
?>