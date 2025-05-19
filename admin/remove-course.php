<?php
require_once '../connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$course_id = $data['course_id'] ?? 0;

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => '缺少课程ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. 课程 state 设为 -1 （已取消）
    $stmt = $pdo->prepare("UPDATE course_list SET state = -1 WHERE id = ?");
    $stmt->execute([$course_id]);

    // 2. 所有该课程预约 status 设为 cancelled
    $stmt2 = $pdo->prepare("UPDATE course_booking SET status = 'cancelled' WHERE course_id = ?");
    $stmt2->execute([$course_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => '课程已取消，所有预约已撤销']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '取消失败: ' . $e->getMessage()]);
}
