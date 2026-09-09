import type { ReactNode } from "react";
import { NavIcon, icons } from "./icons";

/* -------------------------------------------------------------------------
 * HeaderToggle — hamburger / close button, only rendered below lg.
 * ---------------------------------------------------------------------- */
function HeaderToggle({ isOpen, onClick }: { isOpen: boolean; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-expanded={isOpen}
      aria-controls="mobile-nav-drawer"
      aria-label={isOpen ? "Close menu" : "Open menu"}
      className="flex size-10 shrink-0 items-center justify-center rounded-full text-[#191c1d] hover:bg-[#edeeef] lg:hidden"
    >
      <NavIcon size={20}>{isOpen ? icons.close : icons.menu}</NavIcon>
    </button>
  );
}

/* -------------------------------------------------------------------------
 * HeaderSearch / HeaderActionButton / HeaderProfile — small header pieces.
 * ---------------------------------------------------------------------- */
function HeaderSearch() {
  return (
    <div className="relative hidden w-full max-w-[256px] flex-col items-start sm:flex">
      <label htmlFor="dashboard-search" className="sr-only">
        Cari siswa
      </label>
      <input
        id="dashboard-search"
        type="search"
        placeholder="Cari siswa..."
        className="w-full rounded-full bg-[#edeeef] py-[9px] pr-4 pl-10 text-[14px] text-[#404944] placeholder:text-[#404944] focus:outline-2 focus:outline-[#00714d]"
      />
      <span className="absolute top-1/2 left-3 -translate-y-1/2 text-[#404944]">
        <NavIcon size={15}>{icons.search}</NavIcon>
      </span>
    </div>
  );
}

function HeaderActionButton({
  label,
  icon,
  className = "",
}: {
  label: string;
  icon: ReactNode;
  className?: string;
}) {
  return (
    <button
      type="button"
      aria-label={label}
      className={`flex items-center justify-center rounded-full p-2 text-[#191c1d] hover:bg-[#edeeef] ${className}`}
    >
      <NavIcon size={18}>{icon}</NavIcon>
    </button>
  );
}

function HeaderProfile() {
  return (
    <button
      type="button"
      aria-label="Open profile menu"
      className="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-white bg-[#6cf8bb] text-[12px] font-semibold text-[#00714d]"
    >
      TS
    </button>
  );
}

/* -------------------------------------------------------------------------
 * Header — top nav bar composed from the pieces above.
 * ---------------------------------------------------------------------- */
export function Header({ isDrawerOpen, onToggle }: { isDrawerOpen: boolean; onToggle: () => void }) {
  return (
    <header className="flex w-full shrink-0 items-center justify-between gap-4 bg-white/80 px-4 py-3 backdrop-blur-md sm:px-8">
      <HeaderToggle isOpen={isDrawerOpen} onClick={onToggle} />
      <div className="flex flex-1 items-center justify-end gap-6">
        <HeaderSearch />
        <div className="flex items-center gap-1">
          <HeaderActionButton label="Notifications" icon={icons.bell} />
          <HeaderActionButton label="Messages" icon={icons.mail} className="hidden sm:flex" />
          <HeaderActionButton label="Toggle theme" icon={icons.moon} className="hidden sm:flex" />
        </div>
        <HeaderProfile />
      </div>
    </header>
  );
}
