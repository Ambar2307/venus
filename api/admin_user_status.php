<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$admin = require_admin_api();

$input = read_json_body();
$userId = (string)($input['user_id'] ?? '');
$status = (string)($input['status'] ?? '');

if ($userId === '' || !in_array($status, ['active', 'suspended'], true)) {
    json_response(['erro' => 'Dados inválidos.'], 400);
}
if ($userId === $admin['id']) {
    json_response(['erro' => 'Você não pode suspender a própria conta.'], 400);
}

$stmt = db()->prepare('UPDATE users SET status = ? WHERE id = ?');
$stmt->execute([$status, $userId]);

if ($stmt->rowCount() === 0) {
    json_response(['erro' => 'Usuário não encontrado.'], 404);
}

json_response(['ok' => true, 'status' => $status]);
