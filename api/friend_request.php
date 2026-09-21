<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$toId = (string)($input['to_id'] ?? '');
if ($toId === '' || $toId === $user['id']) {
    json_response(['erro' => 'Destino inválido.'], 400);
}

$stmt = db()->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$toId]);
if (!$stmt->fetch()) {
    json_response(['erro' => 'Usuário não encontrado.'], 404);
}

$stmt = db()->prepare('SELECT id FROM friend_requests WHERE from_id = ? AND to_id = ?');
$stmt->execute([$user['id'], $toId]);
if ($stmt->fetch()) {
    json_response(['erro' => 'Pedido já enviado.'], 409);
}

$stmt = db()->prepare(
    'INSERT INTO friend_requests (id, from_id, to_id, status) VALUES (?, ?, ?, "PENDING")'
);
$stmt->execute([gen_uuid(), $user['id'], $toId]);

json_response(['ok' => true]);
