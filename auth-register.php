<?php
ini_set('session.gc_maxlifetime', 2592000);      // 后端保存 30 天
ini_set('session.cookie_lifetime', 2592000);     // 客户端 cookie 保存 30 天
session_start(); // ✅ 开启 Session
header('Content-Type: application/json');
require_once 'connect.php'; // 包含 PDO 和 CORS 设置

// 接收 POST 数据
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$password = $_POST['password'] ?? '';
$role = "student";
$profilePicPath = null;

// 密码加密
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 上传头像（可选）
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = basename($_FILES['profile_pic']['name']);
    $targetPath = $uploadDir . time() . '_' . $filename;

    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
        $profilePicPath = $targetPath;
    }
}

try {
    // 开始事务
    $pdo->beginTransaction();

    // 1️⃣ 插入 auth 表
    $stmt1 = $pdo->prepare("INSERT INTO user_list (phone, password, role) VALUES (:phone, :password, :role)");
    $stmt1->execute([
        ':phone'    => $phone,
        ':password' => $hashedPassword,
        ':role'     => $role
    ]);

    // 2️⃣ 插入 members 表
    $stmt2 = $pdo->prepare("INSERT INTO student_list (phone, name, birthday, profile_pic) 
                            VALUES (:phone, :name, :birthday, :profile_pic)");
    $stmt2->execute([
        ':phone'       => $phone,
        ':name'        => $name,
        ':birthday'    => $birthday,
        ':profile_pic' => $profilePicPath
    ]);

    // 提交事务
    $pdo->commit();

    // 3️⃣ 查询 student_list 获取完整资料
    $stmt3 = $pdo->prepare("SELECT * FROM student_list WHERE phone = :phone LIMIT 1");
    $stmt3->execute([':phone' => $phone]);
    $profileData = $stmt3->fetch() ?: [];

    // ✅ 写入 Session（等同登录成功）
    $_SESSION['user'] = [
        "phone" => $phone,
        "role" => $role,
        "login_time" => time()
    ];

    echo json_encode([
        "success" => true,
        "message" => "注册成功，已自动登录",
        "profile" => array_merge($profileData, [
            "role" => $role,
            "phone" => $phone,
        ]),
    ]);
} catch (PDOException $e) {
    // 回滚事务
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "注册失败: " . $e->getMessage()]);
}
