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

    $sql = "
SELECT 
    SUM(cl.duration) AS total_minutes
FROM
    course_booking cb
JOIN
    course_list cl ON cb.course_id = cl.id
WHERE
    cb.student_id = :student_id
    AND cb.status <> 'cancelled'
    AND cl.start_time >= DATE_SUB(NOW(), INTERVAL (DAYOFWEEK(NOW()) - 2) DAY)
    AND cl.start_time < DATE_ADD(DATE_SUB(NOW(), INTERVAL (DAYOFWEEK(NOW()) - 2) DAY), INTERVAL 7 DAY)
";
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute([':student_id' => $student_id]);
    $result = $stmt2->fetch(PDO::FETCH_ASSOC);



    echo json_encode([
        'success' => true,
        'bookings' => $records,
        'total_minutes' => (int)($result['total_minutes'] ?? 0)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
