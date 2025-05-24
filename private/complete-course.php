<?php
// 设为马来西亚时区
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once '../connect.php'; // 替换为你的实际路径

try {
    // 获取当前时间
    $now = date('Y-m-d H:i:s');

    // 1. 更新已过期的课程 state = 2
    $updateCourse = $pdo->prepare("UPDATE course_list SET state = 2 WHERE start_time < :now AND (state = 0 OR state = 1)");
    $updateCourse->execute([':now' => $now]);

    // 2. 更新已过期课程下所有已预约(status=booked)记录为attended
    //    如果你要的是status=Completed，请见下方备注
    $updateBooking = $pdo->prepare(
        "UPDATE course_booking 
         SET status = 'completed'
         WHERE course_id IN (
            SELECT id FROM course_list WHERE start_time < :now
         ) AND (status = 'booked' OR status = 'paid' OR status = '')"
    );
    $updateBooking->execute([':now' => $now]);

    echo json_encode([
        "success" => true,
        "message" => "Completed course update and booking status update."
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
