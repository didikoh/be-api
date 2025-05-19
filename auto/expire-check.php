<?php
require_once '../connect.php';
$sql = "UPDATE student_list SET package = NULL WHERE package IS NOT NULL AND expire_date IS NOT NULL AND expire_date < CURDATE()";
$stmt = $pdo->prepare($sql);
$stmt->execute();
echo "OK";
