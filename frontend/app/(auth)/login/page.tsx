import type { Metadata } from "next";
import { LoginForm } from "./_components/LoginForm";

export const metadata: Metadata = {
  title: "Masuk | Tahfidz System",
};

export default function LoginPage() {
  return (
    <section
      aria-labelledby="login-title"
      className="flex w-full max-w-[480px] flex-col gap-5 rounded-3xl border border-line bg-card/95 p-6 shadow-[0px_10px_30px_-5px_rgba(0,0,0,0.05),0px_20px_25px_-5px_rgba(0,53,39,0.03)] backdrop-blur-[2px] sm:p-[37px]"
    >
      <div className="flex flex-col items-center gap-1.5 text-center">
        <h1 id="login-title" className="pt-2 text-[24px] leading-8 font-bold tracking-[-0.6px] text-ink">
          Selamat Datang Kembali
        </h1>
        <p className="max-w-[384px] text-[14px] leading-5 text-muted">
          Masuk ke portal akademik untuk mengelola data hafalan, presensi, dan penilaian santri.
        </p>
      </div>

      <LoginForm />

      <p className="text-right text-[11px] leading-[16.5px] text-muted">
        <a href="#" className="underline decoration-dotted underline-offset-2 hover:text-ink">
          Butuh Bantuan? Hubungi Super Admin
        </a>
      </p>
    </section>
  );
}
