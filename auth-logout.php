<?php
ini_set('session.gc_maxlifetime', 2592000);      // 后端保存 30 天
ini_set('session.cookie_lifetime', 2592000);     // 客户端 cookie 保存 30 天
session_start();
header('Content-Type: application/json');
// ========== 解决 CORS 问题 ==========
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'https://yourdomain.com']; // 可根据需要添加正式站地址

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 清除 session 数据
$_SESSION = [];
session_unset();
session_destroy();

// 可选：也清除 session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

echo json_encode([
    "success" => true,
    "message" => "已登出"
]);
