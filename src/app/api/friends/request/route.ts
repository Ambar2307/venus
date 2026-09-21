import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";

export async function POST(req: Request) {
  const session = await getServerSession(authOptions);
  const fromId = (session?.user as any)?.id;
  if (!fromId) {
    return NextResponse.json({ erro: "Não autenticado." }, { status: 401 });
  }

  const { toId } = await req.json();
  if (!toId || toId === fromId) {
    return NextResponse.json({ erro: "Destino inválido." }, { status: 400 });
  }

  const existente = await db.friendRequest.findUnique({
    where: { fromId_toId: { fromId, toId } },
  });
  if (existente) {
    return NextResponse.json({ erro: "Pedido já enviado." }, { status: 409 });
  }

  const pedido = await db.friendRequest.create({
    data: { fromId, toId, status: "PENDING" },
  });

  return NextResponse.json({ id: pedido.id });
}
