<?php
require_once __DIR__ . '/app/init.php';

$updates = [
    'radmin@gmail.com'   => 'Admin123',
    'manager@gmail.com'  => 'manager01',
    'employee@gmail.com' => 'ram24',
];

foreach ($updates as $email => $plain) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $updates->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
    echo "Updated $email<br>";
}