<?php

/** True se existe pedido de amizade aceito em qualquer direção entre os dois usuários. */
function sao_amigos(string $userAId, string $userBId): bool
{
    if ($userAId === $userBId) {
        return true;
    }

    $stmt = db()->prepare(
        "SELECT id FROM friend_requests
         WHERE status = 'ACCEPTED'
           AND ((from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?))
         LIMIT 1"
    );
    $stmt->execute([$userAId, $userBId, $userBId, $userAId]);
    return (bool)$stmt->fetch();
}

/** Lista os amigos aceitos de $userId (nome, tipo, id — para telas de perfil). */
function list_friends(string $userId): array
{
    $stmt = db()->prepare(
        "SELECT p.user_id AS id, p.display_name, p.type
         FROM friend_requests fr
         JOIN profiles p ON p.user_id = IF(fr.from_id = ?, fr.to_id, fr.from_id)
         WHERE fr.status = 'ACCEPTED' AND (fr.from_id = ? OR fr.to_id = ?)
         ORDER BY p.display_name ASC"
    );
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

/**
 * Decide se $viewerId pode ver o arquivo real de uma foto.
 * Fotos públicas: sempre visíveis. Fotos "amigos": só para o dono ou amigos aceitos.
 * Sempre chamado no backend — nunca confiar em checagem feita no cliente.
 */
function pode_ver_foto(?string $viewerId, array $foto): bool
{
    if ($foto['visibility'] === 'PUBLIC') {
        return true;
    }
    if (!$viewerId) {
        return false;
    }
    if ($viewerId === $foto['user_id']) {
        return true;
    }
    return sao_amigos($viewerId, $foto['user_id']);
}

/** Busca ou cria a conversa entre dois usuários, normalizando a ordem das partes. */
function get_or_create_conversation(string $userAId, string $userBId): string
{
    [$partA, $partB] = $userAId < $userBId ? [$userAId, $userBId] : [$userBId, $userAId];

    $stmt = db()->prepare('SELECT id FROM conversations WHERE part_a_id = ? AND part_b_id = ?');
    $stmt->execute([$partA, $partB]);
    $row = $stmt->fetch();
    if ($row) {
        return $row['id'];
    }

    $id = gen_uuid();
    $stmt = db()->prepare(
        'INSERT INTO conversations (id, part_a_id, part_b_id) VALUES (?, ?, ?)'
    );
    $stmt->execute([$id, $partA, $partB]);
    return $id;
}
