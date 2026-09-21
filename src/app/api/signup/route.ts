import { NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import { db } from "@/lib/db";

function idadeMinima18(dataNascimento: string) {
  const nascimento = new Date(dataNascimento);
  const hoje = new Date();
  const idade =
    hoje.getFullYear() -
    nascimento.getFullYear() -
    (hoje.getMonth() < nascimento.getMonth() ||
    (hoje.getMonth() === nascimento.getMonth() &&
      hoje.getDate() < nascimento.getDate())
      ? 1
      : 0);
  return idade >= 18;
}

export async function POST(req: Request) {
  const body = await req.json();
  const { email, password, birthDate, displayName, type, interest, description } =
    body ?? {};

  if (!email || !password || !birthDate || !displayName || !type) {
    return NextResponse.json(
      { erro: "Campos obrigatórios faltando." },
      { status: 400 }
    );
  }

  if (!idadeMinima18(birthDate)) {
    return NextResponse.json(
      { erro: "É necessário ter 18 anos ou mais para se cadastrar." },
      { status: 403 }
    );
  }

  const jaExiste = await db.user.findUnique({ where: { email } });
  if (jaExiste) {
    return NextResponse.json(
      { erro: "Já existe uma conta com este e-mail." },
      { status: 409 }
    );
  }

  const passwordHash = await bcrypt.hash(password, 10);

  const user = await db.user.create({
    data: {
      email,
      passwordHash,
      birthDate: new Date(birthDate),
      plan: "FREE",
      profile: {
        create: {
          displayName,
          type,
          interest: interest ?? "",
          description: description ?? "",
        },
      },
    },
    include: { profile: true },
  });

  return NextResponse.json({
    id: user.id,
    email: user.email,
    displayName: user.profile?.displayName,
  });
}
