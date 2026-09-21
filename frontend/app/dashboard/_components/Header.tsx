import type { ReactNode } from "react";
import { NavIcon, icons } from "./icons";
import { ThemeToggle } from "./ThemeToggle";

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
      className="flex size-10 shrink-0 items-center justify-center rounded-full text-ink hover:bg-hover lg:hidden"
    >
      <NavIcon size={20}>{isOpen ? icons.close : icons.menu}</NavIcon>
    </button>
  );
}

/* -------------------------------------------------------------------------
 * HeaderActionButton / HeaderProfile — small header pieces.
 * ---------------------------------------------------------------------- */
// Only used by the commented-out Notifications/Messages buttons below; kept for when they return.
// eslint-disable-next-line @typescript-eslint/no-unused-vars
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
      className={`flex items-center justify-center rounded-full p-2 text-ink hover:bg-hover ${className}`}
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
      className="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-card-edge bg-mint text-[12px] font-semibold text-on-mint"
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
    <header className="flex w-full shrink-0 items-center justify-between gap-4 bg-card/80 px-4 py-3 backdrop-blur-md sm:px-8">
      <HeaderToggle isOpen={isDrawerOpen} onClick={onToggle} />
      <div className="flex flex-1 items-center justify-end gap-6">
        <div className="flex items-center gap-1">
          {/* Notifications and Messages are not planned yet; kept for when they are needed.
          <HeaderActionButton label="Notifications" icon={icons.bell} />
          <HeaderActionButton label="Messages" icon={icons.mail} className="hidden sm:flex" />
          */}
          <ThemeToggle />
        </div>
        <HeaderProfile />
      </div>
    </header>
  );
}
