<?php
header('Content-Type: application/json');
require_once '../connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? '';

if ($action === 'edit') {
    $phone = $input['phone'] ?? '';
    $name = $input['name'] ?? '';
    $birthday = $input['birthday'] ?? '';
    $profilePicPath = null;

    if (!$name || !$birthday) {
        echo json_encode(["success" => false, "message" => "名字和生日不能为空"]);
        exit;
    }

    try {
        $updateFields = "name = :name, birthday = :birthday";
        $params = [
            ":name" => $name,
            ":birthday" => $birthday,
            ":phone" => $phone,
        ];

        // 处理头像上传（可选）
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = basename($_FILES['profile_pic']['name']);
            $targetPath = $uploadDir . time() . '_' . $filename;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
                $profilePicPath = $targetPath;
                $updateFields .= ", profile_pic = :profile_pic";
                $params[':profile_pic'] = $profilePicPath;
            }
        }

        $stmt = $pdo->prepare("UPDATE student_list SET $updateFields WHERE phone = :phone");
        $stmt->execute($params);

        echo json_encode(["success" => true, "message" => "资料更新成功"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "更新失败: " . $e->getMessage()]);
    }
} elseif ($action === 'change_password') {
    $phone = $input['phone'] ?? '';
    $passwordOld = $input['password_old'] ?? '';
    $passwordNew = $input['password_new'] ?? '';

    if (!$passwordOld || !$passwordNew) {
        echo json_encode(["success" => false, "message" => "请输入原密码和新密码"]);
        exit;
    }

    try {
        // 先查当前密码
        $stmt = $pdo->prepare("SELECT password FROM user_list WHERE phone = :phone LIMIT 1");
        $stmt->execute([':phone' => $phone]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($passwordOld, $user['password'])) {
            echo json_encode(["success" => false, "message" => "原密码错误"]);
            exit;
        }

        // 更新密码
        $hashedNew = password_hash($passwordNew, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE user_list SET password = :password WHERE phone = :phone");
        $stmt->execute([':password' => $hashedNew, ':phone' => $phone]);

        echo json_encode(["success" => true, "message" => "密码已更新"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "密码更新失败: " . $e->getMessage()]);
    }
} else if ($action === 'new') {
    $phone = $input['phone'] ?? '';
    $name = $input['name'] ?? '';
    $birthday = $input['birthday'] ?? '';

    // 接收 POST 数据
    $password = password_hash(substr($phone, -4) . date('Y', strtotime($birthday)), PASSWORD_DEFAULT);;
    $role = "coach";
    $profilePicPath = null;

    try {
        // 开始事务
        $pdo->beginTransaction();

        // 1️⃣ 插入 auth 表
        $stmt1 = $pdo->prepare("INSERT INTO user_list (phone, password, role) VALUES (:phone, :password, :role)");
        $stmt1->execute([
            ':phone'    => $phone,
            ':password' => $password,
            ':role'     => $role
        ]);

        // 2️⃣ 插入 members 表
        $stmt2 = $pdo->prepare("INSERT INTO coach_list (phone, name, birthday, profile_pic) 
                                VALUES (:phone, :name, :birthday, :profile_pic)");
        $stmt2->execute([
            ':phone'       => $phone,
            ':name'        => $name,
            ':birthday'    => $birthday,
            ':profile_pic' => $profilePicPath
        ]);

        // 提交事务
        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "新教练添加成功",
        ]);
    } catch (PDOException $e) {
        // 回滚事务
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "新教练添加失败: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "未知操作"]);
}
