<?php
header('Content-Type: application/json');
require_once './connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$student_id = $input['id'] ?? null;

try {
    // 1. 获取我的预约（完整课程信息 + 预约人数）
    $my_bookings = [];
    if ($student_id) {
        $stmtBooking = $pdo->prepare("
            SELECT 
                c.id AS course_id,
                c.name,
                c.start_time,
                c.difficulty,
                c.coach,
                c.location,
                c.duration,
                c.price,
                c.price_m,
                b.head_count
            FROM 
                course_booking b
            INNER JOIN 
                course_list c ON b.course_id = c.id
            WHERE 
                b.student_id = :student_id
                AND b.status = 'booked'
                AND c.state = 0
            ORDER BY 
                c.start_time ASC
        ");
        $stmtBooking->execute([':student_id' => $student_id]);
        $my_bookings = $stmtBooking->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. 获取推荐课程（24 小时内 + 预约人数）
    $stmtRecommended = $pdo->query("
        SELECT 
            c.*,
            IFNULL(SUM(b.head_count), 0) AS booking_count
        FROM 
            course_list c
        LEFT JOIN 
            course_booking b ON c.id = b.course_id AND b.status = 'booked'
        WHERE 
            c.state = 0
            AND c.start_time >= NOW()
            AND c.start_time <= DATE_ADD(NOW(), INTERVAL 72 HOUR)
        GROUP BY 
            c.id
        ORDER BY 
            c.start_time ASC
    ");
    $recommended = $stmtRecommended->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'my_bookings' => $my_bookings,
        'recommended' => $recommended
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
