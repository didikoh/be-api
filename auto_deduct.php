<?php
header('Content-Type: application/json');
require_once '../connect.php';

// 获取当前时间 +1 小时
$now = new DateTime();
$nextHour = clone $now;
$nextHour->modify('+1 hour');

try {
    // 查询预约状态为 booked 且尚未扣款的记录
    $stmt = $pdo->prepare("
        SELECT b.id AS booking_id, b.student_id, b.course_id, c.price, s.balance
        FROM course_booking b
        JOIN course_list c ON b.course_id = c.id
        JOIN student_list s ON b.student_id = s.id
        WHERE 
            b.status = 'booked'
            AND b.deducted = b'0'
            AND c.start_time BETWEEN :now AND :next_hour
    ");
    $stmt->execute([
        ':now' => $now->format('Y-m-d H:i:s'),
        ':next_hour' => $nextHour->format('Y-m-d H:i:s')
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $successCount = 0;
    $failCount = 0;

    foreach ($results as $row) {
        $booking_id = $row['booking_id'];
        $student_id = $row['student_id'];
        $price = $row['price'];
        $balance = $row['balance'];

        if ($balance >= $price) {
            // 扣款
            $pdo->beginTransaction();

            $stmt1 = $pdo->prepare("UPDATE student_list SET balance = balance - :price WHERE id = :student_id");
            $stmt2 = $pdo->prepare("UPDATE course_booking SET deducted = b'1' WHERE id = :booking_id");

            $stmt1->execute([':price' => $price, ':student_id' => $student_id]);
            $stmt2->execute([':booking_id' => $booking_id]);

            $pdo->commit();
            $successCount++;
        } else {
            // 可记录状态或通知
            $failCount++;
        }
    }

    echo json_encode([
        "success" => true,
        "deducted" => $successCount,
        "failed_due_to_insufficient_balance" => $failCount
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
