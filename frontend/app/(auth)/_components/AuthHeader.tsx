import Link from "next/link";
import { NavIcon, icons } from "../../dashboard/_components/icons";

/* -------------------------------------------------------------------------
 * AuthHeader — brand mark on the left, quick links on the right. "Pusat
 * Bantuan" and the divider drop out on mobile so the bar never overflows.
 * ---------------------------------------------------------------------- */
export function AuthHeader() {
  return (
    <header className="w-full border-b border-line/60 bg-card/70 backdrop-blur-[6px]">
      <div className="mx-auto flex w-full max-w-[1280px] items-center justify-between gap-4 px-4 py-3.5 sm:px-8">
        <Link href="/" className="flex items-center gap-3.5">
          <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)]">
            <NavIcon size={22}>{icons.book}</NavIcon>
          </span>
          <span className="text-[18px] leading-[18px] font-bold tracking-[-0.45px] text-brand">Tahfidz System</span>
        </Link>

        <nav aria-label="Tautan cepat" className="flex items-center text-[14px] leading-5 font-medium text-muted">
          <a href="#" className="hidden items-center gap-1.5 hover:text-ink sm:inline-flex">
            <NavIcon size={16}>{icons.help}</NavIcon>
            Pusat Bantuan
          </a>
          <span aria-hidden="true" className="mx-6 hidden h-4 w-px bg-line sm:block" />
          <Link href="/" className="hover:text-ink">
            Kembali ke Landing Page
          </Link>
        </nav>
      </div>
    </header>
  );
}
