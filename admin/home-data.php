<?php
header('Content-Type: application/json');
require_once '../connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');

try {
    // 用户总数
    $stmt1 = $pdo->query("SELECT COUNT(*) AS total FROM student_list");
    $user_count = (int)($stmt1->fetch()['total']);

    // 会员总数
    $stmt2 = $pdo->query("SELECT COUNT(*) AS total FROM student_list WHERE package IS NOT NULL AND package != ''");
    $member_count = (int)($stmt2->fetch()['total']);

    // 今日预约人数总和（根据当天所有课程的预约总人数 head_count）
    $stmt3 = $pdo->prepare("
        SELECT IFNULL(SUM(b.head_count), 0) AS total
        FROM course_list c
        LEFT JOIN course_booking b ON c.id = b.course_id
        WHERE DATE(CONVERT_TZ(c.start_time, '+00:00', '+08:00')) = :today
    ");
    $stmt3->execute([':today' => $today]);
    $booking_count = (int)($stmt3->fetch()['total']);

    // 今日交易额
    $stmt4 = $pdo->prepare("
        SELECT IFNULL(SUM(amount), 0) AS total 
        FROM transaction_list 
        WHERE type = 'pay' 
        AND DATE(time) = :today
    ");
    $stmt4->execute([':today' => $today]);
    $total_amount = (int)($stmt4->fetch()['total']);

    echo json_encode([
        "success" => true,
        "data" => [
            "user_count"     => $user_count,
            "member_count"   => $member_count,
            "booking_count"  => $booking_count, // 这里就是今日所有课程预约总人数
            "total_amount"   => $total_amount
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "数据获取失败: " . $e->getMessage()
    ]);
}
