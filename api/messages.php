<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = require_login_api();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $withId = (string)($_GET['with'] ?? '');
    if ($withId === '') {
        json_response(['erro' => "Parâmetro 'with' é obrigatório."], 400);
    }

    [$partA, $partB] = $user['id'] < $withId ? [$user['id'], $withId] : [$withId, $user['id']];
    $stmt = db()->prepare('SELECT id FROM conversations WHERE part_a_id = ? AND part_b_id = ?');
    $stmt->execute([$partA, $partB]);
    $conversa = $stmt->fetch();

    if (!$conversa) {
        json_response(['mensagens' => []]);
    }

    $stmt = db()->prepare(
        'SELECT sender_id, body, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC'
    );
    $stmt->execute([$conversa['id']]);
    json_response(['mensagens' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_header();

    $input = read_json_body();
    $toId = (string)($input['to_id'] ?? '');
    $text = trim((string)($input['text'] ?? ''));

    if ($toId === '' || $text === '') {
        json_response(['erro' => 'Dados incompletos.'], 400);
    }
    if ($toId === $user['id']) {
        json_response(['erro' => 'Destino inválido.'], 400);
    }
    if (mb_strlen($text) > 1000) {
        json_response(['erro' => 'Mensagem muito longa.'], 400);
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$toId]);
    if (!$stmt->fetch()) {
        json_response(['erro' => 'Usuário não encontrado.'], 404);
    }

    [$partA, $partB] = $user['id'] < $toId ? [$user['id'], $toId] : [$toId, $user['id']];
    $stmt = db()->prepare('SELECT id FROM conversations WHERE part_a_id = ? AND part_b_id = ?');
    $stmt->execute([$partA, $partB]);
    $conversaExistente = $stmt->fetch();

    if (!is_exclusive($user)) {
        if (!$conversaExistente) {
            // Quem não assina não pode começar uma conversa nova — só responder
            // quando um assinante Exclusivo já tiver chamado primeiro.
            json_response([
                'erro' => 'Só assinantes do plano Exclusivo podem iniciar uma conversa nova.',
                'upgrade' => true,
            ], 403);
        }
        $checagem = pode_mandar_mensagem_gratis($conversaExistente['id'], $user['id']);
        if (!$checagem['permitido']) {
            json_response(['erro' => $checagem['motivo'], 'upgrade' => true], 403);
        }
    }

    $conversationId = $conversaExistente['id'] ?? get_or_create_conversation($user['id'], $toId);

    $stmt = db()->prepare(
        'INSERT INTO messages (id, conversation_id, sender_id, body) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([gen_uuid(), $conversationId, $user['id'], $text]);

    json_response(['ok' => true]);
}

json_response(['erro' => 'Método não permitido.'], 405);
