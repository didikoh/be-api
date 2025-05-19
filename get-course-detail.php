<?php
header('Content-Type: application/json');
require_once './connect.php';

// 读取 JSON 请求
$input = json_decode(file_get_contents("php://input"), true);
$course_id = $input['course_id'] ?? null;
$student_id = $input['student_id'] ?? null;

if (!$course_id) {
    echo json_encode([
        'success' => false,
        'message' => '缺少课程ID'
    ]);
    exit;
}

try {
    // 1. 获取课程资料
    $stmtCourse = $pdo->prepare("SELECT * FROM course_list WHERE id = :course_id AND state = 0 LIMIT 1");
    $stmtCourse->execute([':course_id' => $course_id]);
    $course = $stmtCourse->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode([
            'success' => false,
            'message' => '找不到课程'
        ]);
        exit;
    }

    // 2. 如果有传入 student_id，检查预约状态
    if ($student_id) {
        $stmtBooking = $pdo->prepare("
            SELECT head_count 
            FROM course_booking 
            WHERE course_id = :course_id 
              AND student_id = :student_id 
              AND status = 'booked'
            LIMIT 1
        ");
        $stmtBooking->execute([
            ':course_id' => $course_id,
            ':student_id' => $student_id
        ]);
        $booking = $stmtBooking->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'course' => $course,
            'is_booked' => $booking ? true : false,
            'head_count' => $booking ? (int)$booking['head_count'] : 0
        ]);
    } else {
        // 未传入 student_id：只返回课程资料
        echo json_encode([
            'success' => true,
            'course' => $course
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
