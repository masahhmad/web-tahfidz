import type { IconName } from "./icons";

/* -------------------------------------------------------------------------
 * Nav data — extracted from the Figma "Navigation Links" / "Footer Links"
 * groups inside the SideNavBar component.
 * ---------------------------------------------------------------------- */
export type NavItem = { id: string; label: string; icon: IconName; href?: string };

export const NAV_ITEMS: NavItem[] = [
  { id: "dashboard", label: "Dashboard", icon: "dashboard", href: "/dashboard" },
  { id: "presensi-guru", label: "Presensi Guru", icon: "teacher", href: "/dashboard/presensi-guru" },
  { id: "presensi-siswa", label: "Presensi Siswa", icon: "student", href: "/dashboard/presensi-siswa" },
  { id: "setoran", label: "Setoran Siswa", icon: "deposit", href: "/dashboard/setoran" },
  { id: "kenaikan-juz", label: "Ujian Kenaikan Juz", icon: "exam", href: "/dashboard/kenaikan-juz" },
  { id: "data-siswa", label: "Data Siswa", icon: "dataStudent", href: "/dashboard/data-siswa" },
  { id: "halaqah", label: "Halaqah", icon: "halaqah", href: "/dashboard/halaqah" },
  { id: "target", label: "Target", icon: "target", href: "/dashboard/target" },
  { id: "pengguna", label: "Pengguna", icon: "users", href: "/dashboard/pengguna" },
  { id: "pengaturan", label: "Pengaturan", icon: "settings", href: "/dashboard/pengaturan" },
];

/* Options shown in the "Bantuan" dropdown — who the user can ask for help. */
export const HELP_OPTIONS = [
  { id: "admin", label: "Admin" },
  { id: "developer", label: "Developer" },
] as const;

/* Footer links have no page routes yet ("Keluar" will be a sign-out action,
 * not a navigation target), so they keep rendering as inert anchors. */
export const FOOTER_ITEMS: NavItem[] = [
  { id: "bantuan", label: "Bantuan", icon: "help" },
  { id: "keluar", label: "Keluar", icon: "logout" },
];
