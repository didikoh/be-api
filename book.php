<?php
header('Content-Type: application/json');
require_once './connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$student_id   = $input['student_id']   ?? null;
$course_id    = $input['course_id']    ?? null;
$head_count   = $input['head_count']   ?? 1;
$frozen_price = $input['frozen_price'] ?? null;

if (!$student_id || !$course_id || $frozen_price === null) {
    echo json_encode([
        "success" => false,
        "message" => "student_id, course_id, and frozen_price are required"
    ]);
    exit;
}

if ($frozen_price <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid frozen amount"
    ]);
    exit;
}

// 检查是否已预约
$stmtCheck = $pdo->prepare("SELECT id FROM course_booking WHERE student_id = :student_id AND course_id = :course_id AND status != 'cancelled'");
$stmtCheck->execute([
    ':student_id' => $student_id,
    ':course_id' => $course_id
]);
if ($stmtCheck->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "You have already booked this course"
    ]);
    exit;
}

// 插入预约
$stmt = $pdo->prepare("INSERT INTO course_booking (student_id, course_id, head_count) VALUES (:student_id, :course_id, :head_count)");
$success = $stmt->execute([
    ':student_id' => $student_id,
    ':course_id' => $course_id,
    ':head_count' => $head_count
]);

if ($success) {
    // 更新冻结余额
    $stmtFrozen = $pdo->prepare("UPDATE student_list SET frozen_balance = frozen_balance + :amount WHERE id = :id");
    $stmtFrozen->execute([
        ':amount' => $frozen_price,
        ':id' => $student_id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Booking successful"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Booking failed, please try again later"
    ]);
}

