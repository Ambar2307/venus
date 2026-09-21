"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function SignupPage() {
  const router = useRouter();
  const [erro, setErro] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setErro(null);
    setLoading(true);

    const form = new FormData(e.currentTarget);
    const body = {
      email: form.get("email"),
      password: form.get("password"),
      birthDate: form.get("birthDate"),
      displayName: form.get("displayName"),
      type: form.get("type"),
      interest: form.get("interest"),
      description: form.get("description"),
    };

    const res = await fetch("/api/signup", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    setLoading(false);

    if (!res.ok) {
      const data = await res.json();
      setErro(data.erro ?? "Erro ao criar perfil.");
      return;
    }
    router.push("/login");
  }

  return (
    <main className="max-w-md mx-auto px-4 py-10">
      <h1 className="font-serif text-2xl mb-6">Criar perfil</h1>
      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        <input
          name="displayName"
          placeholder="Nome fictício"
          required
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        />
        <select
          name="type"
          required
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        >
          <option value="COUPLE">Casal</option>
          <option value="SINGLE_WOMAN">Mulher solteira</option>
          <option value="SINGLE_MAN">Homem solteiro</option>
        </select>
        <input
          name="interest"
          placeholder="Interesse (ex.: casais e mulheres)"
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        />
        <textarea
          name="description"
          placeholder="Descrição"
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm min-h-[80px]"
        />
        <input
          name="birthDate"
          type="date"
          required
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        />
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
          minLength={8}
          className="bg-surface2 border border-white/10 rounded-md px-3 py-2 text-sm"
        />

        {erro && <p className="text-sm text-wine">{erro}</p>}

        <button
          disabled={loading}
          className="bg-gold text-[#1a1410] font-semibold rounded-md py-2.5 text-sm disabled:opacity-50"
        >
          {loading ? "Criando..." : "Criar perfil com Acesso Livre"}
        </button>
      </form>
    </main>
  );
}
