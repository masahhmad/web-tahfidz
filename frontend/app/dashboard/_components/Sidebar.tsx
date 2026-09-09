"use client";

import { useEffect, useRef } from "react";
import { NavIcon, icons } from "./icons";
import { NAV_ITEMS, FOOTER_ITEMS, type NavItem } from "./nav-data";

/* -------------------------------------------------------------------------
 * SidebarItem — one nav row, active/inactive states from Figma
 * ("Link - Dashboard (Inactive/Active)" variants).
 * ---------------------------------------------------------------------- */
function SidebarItem({
  item,
  isActive,
  onClick,
}: {
  item: NavItem;
  isActive: boolean;
  onClick?: () => void;
}) {
  return (
    <a
      href="#"
      onClick={onClick}
      aria-current={isActive ? "page" : undefined}
      className={`flex w-full items-center gap-3 rounded-xl px-4 py-3 text-[14px] leading-[20px] transition-colors ${
        isActive
          ? "bg-[#6cf8bb] text-[#00714d]"
          : "text-[#404944] hover:bg-[#f8f9fa]"
      }`}
    >
      <NavIcon size={15}>{icons[item.icon]}</NavIcon>
      {item.label}
    </a>
  );
}

/* -------------------------------------------------------------------------
 * SidebarContent — the nav list shared verbatim by the static desktop
 * sidebar and the mobile off-canvas drawer.
 * ---------------------------------------------------------------------- */
function SidebarContent({
  activeId,
  onNavigate,
  onClose,
}: {
  activeId: string;
  onNavigate?: () => void;
  onClose?: () => void;
}) {
  return (
    <div className="flex h-full w-[260px] flex-col gap-3 overflow-y-auto bg-white px-4 pt-8 pb-3 shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      {/* Header / Logo Area */}
      <div className="flex w-full flex-col items-start pb-8">
        <div className="flex w-full items-center gap-4 px-2">
          <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#064e3b] text-white">
            <NavIcon size={20} className="text-white">
              {icons.dashboard}
            </NavIcon>
          </div>
          <div className="flex flex-1 flex-col items-start">
            <h1 className="text-[20px] leading-[28px] font-bold tracking-[-0.5px] text-[#003527]">
              Tahfidz System
            </h1>
            <p className="text-[12px] leading-[16px] font-semibold tracking-[0.6px] text-[#404944]">
              Management Portal
            </p>
          </div>
          {onClose && (
            <button
              type="button"
              onClick={onClose}
              aria-label="Close menu"
              className="flex size-9 shrink-0 items-center justify-center rounded-full text-[#191c1d] hover:bg-[#edeeef] lg:hidden"
            >
              <NavIcon size={18}>{icons.close}</NavIcon>
            </button>
          )}
        </div>
      </div>

      {/* Navigation Links */}
      <ul className="flex w-full flex-1 flex-col gap-2">
        {NAV_ITEMS.map((item) => (
          <li key={item.id}>
            <SidebarItem item={item} isActive={item.id === activeId} onClick={onNavigate} />
          </li>
        ))}
      </ul>

      {/* Footer Links */}
      <div className="flex w-full flex-col gap-2 border-t border-[#e1e3e4] pt-[13px]">
        {FOOTER_ITEMS.map((item) => (
          <SidebarItem key={item.id} item={item} isActive={false} onClick={onNavigate} />
        ))}
      </div>
    </div>
  );
}

/* -------------------------------------------------------------------------
 * DesktopSidebar — statically visible at >= lg (1024px), hidden below it.
 * ---------------------------------------------------------------------- */
export function DesktopSidebar({ activeId }: { activeId: string }) {
  return (
    <aside className="hidden h-full shrink-0 lg:block" aria-label="Main navigation">
      <SidebarContent activeId={activeId} />
    </aside>
  );
}

/* -------------------------------------------------------------------------
 * MobileDrawer — off-canvas nav for < lg, with backdrop, close-on-click,
 * ESC-to-close and a simple focus trap.
 * ---------------------------------------------------------------------- */
export function MobileDrawer({
  isOpen,
  onClose,
  activeId,
}: {
  isOpen: boolean;
  onClose: () => void;
  activeId: string;
}) {
  const panelRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!isOpen) return;

    triggerRef.current = document.activeElement as HTMLElement;
    panelRef.current?.focus();
    document.body.style.overflow = "hidden";

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") {
        onClose();
        return;
      }
      if (event.key !== "Tab") return;

      const focusable = panelRef.current?.querySelectorAll<HTMLElement>(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (!focusable || focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.removeEventListener("keydown", handleKeyDown);
      document.body.style.overflow = "";
      triggerRef.current?.focus();
    };
  }, [isOpen, onClose]);

  return (
    <div className={`fixed inset-0 z-50 lg:hidden ${isOpen ? "" : "pointer-events-none"}`} aria-hidden={!isOpen}>
      {/* Backdrop overlay — click to close */}
      <div
        onClick={onClose}
        className={`absolute inset-0 bg-black/40 transition-opacity duration-300 ${
          isOpen ? "opacity-100" : "opacity-0"
        }`}
      />
      {/* Sliding panel */}
      <div
        ref={panelRef}
        id="mobile-nav-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="Main navigation"
        tabIndex={-1}
        className={`absolute inset-y-0 left-0 flex h-full max-w-[85vw] outline-none transition-transform duration-300 ease-out ${
          isOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <SidebarContent activeId={activeId} onNavigate={onClose} onClose={onClose} />
      </div>
    </div>
  );
}
