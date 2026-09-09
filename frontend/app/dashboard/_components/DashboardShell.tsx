"use client";

import { useState, type ReactNode } from "react";
import { usePathname } from "next/navigation";
import { DesktopSidebar, MobileDrawer } from "./Sidebar";
import { Header } from "./Header";

/* -------------------------------------------------------------------------
 * DashboardShell — owns the single piece of shared UI state (drawer
 * open/closed) and composes Desktop sidebar + Mobile drawer + Header
 * around the routed page content. Kept as the only client boundary in the
 * dashboard layout so page.tsx itself can stay a server component.
 *
 * activeId is derived from the URL (last path segment, "dashboard" at the
 * /dashboard index) so the highlighted nav link stays correct as more
 * /dashboard/* routes are added, without every page needing to pass it in.
 * ---------------------------------------------------------------------- */
export function DashboardShell({ children }: { children: ReactNode }) {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const pathname = usePathname();
  const activeId = pathname.split("/").filter(Boolean).pop() ?? "dashboard";

  return (
    <div className="flex h-dvh w-full items-start overflow-hidden bg-[#f8f9fa]">
      <DesktopSidebar activeId={activeId} />
      <MobileDrawer isOpen={isDrawerOpen} onClose={() => setIsDrawerOpen(false)} activeId={activeId} />

      <div className="flex h-full min-w-0 flex-1 flex-col items-center">
        <Header isDrawerOpen={isDrawerOpen} onToggle={() => setIsDrawerOpen((open) => !open)} />

        <main className="w-full flex-1 overflow-y-auto">{children}</main>
      </div>
    </div>
  );
}
