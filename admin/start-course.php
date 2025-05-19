<?php
header('Content-Type: application/json');
require_once '../connect.php';
$input = json_decode(file_get_contents("php://input"), true);
$course_id = $input['course_id'] ?? 0;

// 仅管理员页面或后台触发
if (!$course_id) {
    echo json_encode(['success' => false, 'message' => '缺少课程ID']);
    exit;
}

try {
    // 1. 查询课程详情
    $stmt = $pdo->prepare("SELECT * FROM course_list WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode(['success' => false, 'message' => '课程不存在']);
        exit;
    }

    // 2. 查询所有已预约学生（status=booked）
    $stmt = $pdo->prepare(
        "SELECT b.*, s.package, s.balance, s.frozen_balance, s.point, s.phone 
         FROM course_booking b
         JOIN student_list s ON b.student_id = s.id
         WHERE b.course_id = ? AND b.status = 'booked'"
    );
    $stmt->execute([$course_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $now = date('Y-m-d H:i:s'); // 马来西亚时区，确保PHP配置为+08:00
    $course_start = strtotime($course['start_time']);
    $results = [];

    foreach ($bookings as $b) {
        $student_id = $b['student_id'];
        $head_count = intval($b['head_count']) ?: 1; // 默认1人
        $phone = $b['phone'];

        // 判断会员，选择价格
        $isMember = $b['package'] && $b['package'] !== 'none';
        $price = $isMember ? $course['price_m'] : $course['price'];
        $amount = $price * $head_count;

        // 3. 扣款（balance 和 frozen_balance）
        $stmt2 = $pdo->prepare("UPDATE student_list SET balance = balance - ?, frozen_balance = frozen_balance - ? WHERE id = ?");
        $stmt2->execute([$amount, $amount, $student_id]);

        // 4. 只有有 package 才发积分
        $pointToAdd = 0;
        if ($isMember) {
            $booking_time = strtotime($b['booking_time']);
            $early = $course_start - $booking_time > 3600; // 提前一小时
            $pointToAdd = $early ? 2 : 1;

            // 5. 更新积分
            $stmt5 = $pdo->prepare("UPDATE student_list SET point = point + ? WHERE id = ?");
            $stmt5->execute([$pointToAdd, $student_id]);
        }

        // 6. 增加交易记录（type固定为payment）
        $stmt3 = $pdo->prepare(
            "INSERT INTO transaction_list 
                (member_id, phone, type, amount, point, head_count, course_id, time) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt3->execute([
            $student_id,
            $phone,
            'payment',         // type 只用 payment
            -$amount,
            $pointToAdd,
            $head_count,
            $course_id,
            $now
        ]);

        // 7. 更新预约状态为paid
        $stmt4 = $pdo->prepare("UPDATE course_booking SET status = 'paid' WHERE id = ?");
        $stmt4->execute([$b['id']]);

        $results[] = [
            "student_id" => $student_id,
            "deducted" => $amount,
            "added_points" => $pointToAdd
        ];
    }

    // 8. 课程状态设置为已开始（1）
    $stmt6 = $pdo->prepare("UPDATE course_list SET state = 1 WHERE id = ?");
    $stmt6->execute([$course_id]);

    echo json_encode(['success' => true, 'results' => $results]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
