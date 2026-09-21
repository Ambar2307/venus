import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";
import { podeVerFoto } from "@/lib/friends";

// GET /api/photos — feed. Fotos "amigos" aparecem sem a URL real pra quem não é amigo.
export async function GET() {
  const session = await getServerSession(authOptions);
  const viewerId = (session?.user as any)?.id ?? null;

  const fotos = await db.photo.findMany({
    where: { moderationStatus: "APPROVED" },
    orderBy: { createdAt: "desc" },
    take: 30,
    include: {
      user: { include: { profile: true } },
      likes: true,
      comments: { include: { user: { include: { profile: true } } } },
    },
  });

  const resultado = await Promise.all(
    fotos.map(async (foto) => {
      const visivel = await podeVerFoto(viewerId, foto);
      return {
        id: foto.id,
        autor: foto.user.profile?.displayName ?? "Perfil",
        tipo: foto.user.profile?.type,
        caption: foto.caption,
        visibility: foto.visibility,
        reservada: !visivel,
        url: visivel ? foto.url : null,
        totalLikes: foto.likes.length,
        curtidoPeloViewer: viewerId
          ? foto.likes.some((l) => l.userId === viewerId)
          : false,
        comentarios: foto.comments.map((c) => ({
          autor: c.user.profile?.displayName ?? "Perfil",
          texto: c.text,
        })),
      };
    })
  );

  return NextResponse.json(resultado);
}

// POST /api/photos — publicar uma foto nova (entra como PENDING até moderação aprovar).
export async function POST(req: Request) {
  const session = await getServerSession(authOptions);
  const userId = (session?.user as any)?.id;
  if (!userId) {
    return NextResponse.json({ erro: "Não autenticado." }, { status: 401 });
  }

  const { url, caption, visibility } = await req.json();
  if (!url) {
    return NextResponse.json({ erro: "URL da foto é obrigatória." }, { status: 400 });
  }

  // Só o plano Exclusivo pode marcar fotos como "somente amigos".
  const user = await db.user.findUnique({ where: { id: userId } });
  const visibilidadeFinal =
    visibility === "FRIENDS" && user?.plan === "EXCLUSIVE" ? "FRIENDS" : "PUBLIC";

  const foto = await db.photo.create({
    data: {
      userId,
      url,
      caption,
      visibility: visibilidadeFinal,
      moderationStatus: "PENDING",
    },
  });

  return NextResponse.json({ id: foto.id, status: foto.moderationStatus });
}
