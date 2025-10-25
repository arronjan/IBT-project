<?php
require_once 'data_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $user_id = $input['user_id'] ?? null;
    $court_id = $input['court_id'] ?? null;
    $booking_date = $input['booking_date'] ?? null;
    $start_time = $input['start_time'] ?? null;
    $end_time = $input['end_time'] ?? null;
    $payment_method = $input['payment_method'] ?? 'Walk-in';
    $gcash_reference = $input['gcash_reference'] ?? null;
    $proof_of_payment = $input['proof_of_payment'] ?? null;

    if (!$user_id || !$court_id || !$booking_date || !$start_time || !$end_time) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if ($start_time >= $end_time) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit;
    }

    $today = date('Y-m-d');
    if ($booking_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Cannot book for past dates']);
        exit;
    }

    // Calculate total amount
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $duration_minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
    $duration_hours = $duration_minutes / 60;
    $total_amount = $duration_hours * 250;

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT court_id, availability_status FROM court WHERE court_id = ?");
    $stmt->bind_param("i", $court_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Court not found']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $court = $result->fetch_assoc();
    if ($court['availability_status'] !== 'Available') {
        echo json_encode(['success' => false, 'message' => 'Court is currently unavailable']);
        $stmt->close();
        $conn->close();
        exit;
    }

    // Check for conflicts
    $stmt = $conn->prepare("
        SELECT booking_id, start_time, end_time 
        FROM booking 
        WHERE court_id = ? 
        AND booking_date = ? 
        AND status NOT IN ('Cancelled', 'Completed')
    ");

    $stmt->bind_param("is", $court_id, $booking_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $has_conflict = false;
    while ($existing = $result->fetch_assoc()) {
        if (
            ($start_time >= $existing['start_time'] && $start_time < $existing['end_time']) ||
            ($end_time > $existing['start_time'] && $end_time <= $existing['end_time']) ||
            ($start_time <= $existing['start_time'] && $end_time >= $existing['end_time'])
        ) {
            $has_conflict = true;
            break;
        }
    }

    if ($has_conflict) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose a different time.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    // Insert booking
    $status = 'Pending';
    $payment_status = 'pending';

    $stmt = $conn->prepare("
        INSERT INTO booking (user_id, court_id, booking_date, start_time, end_time, status, total_amount, payment_method, payment_status, gcash_reference, proof_of_payment) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iissssdssss",
        $user_id,
        $court_id,
        $booking_date,
        $start_time,
        $end_time,
        $status,
        $total_amount,
        $payment_method,
        $payment_status,
        $gcash_reference,
        $proof_of_payment
    );

    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;

        $stmt_info = $conn->prepare("
            SELECT u.name as user_name, c.court_name
            FROM user u, court c
            WHERE u.user_id = ? AND c.court_id = ?
        ");
        $stmt_info->bind_param("ii", $user_id, $court_id);
        $stmt_info->execute();
        $info_result = $stmt_info->get_result();
        $info = $info_result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $booking_id,
            'booking_details' => [
                'user_name' => $info['user_name'] ?? 'Unknown',
                'court_name' => $info['court_name'] ?? 'Unknown',
                'booking_date' => $booking_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'total_amount' => $total_amount,
                'payment_method' => $payment_method,
                'status' => $status
            ]
        ]);

        $stmt_info->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create booking: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>