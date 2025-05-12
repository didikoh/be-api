<?php
require_once '../connect.php'; // 连接数据库（根据你实际路径调整）
header('Content-Type: application/json');

// 获取 POST 数据
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$amount = intval($data['amount'] ?? 0);
$package = trim($data['package'] ?? '');

if (!$id) {
    echo json_encode(["success" => false, "message" => "无效的会员 ID"]);
    exit;
}

if ($amount < 0) {
    echo json_encode(["success" => false, "message" => "无效的充值金额"]);
    exit;
}

if (!$package) {
    echo json_encode(["success" => false, "message" => "无效的课程包"]);
    exit;
}

if ($package === 'none') {
    $package = null;
}

try {
    // 开始事务
    $pdo->beginTransaction();

    // 1. 获取当前 member 信息（用于累加金额与取得 phone）
    $stmt = $pdo->prepare("SELECT balance, phone FROM student_list WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $member = $stmt->fetch();

    if (!$member) {
        throw new Exception("找不到该会员");
    }

    $newAmount = $member['balance'] + $amount;

    // 2. 更新 student_list：累加金额 & 更新套餐
    $stmt = $pdo->prepare("UPDATE student_list 
    SET 
        balance = :amount, 
        package = :package, 
        active_date = CURDATE(), 
        expire_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
    WHERE id = :id");
    $stmt->execute([
        ':amount' => $newAmount,
        ':package' => $package,
        ':id' => $id
    ]);

    if ($amount > 0) {
        // 3. 插入 transaction_list
        $stmt = $pdo->prepare("
        INSERT INTO transaction_list (member_id, phone, type, amount)
        VALUES (:member_id, :phone, 'topup', :amount)
        ");
        $stmt->execute([
            ':member_id' => $id,
            ':phone' => $member['phone'],
            ':amount' => $amount
        ]);
    }

    // 提交事务
    $pdo->commit();

    echo json_encode(["success" => true, "message" => "充值成功"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "充值失败: " . $e->getMessage()]);
}
