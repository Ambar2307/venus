import { db } from "./db";

/** True se existe pedido de amizade aceito em qualquer direção entre os dois usuários. */
export async function saoAmigos(userAId: string, userBId: string) {
  if (userAId === userBId) return true;

  const amizade = await db.friendRequest.findFirst({
    where: {
      status: "ACCEPTED",
      OR: [
        { fromId: userAId, toId: userBId },
        { fromId: userBId, toId: userAId },
      ],
    },
  });
  return !!amizade;
}

/**
 * Decide se `viewerId` pode ver a URL real de uma foto.
 * Fotos públicas: sempre visíveis.
 * Fotos "amigos": só para o dono ou amigos aceitos.
 * Isso deve ser chamado sempre no backend, nunca confiar em checagem feita no cliente.
 */
export async function podeVerFoto(
  viewerId: string | null,
  foto: { userId: string; visibility: "PUBLIC" | "FRIENDS" }
) {
  if (foto.visibility === "PUBLIC") return true;
  if (!viewerId) return false;
  if (viewerId === foto.userId) return true;
  return saoAmigos(viewerId, foto.userId);
}
