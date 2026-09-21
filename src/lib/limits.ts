import { db } from "./db";

export const LIMITE_DIARIO = 3;

function inicioDoDiaUTC(): Date {
  const agora = new Date();
  return new Date(
    Date.UTC(agora.getUTCFullYear(), agora.getUTCMonth(), agora.getUTCDate())
  );
}

/**
 * Tenta registrar uma curtida respeitando o limite diário do plano Livre.
 * Usuários Exclusivos nunca são bloqueados aqui.
 * Retorna { permitido: boolean, motivo?: string }.
 */
export async function podeCurtir(userId: string, plano: string) {
  if (plano === "EXCLUSIVE") return { permitido: true };

  const hoje = inicioDoDiaUTC();
  const uso = await db.dailyUsage.findUnique({
    where: { userId_date: { userId, date: hoje } },
  });

  if (uso && uso.likesCount >= LIMITE_DIARIO) {
    return {
      permitido: false,
      motivo: `Limite de ${LIMITE_DIARIO} curtidas por dia no Acesso Livre.`,
    };
  }
  return { permitido: true };
}

export async function registrarCurtida(userId: string) {
  const hoje = inicioDoDiaUTC();
  await db.dailyUsage.upsert({
    where: { userId_date: { userId, date: hoje } },
    create: { userId, date: hoje, likesCount: 1, commentsCount: 0 },
    update: { likesCount: { increment: 1 } },
  });
}

export async function podeComentar(userId: string, plano: string) {
  if (plano === "EXCLUSIVE") return { permitido: true };

  const hoje = inicioDoDiaUTC();
  const uso = await db.dailyUsage.findUnique({
    where: { userId_date: { userId, date: hoje } },
  });

  if (uso && uso.commentsCount >= LIMITE_DIARIO) {
    return {
      permitido: false,
      motivo: `Limite de ${LIMITE_DIARIO} comentários por dia no Acesso Livre.`,
    };
  }
  return { permitido: true };
}

export async function registrarComentario(userId: string) {
  const hoje = inicioDoDiaUTC();
  await db.dailyUsage.upsert({
    where: { userId_date: { userId, date: hoje } },
    create: { userId, date: hoje, likesCount: 0, commentsCount: 1 },
    update: { commentsCount: { increment: 1 } },
  });
}
