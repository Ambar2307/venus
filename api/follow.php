<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$targetId = (string)($input['user_id'] ?? '');

if ($targetId === '' || $targetId === $user['id']) {
    json_response(['erro' => 'Destino inválido.'], 400);
}

$stmt = db()->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$targetId]);
if (!$stmt->fetch()) {
    json_response(['erro' => 'Usuário não encontrado.'], 404);
}

$seguindo = toggle_follow($user['id'], $targetId);

json_response(['ok' => true, 'seguindo' => $seguindo]);
