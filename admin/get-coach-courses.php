<?php
header('Content-Type: application/json');
require_once '../connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur'); // 马来西亚时区

// 获取参数
$coach_name = $_GET['coach_name'] ?? '';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

if (!$coach_name) {
    echo json_encode(["success" => false, "message" => "缺少教师参数"]);
    exit;
}

// 计算当月范围
$month_start = date('Y-m-01 00:00:00', strtotime("$year-$month-01"));
$month_end = date('Y-m-t 23:59:59', strtotime("$year-$month-01"));

try {
    // 查找该教师该月所有课程
    $stmt = $pdo->prepare("SELECT id, name, start_time FROM course_list WHERE coach = :coach AND start_time BETWEEN :start AND :end ORDER BY start_time ASC");
    $stmt->execute([
        ':coach' => $coach_name,
        ':start' => $month_start,
        ':end' => $month_end,
    ]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 查询每门课人数
    foreach ($courses as &$course) {
        $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT student_id) AS student_count FROM course_booking WHERE course_id = :course_id AND status = 'booked'");
        $stmt2->execute([':course_id' => $course['id']]);
        $course['student_count'] = (int)($stmt2->fetchColumn());
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "coach" => $coach_name,
            "year" => $year,
            "month" => $month,
            "courses" => $courses,
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "查询失败: " . $e->getMessage(),
    ]);
}
