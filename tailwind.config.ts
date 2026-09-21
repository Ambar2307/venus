import type { Config } from "tailwindcss";

const config: Config = {
  content: ["./src/**/*.{js,ts,jsx,tsx,mdx}"],
  theme: {
    extend: {
      colors: {
        bg: "#15121b",
        surface: "#1e1926",
        surface2: "#241d2e",
        gold: "#c9a15a",
        wine: "#8a3f52",
        text: "#ede6de",
        muted: "#9c90a8",
      },
    },
  },
  plugins: [],
};
export default config;
