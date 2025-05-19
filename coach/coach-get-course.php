<?php
header('Content-Type: application/json');
require_once '../connect.php'; // 包含 PDO 和数据库连接

$input = json_decode(file_get_contents("php://input"), true);
$userId = $input['user_id'] ?? null;

if (!$userId) {
    echo json_encode(["success" => false, "message" => "缺少 user_id"]);
    exit;
}

try {
    // Step 1: 获取 coach_list.id
    $stmtCoach = $pdo->prepare("SELECT name FROM coach_list WHERE id = :user_id LIMIT 1");
    $stmtCoach->execute([':user_id' => $userId]);
    $coach = $stmtCoach->fetch();

    if (!$coach) {
        echo json_encode(["success" => false, "message" => "教练不存在"]);
        exit;
    }

    $coachName = $coach['name'];

    // Step 2: 获取课程资料 + 每门课程预约人数
    $stmtCourses = $pdo->prepare("
        SELECT 
            c.*,
            (
                SELECT COUNT(*) 
                FROM course_booking b 
                WHERE b.course_id = c.id AND b.status = 'booked'
            ) AS booking_count
        FROM course_list c
        WHERE c.coach = :coach_name
        ORDER BY c.start_time DESC
    ");
    $stmtCourses->execute([':coach_name' => $coachName]);
    $courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "courses" => $courses]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
