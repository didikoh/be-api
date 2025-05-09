<?php
header('Content-Type: application/json');
require_once '../connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? '';
$role = $input['role'] ?? '';

if($id == ""||$role == ""){
    echo json_encode(["success" => false, "message" => "缺少参数"]);
    exit;
}

$tableName = $role . "_list";

if ($id != -1) {
    $phone = $input['phone'] ?? '';
    $name = $input['name'] ?? '';
    $birthday = $input['birthday'] ?? '';

    if (!$name || !$birthday) {
        echo json_encode(["success" => false, "message" => "名字和生日不能为空"]);
        exit;
    }

    try {
        $updateFields = "name = :name, birthday = :birthday,phone = :phone";
        $params = [
            ":name" => $name,
            ":birthday" => $birthday,
            ":phone" => $phone,
            ":id" => $id
        ];

        $stmt = $pdo->prepare("UPDATE $tableName SET $updateFields WHERE id = :id");
        $stmt->execute($params);

        echo json_encode(["success" => true, "message" => "更新用户资料成功"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "更新失败: " . $e->getMessage()]);
    }
} else {
    $phone = $input['phone'] ?? '';
    $name = $input['name'] ?? '';
    $birthday = $input['birthday'] ?? '';

    // 接收 POST 数据
    $password = password_hash(substr($phone, -4) . date('Y', strtotime($birthday)), PASSWORD_DEFAULT);;

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
        $stmt2 = $pdo->prepare("INSERT INTO $tableName (phone, name, birthday) 
                                VALUES (:phone, :name, :birthday)");
        $stmt2->execute([
            ':phone'       => $phone,
            ':name'        => $name,
            ':birthday'    => $birthday,
        ]);

        // 提交事务
        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "新用户添加成功",
        ]);
    } catch (PDOException $e) {
        // 回滚事务
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "新用户添加失败: " . $e->getMessage()]);
    }
}
