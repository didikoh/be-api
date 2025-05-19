<?php
header('Content-Type: application/json');
require_once '../connect.php'; // 数据库连接

$input = json_decode(file_get_contents("php://input"), true);
$courseId = $input['course_id'] ?? null;

if (!$courseId) {
    echo json_encode(["success" => false, "message" => "缺少 course_id"]);
    exit;
}

try {
    // 获取课程详情
    $stmtCourse = $pdo->prepare("SELECT * FROM course_list WHERE id = :course_id LIMIT 1");
    $stmtCourse->execute([':course_id' => $courseId]);
    $course = $stmtCourse->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode(["success" => false, "message" => "课程不存在"]);
        exit;
    }

    // 获取该课程所有预约记录
    $stmtBooking = $pdo->prepare("
        SELECT b.*, s.name AS student_name, s.phone AS student_phone
        FROM course_booking b
        JOIN student_list s ON b.student_id = s.id
        WHERE b.course_id = :course_id
        ORDER BY b.booking_time DESC
    ");
    $stmtBooking->execute([':course_id' => $courseId]);
    $bookings = $stmtBooking->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "course" => $course,
        "bookings" => $bookings
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
