<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$password = (string)($input['password'] ?? '');

if (!password_verify($password, $user['password_hash'])) {
    json_response(['erro' => 'Senha incorreta.'], 403);
}

$stmt = db()->prepare('SELECT file_path FROM photos WHERE user_id = ?');
$stmt->execute([$user['id']]);
$filePaths = array_column($stmt->fetchAll(), 'file_path');

$stmt = db()->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$user['id']]);

foreach ($filePaths as $filePath) {
    $abs = __DIR__ . '/../' . $filePath;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

logout_user();
json_response(['ok' => true]);
