<?php
header('Content-Type: application/json');
require_once '../connect.php';

// 当前月份起止
date_default_timezone_set('Asia/Kuala_Lumpur'); // 马来西亚时区
$month_start = date('Y-m-01 00:00:00');
$month_end = date('Y-m-t 23:59:59');

try {
    // 1. 取所有教师
    $stmt = $pdo->query("SELECT * FROM coach_list ORDER BY id DESC");
    $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. 批量查课程和学生数
    foreach ($coaches as &$coach) {
        $coachName = $coach['name'];

        // 2.1 本月课程数
        $courseStmt = $pdo->prepare("SELECT id FROM course_list WHERE coach = :coach AND start_time BETWEEN :month_start AND :month_end");
        $courseStmt->execute([
            ':coach' => $coachName,
            ':month_start' => $month_start,
            ':month_end' => $month_end,
        ]);
        $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
        $courseIds = array_column($courses, 'id');
        $coach['month_course_count'] = count($courseIds);

        // 2.2 本月总学生数（去重）
        if (count($courseIds) > 0) {
            // 构建 IN 查询
            $in  = str_repeat('?,', count($courseIds) - 1) . '?';
            $bookStmt = $pdo->prepare("SELECT DISTINCT student_id FROM course_booking WHERE course_id IN ($in) AND status = 'booked'");
            $bookStmt->execute($courseIds);
            $students = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
            $coach['month_student_count'] = count($students);
        } else {
            $coach['month_student_count'] = 0;
        }
    }

    echo json_encode([
        "success" => true,
        "data" => $coaches,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "读取教师资料失败: " . $e->getMessage(),
    ]);
}
