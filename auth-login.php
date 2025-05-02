<?php
ini_set('session.gc_maxlifetime', 2592000);      // 后端保存 30 天
ini_set('session.cookie_lifetime', 2592000);     // 客户端 cookie 保存 30 天
session_start(); // ✅ 启用 Session
header('Content-Type: application/json');
require_once 'connect.php'; // 包含 PDO 和 CORS 支持

// 获取输入数据
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($phone) || empty($password)) {
    echo json_encode(["success" => false, "message" => "手机号和密码不能为空"]);
    exit;
}

try {
    // 查询 auth 表中是否存在该手机号
    $stmt = $pdo->prepare("SELECT password, role FROM user_list WHERE phone = :phone LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "手机号未注册"]);
        exit;
    }

    // 验证密码
    if (password_verify($password, $user['password'])) {
        $role = $user['role'];
        $profileData = [];


        // 根据角色获取详细信息
        if ($role === 'student') {
            $stmt2 = $pdo->prepare("SELECT * FROM student_list WHERE phone = :phone LIMIT 1");
            $stmt2->execute([':phone' => $phone]);
            $profileData = $stmt2->fetch() ?: [];
        } elseif ($role === 'coach') {
            $stmt2 = $pdo->prepare("SELECT * FROM coach_list WHERE phone = :phone LIMIT 1");
            $stmt2->execute([':phone' => $phone]);
            $profileData = $stmt2->fetch() ?: [];
        } elseif ($role === 'admin') {
            $stmt2 = $pdo->prepare("SELECT * FROM admin_list WHERE phone = :phone LIMIT 1");
            $stmt2->execute([':phone' => $phone]);
            $profileData = $stmt2->fetch() ?: [];
        }

        // 写入 Session
        $_SESSION['user'] = [
            "phone" => $phone,
            "role" => $role,
            "login_time" => time()
        ];

        echo json_encode([
            "success" => true,
            "message" => "登录成功",
            "profile" => array_merge($profileData, [
                "role" => $role,
                "phone" => $phone,
            ]),
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "密码错误"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "数据库错误", "error" => $e->getMessage()]);
}
