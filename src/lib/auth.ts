import CredentialsProvider from "next-auth/providers/credentials";
import type { NextAuthOptions } from "next-auth";
import bcrypt from "bcryptjs";
import { db } from "./db";

export const authOptions: NextAuthOptions = {
  session: { strategy: "jwt" },
  pages: {
    signIn: "/login",
  },
  providers: [
    CredentialsProvider({
      name: "Email e senha",
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Senha", type: "password" },
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) return null;

        const user = await db.user.findUnique({
          where: { email: credentials.email },
          include: { profile: true },
        });
        if (!user) return null;
        if (user.status !== "active") return null;

        const senhaValida = await bcrypt.compare(
          credentials.password,
          user.passwordHash
        );
        if (!senhaValida) return null;

        return {
          id: user.id,
          email: user.email,
          name: user.profile?.displayName ?? "Sem nome",
          plan: user.plan,
        } as any;
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.id = (user as any).id;
        token.plan = (user as any).plan;
      }
      return token;
    },
    async session({ session, token }) {
      if (session.user) {
        (session.user as any).id = token.id;
        (session.user as any).plan = token.plan;
      }
      return session;
    },
  },
};

/** Verdadeiro se o usuário está no plano Exclusivo e a assinatura ainda é válida. */
export function isExclusive(user: {
  plan: string;
  planValidUntil: Date | null;
}) {
  if (user.plan !== "EXCLUSIVE") return false;
  if (!user.planValidUntil) return false;
  return user.planValidUntil.getTime() > Date.now();
}
