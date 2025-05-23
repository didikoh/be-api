<?php
header('Content-Type: application/json');
require_once './connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$student_id = $input['id'] ?? null;

try {
    // 根据是否有 student_id 动态拼接 SQL
    $sql = "
        SELECT 
            c.*,
            IFNULL(SUM(b.head_count), 0) AS booking_count
            " . ($student_id ? ",
            MAX(CASE WHEN b.student_id = :student_id THEN 1 ELSE 0 END) AS is_booked" : "") . "
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
    ";

    $stmt = $pdo->prepare($sql);
    if ($student_id) {
        $stmt->execute([':student_id' => $student_id]);
    } else {
        $stmt->execute();
    }

    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($courses as &$course) {
        $course['booking_count'] = (int) $course['booking_count'];
        if ($student_id) {
            $course['is_booked'] = isset($course['is_booked']) ? $course['is_booked'] == 1 : false;
        }
    }

    echo json_encode([
        'success' => true,
        'courses' => $courses,
        'student_id' => $student_id, // 传回 id，更有用
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
