<?php
require_once '../connect.php';
header('Content-Type: application/json');

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
    $pdo->beginTransaction();

    // 1. 取当前会员信息
    $stmt = $pdo->prepare("SELECT balance, phone, package AS current_package FROM student_list WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $member = $stmt->fetch();

    if (!$member) throw new Exception("找不到该会员");

    $newAmount = $member['balance'] + $amount;

    // ===== 新增 package 变更时检测预约 =====
    $isPackageChanged = ($package && $package !== $member['current_package']);
    if ($isPackageChanged) {
        // 检查预约表有没有未完成的预约
        $stmtCheck = $pdo->prepare(
            "SELECT COUNT(*) FROM course_booking WHERE student_id = :id AND status = 'booked'"
        );
        $stmtCheck->execute([':id' => $id]);
        $bookingCount = $stmtCheck->fetchColumn();
        if ($bookingCount > 0) {
            $pdo->rollBack(); // 退出前记得回滚
            echo json_encode([
                "success" => false,
                "message" => "请先取消所有预约"
            ]);
            exit;
        }
    }
    // ===== 检查结束 =====

    // 2. 更新student_list
    $stmt = $pdo->prepare("UPDATE student_list 
        SET balance = :amount, 
            package = :package, 
            active_date = CURDATE(), 
            expire_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
        WHERE id = :id");
    $stmt->execute([
        ':amount' => $newAmount,
        ':package' => $package,
        ':id' => $id
    ]);

    // 3. 如果套餐发生变化（含首次开通），记录 package 激活transaction
    if ($isPackageChanged) {
        // 查找套餐信息
        $stmtPkg = $pdo->prepare("SELECT value FROM package_list WHERE name = :name LIMIT 1");
        $stmtPkg->execute([':name' => $package]);
        $pkgRow = $stmtPkg->fetch();

        if (!$pkgRow) throw new Exception("找不到对应的课程包: $package");

        // 插入 transaction_list（type 可自定义，比如 activate_package）
        $stmt = $pdo->prepare("
            INSERT INTO transaction_list (member_id, phone, type, amount, package)
            VALUES (:member_id, :phone, 'activate_package', :amount, :package)
        ");
        $stmt->execute([
            ':member_id' => $id,
            ':phone'     => $member['phone'],
            ':amount'    => $pkgRow['value'], // amount 可填 0，或 pkgRow['value']，视业务需求
            ':package'   => $package,
        ]);
    }

    // 4. 充值记录
    if ($amount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO transaction_list (member_id, phone, type, amount)
            VALUES (:member_id, :phone, 'topup', :amount)
        ");
        $stmt->execute([
            ':member_id' => $id,
            ':phone'     => $member['phone'],
            ':amount'    => $amount
        ]);
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "操作成功"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "充值失败: " . $e->getMessage()]);
}
