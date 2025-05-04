<?php
header('Content-Type: application/json');
require_once '../connect.php'; // 你的 PDO 和 CORS 设置

try {
    $stmt = $pdo->query("SELECT * FROM coach_list ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "读取学生资料失败: " . $e->getMessage(),
    ]);
}
