<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
require_admin_api();

$input = read_json_body();
$reportId = (string)($input['report_id'] ?? '');
$action = (string)($input['action'] ?? '');

if ($reportId === '' || !in_array($action, ['dismiss', 'remove_photo'], true)) {
    json_response(['erro' => 'Dados inválidos.'], 400);
}

$stmt = db()->prepare('SELECT * FROM reports WHERE id = ?');
$stmt->execute([$reportId]);
$report = $stmt->fetch();
if (!$report) {
    json_response(['erro' => 'Denúncia não encontrada.'], 404);
}

$pdo = db();
$pdo->beginTransaction();
try {
    if ($action === 'remove_photo') {
        $pdo->prepare("UPDATE photos SET moderation_status = 'REJECTED' WHERE id = ?")
            ->execute([$report['photo_id']]);
    }
    $pdo->prepare("UPDATE reports SET status = 'REVIEWED' WHERE id = ?")
        ->execute([$reportId]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['erro' => 'Não foi possível processar a denúncia.'], 500);
}

json_response(['ok' => true]);
