<?php
header('Content-Type: application/json');
require_once './connect.php'; // 调整为你的连接文件路径

// 获取 JSON 请求数据
$input = json_decode(file_get_contents('php://input'), true);

$student_id = $input['student_id'] ?? null;
$course_id = $input['course_id'] ?? null;
$head_count = $input['head_count'] ?? 1;

if (!$student_id || !$course_id) {
    echo json_encode([
        "success" => false,
        "message" => "student_id 和 course_id 是必填项"
    ]);
    exit;
}

// 检查是否已预约同一课程
$stmtCheck = $pdo->prepare("SELECT id FROM course_booking WHERE student_id = :student_id AND course_id = :course_id AND status = 'booked'");
$stmtCheck->execute([
    ':student_id' => $student_id,
    ':course_id' => $course_id
]);

if ($stmtCheck->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "你已预约了此课程"
    ]);
    exit;
}

// 插入预约记录
$stmt = $pdo->prepare("INSERT INTO course_booking (student_id, course_id, head_count) VALUES (:student_id, :course_id, :head_count)");
$success = $stmt->execute([
    ':student_id' => $student_id,
    ':course_id' => $course_id,
    ':head_count' => $head_count
]);

if ($success) {
    echo json_encode([
        "success" => true,
        "message" => "预约成功"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "预约失败，请稍后再试"
    ]);
}
