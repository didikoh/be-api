<?php
ini_set('session.gc_maxlifetime', 2592000);      // 后端保存 30 天
ini_set('session.cookie_lifetime', 2592000);     // 客户端 cookie 保存 30 天
session_start();
header('Content-Type: application/json');
require_once './connect.php';

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "loggedIn" => false,
        "message" => "用户未登录"
    ]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$role = $_SESSION['user']['role'];

try {
    $profileData = [];

    if ($role === 'student') {
        $stmt = $pdo->prepare("SELECT * FROM student_list WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $user_id]);
        $profileData = $stmt->fetch() ?: [];
    } elseif ($role === 'coach') {
        $stmt = $pdo->prepare("SELECT * FROM coach_list WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $user_id]);
        $profileData = $stmt->fetch() ?: [];
    } elseif ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admin_list WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $user_id]);
        $profileData = $stmt->fetch() ?: [];
    }

    echo json_encode([
        "loggedIn" => true,
        "message" => "获取用户资料成功",
        "profile" => array_merge($profileData, [
            "role" => $role,
        ]),
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "loggedIn" => false,
        "message" => "获取用户资料失败",
        "error" => $e->getMessage()
    ]);
}
