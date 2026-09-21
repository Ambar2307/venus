<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$requestId = (string)($input['request_id'] ?? '');
$aceitar = (bool)($input['aceitar'] ?? false);

$stmt = db()->prepare('SELECT * FROM friend_requests WHERE id = ?');
$stmt->execute([$requestId]);
$pedido = $stmt->fetch();

if (!$pedido || $pedido['to_id'] !== $user['id']) {
    json_response(['erro' => 'Pedido não encontrado.'], 404);
}

$novoStatus = $aceitar ? 'ACCEPTED' : 'DECLINED';
$stmt = db()->prepare('UPDATE friend_requests SET status = ? WHERE id = ?');
$stmt->execute([$novoStatus, $requestId]);

json_response(['status' => $novoStatus]);
