<?php
// ========== 解决 CORS 问题 ==========
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'https://yourdomain.com']; // 可根据需要添加正式站地址

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 预检请求直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ========== 数据库连接 ==========
if (!isset($pdo)) {
    $host = '151.106.124.1';
    $db   = 'u839013241_bestudio';
    $user = 'u839013241_beadmin';
    $pass = 'HxDb!20BeS@Xh785!';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 报错时抛出异常
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 默认取回关联数组
        PDO::ATTR_EMULATE_PREPARES   => false,                  // 禁用模拟预处理（更安全）
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        // 可选：连接成功提示
        // echo "✅ 数据库连接成功！";
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "数据库连接失败", "error" => $e->getMessage()]);
        exit;
    }
}
