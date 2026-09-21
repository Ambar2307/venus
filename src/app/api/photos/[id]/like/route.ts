import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";
import { podeCurtir, registrarCurtida } from "@/lib/limits";

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

  const jaCurtiu = await db.like.findUnique({
    where: { userId_photoId: { userId, photoId: params.id } },
  });
  if (jaCurtiu) {
    return NextResponse.json({ erro: "Você já curtiu esta foto." }, { status: 409 });
  }

  const checagem = await podeCurtir(userId, plano);
  if (!checagem.permitido) {
    return NextResponse.json(
      { erro: checagem.motivo, upgrade: true },
      { status: 403 }
    );
  }

  await db.like.create({ data: { userId, photoId: params.id } });
  await registrarCurtida(userId);

  return NextResponse.json({ ok: true });
}
