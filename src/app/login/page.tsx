"use client";

import { signIn } from "next-auth/react";
import { useState } from "react";

export default function LoginPage() {
  const [erro, setErro] = useState<string | null>(null);

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setErro(null);
    const form = new FormData(e.currentTarget);

    const res = await signIn("credentials", {
      email: form.get("email"),
      password: form.get("password"),
      redirect: false,
    });

    if (res?.error) {
      setErro("E-mail ou senha inválidos.");
      return;
    }
    window.location.href = "/";
  }

  return (
    <main className="max-w-sm mx-auto px-4 py-10">
      <h1 className="font-serif text-2xl mb-6">Entrar</h1>
      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        <input
          name="email"
          type="email"
          placeholder="E-mail"
          required
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        />
        <input
          name="password"
          type="password"
          placeholder="Senha"
          required
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        />
        {erro && <p className="text-sm text-wine">{erro}</p>}
        <button className="bg-gold text-[#1a1410] font-semibold rounded-md py-2.5 text-sm">
          Entrar
        </button>
      </form>
    </main>
  );
}
