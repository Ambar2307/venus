import { NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";
import { isExclusive } from "@/lib/auth";

async function exigirExclusivo(userId: string) {
  const user = await db.user.findUnique({ where: { id: userId } });
  if (!user || !isExclusive(user)) {
    return false;
  }
  return true;
}

// GET /api/messages?with=<userId> — histórico da conversa com outro usuário
export async function GET(req: Request) {
  const session = await getServerSession(authOptions);
  const userId = (session?.user as any)?.id;
  if (!userId) {
    return NextResponse.json({ erro: "Não autenticado." }, { status: 401 });
  }
  if (!(await exigirExclusivo(userId))) {
    return NextResponse.json(
      { erro: "Chat interno é exclusivo do plano pago.", upgrade: true },
      { status: 403 }
    );
  }

  const { searchParams } = new URL(req.url);
  const withId = searchParams.get("with");
  if (!withId) {
    return NextResponse.json({ erro: "Parâmetro 'with' é obrigatório." }, { status: 400 });
  }

  const conversa = await db.conversation.findFirst({
    where: {
      OR: [
        { partAId: userId, partBId: withId },
        { partAId: withId, partBId: userId },
      ],
    },
    include: { messages: { orderBy: { sentAt: "asc" } } },
  });

  return NextResponse.json(conversa?.messages ?? []);
}

// POST /api/messages { toId, text } — envia mensagem, criando a conversa se não existir
export async function POST(req: Request) {
  const session = await getServerSession(authOptions);
  const userId = (session?.user as any)?.id;
  if (!userId) {
    return NextResponse.json({ erro: "Não autenticado." }, { status: 401 });
  }
  if (!(await exigirExclusivo(userId))) {
    return NextResponse.json(
      { erro: "Chat interno é exclusivo do plano pago.", upgrade: true },
      { status: 403 }
    );
  }

  const { toId, text } = await req.json();
  if (!toId || !text?.trim()) {
    return NextResponse.json({ erro: "Dados incompletos." }, { status: 400 });
  }

  let conversa = await db.conversation.findFirst({
    where: {
      OR: [
        { partAId: userId, partBId: toId },
        { partAId: toId, partBId: userId },
      ],
    },
  });

  if (!conversa) {
    conversa = await db.conversation.create({
      data: { partAId: userId, partBId: toId },
    });
  }

  const mensagem = await db.message.create({
    data: { conversationId: conversa.id, senderId: userId, text: text.trim() },
  });

  return NextResponse.json(mensagem);
}
