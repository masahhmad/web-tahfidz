import type { ReactNode } from "react";
import { AuthHeader } from "./_components/AuthHeader";
import { AuthFooter } from "./_components/AuthFooter";

/* Shared shell for every auth page (login now, register/reset later): top
 * bar, subtle dotted page background, centered content slot, footer. */
export default function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex flex-1 flex-col bg-page bg-[radial-gradient(circle,var(--line)_1px,transparent_1px)] [background-size:24px_24px]">
      <AuthHeader />
      <main className="flex flex-1 items-center justify-center px-4 py-10 sm:px-6">{children}</main>
      <AuthFooter />
    </div>
  );
}
