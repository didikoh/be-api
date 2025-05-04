<?php
header('Content-Type: application/json');
require_once '../connect.php'; // 你的 PDO 和 CORS 设置

try {
    $stmt = $pdo->query("SELECT * FROM student_list ORDER BY id DESC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $students,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "读取学生资料失败: " . $e->getMessage(),
    ]);
}
