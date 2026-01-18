<?php
$new_password = 'test1234'; // Kullanmak istediğimiz yeni şifre
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
echo "Yeni Hash: " . $hashed_password;
?>