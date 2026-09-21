import { getServerSession } from "next-auth";
import { authOptions } from "@/lib/auth";
import { db } from "@/lib/db";
import { podeVerFoto } from "@/lib/friends";

const TIPO_LABEL: Record<string, string> = {
  COUPLE: "Casal",
  SINGLE_WOMAN: "Solteira",
  SINGLE_MAN: "Solteiro",
};

export default async function FeedPage() {
  const session = await getServerSession(authOptions);
  const viewerId = (session?.user as any)?.id ?? null;

  const fotos = await db.photo.findMany({
    where: { moderationStatus: "APPROVED" },
    orderBy: { createdAt: "desc" },
    take: 20,
    include: {
      user: { include: { profile: true } },
      likes: true,
      comments: true,
    },
  });

  return (
    <main className="max-w-xl mx-auto px-4 py-10">
      <h1 className="font-serif text-2xl mb-6">Feed</h1>

      {!session && (
        <div className="mb-6 rounded-xl border border-white/10 bg-surface px-4 py-3 text-sm text-muted">
          Você está vendo o feed como visitante.{" "}
          <a href="/login" className="text-gold underline">Entrar</a> ou{" "}
          <a href="/signup" className="text-gold underline">criar perfil</a> para curtir e comentar.
        </div>
      )}

      <div className="flex flex-col gap-6">
        {fotos.map(async (foto) => {
          const visivel = await podeVerFoto(viewerId, foto);
          const nome = foto.user.profile?.displayName ?? "Perfil";
          const tipo = foto.user.profile?.type
            ? TIPO_LABEL[foto.user.profile.type]
            : "";

          return (
            <article
              key={foto.id}
              className="rounded-2xl border border-white/10 bg-surface overflow-hidden"
            >
              <div className="flex items-center gap-3 px-4 py-3">
                <div className="w-9 h-9 rounded-full bg-gradient-to-br from-gold to-wine" />
                <div>
                  <div className="text-sm font-semibold">{nome}</div>
                  <div className="text-xs text-muted">{tipo}</div>
                </div>
              </div>

              <div className="mx-4 h-64 rounded-xl bg-gradient-to-br from-surface2 to-wine relative overflow-hidden">
                {!visivel && (
                  <div className="absolute inset-0 flex items-center justify-center text-xs text-white/80 bg-black/40 backdrop-blur-sm">
                    🔒 Reservada aos amigos
                  </div>
                )}
              </div>

              <div className="px-4 py-3">
                <p className="text-sm text-text/90 mb-2">{foto.caption}</p>
                <div className="text-xs text-muted">
                  {foto.likes.length} curtidas · {foto.comments.length} comentários
                </div>
              </div>
            </article>
          );
        })}

        {fotos.length === 0 && (
          <p className="text-sm text-muted">
            Nenhuma foto aprovada ainda. Publique a primeira em{" "}
            <code>POST /api/photos</code>.
          </p>
        )}
      </div>
    </main>
  );
}
