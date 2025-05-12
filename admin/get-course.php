<?php
header('Content-Type: application/json');
require_once 'connect.php';

try {
    // 查询课程和对应预约人数
    $stmt = $pdo->query("
        SELECT 
            c.*,
            IFNULL(SUM(b.head_count), 0) AS booking_count
        FROM 
            course_list c
        LEFT JOIN 
            course_booking b ON c.id = b.course_id AND b.status = 'booked'
        WHERE 
            c.state = 0
        GROUP BY 
            c.id
        ORDER BY 
            c.start_time DESC
    ");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 查询所有预约信息，附带用户姓名与预约人数（只统计有效预约）
    $bookingStmt = $pdo->query("
        SELECT 
            b.course_id,
            s.name AS student_name,
            b.head_count
        FROM 
            course_booking b
        INNER JOIN 
            student_list s ON b.student_id = s.id
        WHERE 
            b.status = 'booked'
    ");
    $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);

    // 组合课程与其对应的预约列表
    foreach ($courses as &$course) {
        $courseId = $course['id'];
        $course['bookings'] = array_values(array_filter($bookings, function ($b) use ($courseId) {
            return $b['course_id'] == $courseId;
        }));
    }

    echo json_encode([
        'success' => true,
        'courses' => $courses
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
