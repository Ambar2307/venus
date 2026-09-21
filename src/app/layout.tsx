import "./globals.css";
import type { ReactNode } from "react";

export const metadata = {
  title: "Reserva",
  description: "Rede social de encontros para casais e solteiros",
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="pt-BR">
      <body>{children}</body>
    </html>
  );
}
