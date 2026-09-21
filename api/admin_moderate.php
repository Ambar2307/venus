<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
require_admin_api();

$input = read_json_body();
$photoId = (string)($input['photo_id'] ?? '');
$action = (string)($input['action'] ?? '');

if ($photoId === '' || !in_array($action, ['approve', 'reject'], true)) {
    json_response(['erro' => 'Dados inválidos.'], 400);
}

$novoStatus = $action === 'approve' ? 'APPROVED' : 'REJECTED';

$stmt = db()->prepare('UPDATE photos SET moderation_status = ? WHERE id = ?');
$stmt->execute([$novoStatus, $photoId]);

if ($stmt->rowCount() === 0) {
    json_response(['erro' => 'Foto não encontrada.'], 404);
}

json_response(['ok' => true, 'status' => $novoStatus]);
