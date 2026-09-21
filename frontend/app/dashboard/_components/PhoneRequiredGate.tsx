import Link from "next/link";

/* -------------------------------------------------------------------------
 * PhoneRequiredGate — replaces the main section of every dashboard page
 * (except Pengaturan) while an admin / super admin has not filled in their
 * phone number yet. Sidebar and header stay usable around it.
 * ---------------------------------------------------------------------- */
export function PhoneRequiredGate() {
  return (
    <div className="flex min-h-full w-full items-center justify-center bg-card p-6">
      <section aria-labelledby="phone-gate-title" className="flex max-w-lg flex-col items-center gap-6 text-center">
        <h2 id="phone-gate-title" className="text-[20px] leading-7 font-semibold text-ink">
          Nomer Telp belum diisi, edit pengguna dan isi terlebih dahulu nomer telp
        </h2>
        <Link
          href="/dashboard/pengaturan"
          className="rounded-lg bg-brand px-8 py-[10px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover"
        >
          Ke menu Pengaturan
        </Link>
      </section>
    </div>
  );
}
