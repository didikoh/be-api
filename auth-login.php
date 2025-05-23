<?php
ini_set('session.gc_maxlifetime', 2592000);      // 后端保存 30 天
ini_set('session.cookie_lifetime', 2592000);     // 客户端 cookie 保存 30 天
session_start(); // ✅ 启用 Session
require_once './connect.php'; // 包含 PDO 和 CORS 支持
// 获取输入数据
$input = json_decode(file_get_contents("php://input"), true);
$phone = $input['phone'] ?? '';
$password = $input['password'] ?? '';

if (empty($phone) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please provide phone number and password"]);
    exit;
}

try {
    // 查询 auth 表中是否存在该手机号
    $stmt = $pdo->prepare("SELECT * FROM user_list WHERE phone = :phone AND state != -1 LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Phone number not found"]);
        exit;
    }

    // 验证密码
    if (password_verify($password, $user['password'])) {
        $role = $user['role'];
        $profileData = [];
        $user_id = $user['id'];


        // 根据角色获取详细信息
        if ($role === 'student') {
            $stmt2 = $pdo->prepare("SELECT * FROM student_list WHERE user_id = :user_id LIMIT 1");
            $stmt2->execute([':user_id' => $user_id]);
            $profileData = $stmt2->fetch() ?: [];
        } elseif ($role === 'coach') {
            $stmt2 = $pdo->prepare("SELECT * FROM coach_list WHERE user_id = :user_id LIMIT 1");
            $stmt2->execute([':user_id' => $user_id]);
            $profileData = $stmt2->fetch() ?: [];
        } elseif ($role === 'admin') {
            $stmt2 = $pdo->prepare("SELECT * FROM admin_list WHERE user_id = :user_id LIMIT 1");
            $stmt2->execute([':user_id' => $user_id]);
            $profileData = $stmt2->fetch() ?: [];
        }

        // 写入 Session
        $_SESSION['user'] = [
            "user_id" => $user_id,
            "role" => $role,
            "login_time" => time()
        ];

        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "profile" => array_merge($profileData, [
                "role" => $role,
            ]),
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Password incorrect"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error", "error" => $e->getMessage()]);
}
