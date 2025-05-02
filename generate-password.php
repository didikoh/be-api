<?php
$password = 'xiaohann';  // 例如：admin12345
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "加密后的密码是: " . $hash;
