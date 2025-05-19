<?php
header('Content-Type: application/json');
require_once '../connect.php';

$insert = json_decode(file_get_contents("php://input"), true);
$booking_id = $insert['booking_id'] ?? 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => '缺少参数']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE course_booking SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$booking_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
