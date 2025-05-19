<?php
require_once '../connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
$now = date('Y-m-d H:i:s');

// 查找所有 state=1（已开始但未结束）且结束时间已过的课程
$stmt = $pdo->query("SELECT id FROM course_list WHERE state=1 AND DATE_ADD(start_time, INTERVAL duration MINUTE) < '$now'");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    $id = $course['id'];
    // 1. 更新学生预约状态
    $pdo->prepare("UPDATE course_booking SET status='done' WHERE course_id=? AND status='paid'")->execute([$id]);
    // 2. 更新课程状态为已结束
    $pdo->prepare("UPDATE course_list SET state=2 WHERE id=?")->execute([$id]);
}
echo "OK";
