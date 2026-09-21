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
