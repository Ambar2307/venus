import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";

export async function POST(req: Request) {
  const session = await getServerSession(authOptions);
  const userId = (session?.user as any)?.id;
  if (!userId) {
    return NextResponse.json({ erro: "Não autenticado." }, { status: 401 });
  }

  const { requestId, aceitar } = await req.json();

  const pedido = await db.friendRequest.findUnique({ where: { id: requestId } });
  if (!pedido || pedido.toId !== userId) {
    return NextResponse.json({ erro: "Pedido não encontrado." }, { status: 404 });
  }

  const atualizado = await db.friendRequest.update({
    where: { id: requestId },
    data: { status: aceitar ? "ACCEPTED" : "DECLINED" },
  });

  return NextResponse.json({ status: atualizado.status });
}
