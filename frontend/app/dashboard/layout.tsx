import { DashboardShell } from "./_components/DashboardShell";
import { SessionProvider } from "./_components/session";

export default function DashboardLayout({ children }: LayoutProps<"/dashboard">) {
  return (
    <SessionProvider>
      <DashboardShell>{children}</DashboardShell>
    </SessionProvider>
  );
}
