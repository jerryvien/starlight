<?php
// Hash the password 'admin123' using bcrypt
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
echo $hashed_password;
?>