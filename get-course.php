<?php
header('Content-Type: application/json');
require_once 'connect.php';

$input = json_decode(file_get_contents("php://input"), true);
// 获取前端传入的 phone（通过 POST 或 GET 均可）
$phone = $input['phone'] ?? null;
$student_id = null;

if ($phone) {
    $stmtUser = $pdo->prepare("SELECT id FROM student_list WHERE phone = :phone LIMIT 1");
    $stmtUser->execute([':phone' => $phone]);
    $student = $stmtUser->fetch();
    if ($student) {
        $student_id = $student['id'];
    }
}else{
    echo json_encode([
        'success' => false,
        'message' => '用户未登录'
    ]);
    exit;
}

try {
    // 查询课程及预约总人数 + 当前用户是否已预约
    $sql = "
        SELECT 
            c.*,
            IFNULL(SUM(b.head_count), 0) AS booking_count" .
            ($student_id ? ",
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
        $course['is_booked'] = isset($course['is_booked']) ? $course['is_booked'] == 1 : false;
    }

    echo json_encode([
        'success' => true,
        'courses' => $courses,
        'student_id' => $phone
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
