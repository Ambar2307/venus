import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";
import { podeComentar, registrarComentario } from "@/lib/limits";

export async function POST(
  req: Request,
  { params }: { params: { id: string } }
) {
  const session = await getServerSession(authOptions);
  const userId = (session?.user as any)?.id;
  const plano = (session?.user as any)?.plan ?? "FREE";
  if (!userId) {
    return NextResponse.json({ erro: "Não autenticado." }, { status: 401 });
  }

  const { text } = await req.json();
  if (!text || !text.trim()) {
    return NextResponse.json({ erro: "Comentário vazio." }, { status: 400 });
  }

  const checagem = await podeComentar(userId, plano);
  if (!checagem.permitido) {
    return NextResponse.json(
      { erro: checagem.motivo, upgrade: true },
      { status: 403 }
    );
  }

  const comentario = await db.comment.create({
    data: { userId, photoId: params.id, text: text.trim() },
  });
  await registrarComentario(userId);

  return NextResponse.json({ id: comentario.id });
}
