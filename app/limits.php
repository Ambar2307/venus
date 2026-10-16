<?php

const LIMITE_DIARIO = 3;

function hoje_sql(): string
{
    return gmdate('Y-m-d');
}

function pode_curtir(string $userId, string $plano): array
{
    if ($plano === 'EXCLUSIVE') {
        return ['permitido' => true];
    }

    $stmt = db()->prepare('SELECT likes_count FROM daily_usage WHERE user_id = ? AND usage_date = ?');
    $stmt->execute([$userId, hoje_sql()]);
    $uso = $stmt->fetch();

    if ($uso && (int)$uso['likes_count'] >= LIMITE_DIARIO) {
        return [
            'permitido' => false,
            'motivo' => sprintf('Limite de %d curtidas por dia no Acesso Livre.', LIMITE_DIARIO),
        ];
    }
    return ['permitido' => true];
}

function registrar_curtida(string $userId): void
{
    $stmt = db()->prepare(
        'INSERT INTO daily_usage (user_id, usage_date, likes_count, comments_count)
         VALUES (?, ?, 1, 0)
         ON DUPLICATE KEY UPDATE likes_count = likes_count + 1'
    );
    $stmt->execute([$userId, hoje_sql()]);
}

function pode_comentar(string $userId, string $plano): array
{
    if ($plano === 'EXCLUSIVE') {
        return ['permitido' => true];
    }

    $stmt = db()->prepare('SELECT comments_count FROM daily_usage WHERE user_id = ? AND usage_date = ?');
    $stmt->execute([$userId, hoje_sql()]);
    $uso = $stmt->fetch();

    if ($uso && (int)$uso['comments_count'] >= LIMITE_DIARIO) {
        return [
            'permitido' => false,
            'motivo' => sprintf('Limite de %d comentários por dia no Acesso Livre.', LIMITE_DIARIO),
        ];
    }
    return ['permitido' => true];
}

function registrar_comentario(string $userId): void
{
    $stmt = db()->prepare(
        'INSERT INTO daily_usage (user_id, usage_date, likes_count, comments_count)
         VALUES (?, ?, 0, 1)
         ON DUPLICATE KEY UPDATE comments_count = comments_count + 1'
    );
    $stmt->execute([$userId, hoje_sql()]);
}

/**
 * Quantas mensagens grátis alguém do plano Livre pode mandar numa conversa
 * que um assinante Exclusivo começou com ela. Depois disso, só assinando
 * pra continuar.
 */
const CHAT_FREE_MESSAGE_LIMIT = 6;

function pode_mandar_mensagem_gratis(string $conversationId, string $userId): array
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_id = ?');
    $stmt->execute([$conversationId, $userId]);
    $enviadas = (int)$stmt->fetchColumn();

    if ($enviadas >= CHAT_FREE_MESSAGE_LIMIT) {
        return [
            'permitido' => false,
            'motivo' => sprintf(
                'Você já usou suas %d mensagens grátis nessa conversa. Assine o plano Exclusivo pra continuar.',
                CHAT_FREE_MESSAGE_LIMIT
            ),
        ];
    }
    return ['permitido' => true];
}
