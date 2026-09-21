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
    json_response(['erro' => 'photo_id é obrigatório.'], 400);
}

$stmt = db()->prepare('SELECT id FROM likes WHERE user_id = ? AND photo_id = ?');
$stmt->execute([$user['id'], $photoId]);
if ($stmt->fetch()) {
    json_response(['erro' => 'Você já curtiu esta foto.'], 409);
}

$checagem = pode_curtir($user['id'], $user['plan']);
if (!$checagem['permitido']) {
    json_response(['erro' => $checagem['motivo'], 'upgrade' => true], 403);
}

$stmt = db()->prepare('INSERT INTO likes (id, user_id, photo_id) VALUES (?, ?, ?)');
$stmt->execute([gen_uuid(), $user['id'], $photoId]);
registrar_curtida($user['id']);

json_response(['ok' => true]);
