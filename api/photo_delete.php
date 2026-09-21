<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$photoId = (string)($input['photo_id'] ?? '');

if ($photoId === '') {
    json_response(['erro' => 'Dados inválidos.'], 400);
}

try {
    delete_photo($user['id'], $photoId);
} catch (RuntimeException $e) {
    json_response(['erro' => $e->getMessage()], 404);
}

json_response(['ok' => true]);
