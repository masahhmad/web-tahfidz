import type { IconName } from "./icons";

/* -------------------------------------------------------------------------
 * Nav data — extracted from the Figma "Navigation Links" / "Footer Links"
 * groups inside the SideNavBar component.
 * ---------------------------------------------------------------------- */
export type NavItem = { id: string; label: string; icon: IconName };

export const NAV_ITEMS: NavItem[] = [
  { id: "dashboard", label: "Dashboard", icon: "dashboard" },
  { id: "presensi-guru", label: "Presensi Guru", icon: "teacher" },
  { id: "presensi-siswa", label: "Presensi Siswa", icon: "student" },
  { id: "setoran-siswa", label: "Setoran Siswa", icon: "deposit" },
  { id: "ujian-kenaikan-juz", label: "Ujian Kenaikan Juz", icon: "exam" },
  { id: "data-siswa", label: "Data Siswa", icon: "dataStudent" },
  { id: "halaqah", label: "Halaqah", icon: "halaqah" },
  { id: "target", label: "Target", icon: "target" },
  { id: "pengguna", label: "Pengguna", icon: "users" },
  { id: "settings", label: "Settings", icon: "settings" },
];

export const FOOTER_ITEMS: NavItem[] = [
  { id: "bantuan", label: "Bantuan", icon: "help" },
  { id: "keluar", label: "Keluar", icon: "logout" },
];
