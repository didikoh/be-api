<?php
header('Content-Type: application/json');
require_once 'connect.php'; // 包含 PDO 初始化

$id = $_GET['id'] ?? null;

try {
    if ($id) {
        // 获取单个课程（用于编辑）
        $stmt = $pdo->prepare("SELECT * FROM course_list WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($course) {
            echo json_encode([
                'success' => true,
                'course' => $course
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '找不到该课程'
            ]);
        }
    } else {
        // 获取所有课程
        $stmt = $pdo->query("SELECT * FROM course_list WHERE state = 0 ORDER BY start_time DESC");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'courses' => $courses
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}
