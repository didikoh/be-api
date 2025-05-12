<?php
require_once '../connect.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT          
        t.id AS transaction_id,
        t.member_id,
        s.name AS member_name,
        t.phone,
        t.type,
        t.amount,
        t.point,
        t.head_count,
        t.course_id,
        t.time,
        c.name AS course_name,
        c.start_time
        FROM transaction_list t
        LEFT JOIN student_list s ON t.member_id = s.id
        LEFT JOIN course_list c ON t.course_id = c.id
        ORDER BY t.id DESC
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $transactions]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "获取交易记录失败: " . $e->getMessage()]);
}
