import Link from "next/link";

/* Shown in place of a page the current role may not open (e.g. a guru
 * pengampu typing /dashboard/pengguna). The backend still enforces 403. */
export function AccessDenied() {
  return (
    <div className="flex min-h-full w-full items-center justify-center bg-card p-6">
      <section aria-labelledby="access-denied-title" className="flex max-w-lg flex-col items-center gap-6 text-center">
        <h2 id="access-denied-title" className="text-[20px] leading-7 font-semibold text-ink">
          Anda tidak punya akses ke halaman ini
        </h2>
        <Link
          href="/dashboard"
          className="rounded-lg bg-brand px-8 py-[10px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover"
        >
          Kembali ke Dashboard
        </Link>
      </section>
    </div>
  );
}
