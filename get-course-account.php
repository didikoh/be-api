<?php
header('Content-Type: application/json');
require_once './connect.php';

// 获取 JSON 输入
$input = json_decode(file_get_contents("php://input"), true);
$student_id = $input['student_id'] ?? null;

if (!$student_id) {
    echo json_encode([
        'success' => false,
        'message' => '缺少用户ID'
    ]);
    exit;
}

try {
    // 获取该用户所有预约记录及课程资料
    $stmt = $pdo->prepare("
        SELECT 
            b.id AS booking_id,
            b.course_id,
            b.status,
            b.head_count,
            b.booking_time,
            c.name AS course_name,
            c.coach,
            c.location,
            c.start_time,
            c.duration,
            c.difficulty,
            c.price,
            c.price_m
        FROM 
            course_booking b
        INNER JOIN 
            course_list c ON b.course_id = c.id
        WHERE 
            b.student_id = :student_id
        ORDER BY 
            c.start_time DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bookings' => $records
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
