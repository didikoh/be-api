<?php
ini_set('session.gc_maxlifetime', 2592000);      // 后端保存 30 天
ini_set('session.cookie_lifetime', 2592000);     // 客户端 cookie 保存 30 天
session_start(); // ✅ 开启 Session
require_once './connect.php'; // 包含 PDO 和 CORS 设置

// 接收 JSON 数据
$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? '';
$phone = $input['phone'] ?? '';
$birthday = $input['birthday'] ?? '';
$password = $input['password'] ?? '';
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

// ========== 检查手机号是否已注册 ==========
$stmtCheck = $pdo->prepare("SELECT 1 FROM user_list WHERE phone = :phone AND state != -1 LIMIT 1");
$stmtCheck->execute([':phone' => $phone]);
if ($stmtCheck->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Phone number already registered"
    ]);
    exit;
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

    $user_id = $pdo->lastInsertId();
    // 2️⃣ 插入 members 表
    $stmt2 = $pdo->prepare("INSERT INTO student_list (user_id,phone, name, birthday, profile_pic) 
                            VALUES (:user_id,:phone, :name, :birthday, :profile_pic)");
    $stmt2->execute([
        ':user_id'     => $user_id,
        ':phone'       => $phone,
        ':name'        => $name,
        ':birthday'    => $birthday,
        ':profile_pic' => $profilePicPath
    ]);

    // 提交事务
    $pdo->commit();


    // 3️⃣ 查询 student_list 获取完整资料
    $stmt3 = $pdo->prepare("SELECT * FROM student_list WHERE user_id = :user_id LIMIT 1");
    $stmt3->execute([':user_id' => $user_id]);
    $profileData = $stmt3->fetch() ?: [];

    // ✅ 写入 Session（等同登录成功）
    $_SESSION['user'] = [
        "user_id" => $user_id,
        "role" => $role,
        "login_time" => time()
    ];

    echo json_encode([
        "success" => true,
        "message" => "Registration successful",
        "profile" => array_merge($profileData, [
            "role" => $role,
        ]),
    ]);
} catch (PDOException $e) {
    // 回滚事务
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Register failed: " . $e->getMessage()]);
}
