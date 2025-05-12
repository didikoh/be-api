<?php
header('Content-Type: application/json');
require_once './connect.php'; // 修改为你的实际连接路径

$input = json_decode(file_get_contents('php://input'), true);

$booking_id = $input['booking_id'] ?? null;
$student_id = $input['student_id'] ?? null;
$course_id = $input['course_id'] ?? null;

if (!$booking_id && (!$student_id || !$course_id)) {
    echo json_encode([
        "success" => false,
        "message" => "请提供 booking_id，或 student_id + course_id"
    ]);
    exit;
}

try {
    if ($booking_id) {
        // 通过 booking_id 取消
        $stmt = $pdo->prepare("UPDATE course_booking SET status = 'cancelled' WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
    } else {
        // 通过 student_id + course_id 取消（只取消状态为 booked 的）
        $stmt = $pdo->prepare("
            UPDATE course_booking 
            SET status = 'cancelled' 
            WHERE student_id = :student_id AND course_id = :course_id AND status = 'booked'
        ");
        $stmt->execute([
            ':student_id' => $student_id,
            ':course_id' => $course_id
        ]);
    }

    echo json_encode([
        "success" => true,
        "message" => "取消成功"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "取消失败: " . $e->getMessage()
    ]);
}
