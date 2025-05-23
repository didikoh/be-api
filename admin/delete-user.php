<?php
header('Content-Type: application/json');
require_once '../connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$phone = $input['phone'] ?? null;

if (!$phone) {
    echo json_encode(['success' => false, 'message' => '缺少参数']);
    exit;
}

// ========== 逻辑删除 ==========

try {
    $stmt = $pdo->prepare("UPDATE user_list SET state = -1 WHERE phone = ?");
    $stmt->execute([$phone]);
    echo json_encode(['success' => true, 'message' => '会员已删除']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '删除失败: ' . $e->getMessage()]);
}
exit;
