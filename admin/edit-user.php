<?php
header('Content-Type: application/json');
require_once '../connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? '';
$role = $input['role'] ?? '';

if ($id == "" || $role == "") {
    echo json_encode(["success" => false, "message" => "缺少参数"]);
    exit;
}

$tableName = $role . "_list";

$phone = $input['phone'] ?? '';
$name = $input['name'] ?? '';
$birthday = $input['birthday'] ?? '';

if (!$name || !$birthday || !$phone) {
    echo json_encode(["success" => false, "message" => "名字、生日和电话不能为空"]);
    exit;
}

try {
    if ($id != -1) {
        // ✅ 更新前检查电话号码是否冲突（排除自己）
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_list WHERE phone = :phone AND id != :id");
        $stmt->execute([':phone' => $phone, ':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "该电话号码已被使用"]);
            exit;
        }

        $updateFields = "name = :name, birthday = :birthday, phone = :phone";
        $params = [
            ":name" => $name,
            ":birthday" => $birthday,
            ":phone" => $phone,
            ":id" => $id
        ];

        $stmt = $pdo->prepare("UPDATE $tableName SET $updateFields WHERE id = :id");
        $stmt->execute($params);

        echo json_encode(["success" => true, "message" => "更新用户资料成功"]);
    } else {
        // ✅ 新增前检查电话号码是否重复
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_list WHERE phone = :phone");
        $stmt->execute([':phone' => $phone]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "该电话号码已存在"]);
            exit;
        }

        $password = password_hash(substr($phone, -4) . date('Y', strtotime($birthday)), PASSWORD_DEFAULT);

        $pdo->beginTransaction();

        // 插入 user_list 表
        $stmt1 = $pdo->prepare("INSERT INTO user_list (phone, password, role) VALUES (:phone, :password, :role)");
        $stmt1->execute([
            ':phone'    => $phone,
            ':password' => $password,
            ':role'     => $role
        ]);

        // 插入 role 对应表
        $stmt2 = $pdo->prepare("INSERT INTO $tableName (phone, name, birthday) 
                                VALUES (:phone, :name, :birthday)");
        $stmt2->execute([
            ':phone'    => $phone,
            ':name'     => $name,
            ':birthday' => $birthday,
        ]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "新用户添加成功",
        ]);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "操作失败: " . $e->getMessage()]);
}
