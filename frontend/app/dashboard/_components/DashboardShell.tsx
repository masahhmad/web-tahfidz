"use client";

import { useState, type ReactNode } from "react";
import { usePathname } from "next/navigation";
import { DesktopSidebar, MobileDrawer } from "./Sidebar";
import { Header } from "./Header";
import { PhoneRequiredGate } from "./PhoneRequiredGate";
import { AccessDenied } from "./AccessDenied";
import { NAV_ITEMS, canAccessNav } from "./nav-data";
import { useSession } from "./session";

const SETTINGS_PATH = "/dashboard/pengaturan";

/* -------------------------------------------------------------------------
 * DashboardShell — owns the single piece of shared UI state (drawer
 * open/closed) and composes Desktop sidebar + Mobile drawer + Header
 * around the routed page content. Kept as the only client boundary in the
 * dashboard layout so page.tsx itself can stay a server component.
 *
 * activeId is derived from the URL (last path segment, "dashboard" at the
 * /dashboard index) so the highlighted nav link stays correct as more
 * /dashboard/* routes are added, without every page needing to pass it in.
 *
 * The <main> content is also where two guards apply, so they cover every
 * dashboard page without touching each page.tsx: the phone-number gate
 * (everything except Pengaturan is replaced until the number is filled in)
 * and the role check (pages the current role may not open).
 * ---------------------------------------------------------------------- */
export function DashboardShell({ children }: { children: ReactNode }) {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const pathname = usePathname();
  const { user } = useSession();
  const activeId = pathname.split("/").filter(Boolean).pop() ?? "dashboard";

  const isSettingsPage = pathname === SETTINGS_PATH || pathname.startsWith(`${SETTINGS_PATH}/`);
  const isGated = user.perlu_lengkapi_telp && !isSettingsPage;
  const navItem = NAV_ITEMS.find((item) => item.id === activeId);
  const isForbidden = navItem !== undefined && !canAccessNav(navItem, user.role);

  return (
    <div className="flex h-dvh w-full items-start overflow-hidden bg-page">
      <DesktopSidebar activeId={activeId} />
      <MobileDrawer isOpen={isDrawerOpen} onClose={() => setIsDrawerOpen(false)} activeId={activeId} />

      <div className="flex h-full min-w-0 flex-1 flex-col items-center">
        <Header isDrawerOpen={isDrawerOpen} onToggle={() => setIsDrawerOpen((open) => !open)} />

        <main className="w-full flex-1 overflow-y-auto">
          {isGated ? <PhoneRequiredGate /> : isForbidden ? <AccessDenied /> : children}
        </main>
      </div>
    </div>
  );
}
