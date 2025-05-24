<?php
header('Content-Type: application/json');
require_once '../connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$userId = $input['user_id'] ?? null;

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

try {
    // 1. 检查教练是否存在（可选，但更安全）
    $stmtCoach = $pdo->prepare("SELECT id, name FROM coach_list WHERE id = :user_id LIMIT 1");
    $stmtCoach->execute([':user_id' => $userId]);
    $coach = $stmtCoach->fetch();
    if (!$coach) {
        echo json_encode(["success" => false, "message" => "Coach not found"]);
        exit;
    }

    // 2. 本月时间区间
    $startOfMonth = date('Y-m-01 00:00:00');
    $endOfMonth = date('Y-m-t 23:59:59');

    // 3. 本月课程数
    $stmtClassCount = $pdo->prepare("
        SELECT COUNT(*) AS class_count
        FROM course_list
        WHERE coach_id = :coach_id
          AND start_time >= :start_of_month
          AND start_time <= :end_of_month
    ");
    $stmtClassCount->execute([
        ':coach_id' => $userId,
        ':start_of_month' => $startOfMonth,
        ':end_of_month' => $endOfMonth
    ]);
    $classCount = (int)$stmtClassCount->fetchColumn();

    // 4. 本月所有课程预约人数总和
    $stmtStudentCount = $pdo->prepare("
        SELECT COALESCE(SUM(b.head_count), 0) AS student_count
        FROM course_list c
        JOIN course_booking b ON b.course_id = c.id AND b.status = 'booked'
        WHERE c.coach_id = :coach_id
          AND c.start_time >= :start_of_month
          AND c.start_time <= :end_of_month
    ");
    $stmtStudentCount->execute([
        ':coach_id' => $userId,
        ':start_of_month' => $startOfMonth,
        ':end_of_month' => $endOfMonth
    ]);
    $studentCount = (int)$stmtStudentCount->fetchColumn();

    // 5. 所有课程资料（每门课 booking_count 为 head_count 总和）
    $stmtCourses = $pdo->prepare("
        SELECT 
            c.*,
            (
                SELECT COALESCE(SUM(b.head_count), 0)
                FROM course_booking b 
                WHERE b.course_id = c.id AND b.status = 'booked'
            ) AS booking_count
        FROM course_list c
        WHERE c.coach_id = :coach_id
        ORDER BY c.start_time DESC
    ");
    $stmtCourses->execute([':coach_id' => $userId]);
    $courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

    // 6. 返回
    echo json_encode([
        "success" => true,
        "courses" => $courses,
        "classCountThisMonth" => $classCount,
        "studentCountThisMonth" => $studentCount
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
