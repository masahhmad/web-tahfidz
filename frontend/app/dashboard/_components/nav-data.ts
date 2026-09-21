import type { IconName } from "./icons";
import type { Role } from "./session";

/* -------------------------------------------------------------------------
 * Nav data — extracted from the Figma "Navigation Links" / "Footer Links"
 * groups inside the SideNavBar component.
 * ---------------------------------------------------------------------- */
export type NavItem = {
  id: string;
  label: string;
  icon: IconName;
  href?: string;
  /** Roles allowed to see this page; omitted = every role. */
  roles?: readonly Role[];
};

// Data Siswa / Halaqah / Target: guru pengampu has no access (super admin is read-only there).
const STAFF_ROLES: readonly Role[] = ["super_admin", "admin"];

export function canAccessNav(item: NavItem, role: Role): boolean {
  return !item.roles || item.roles.includes(role);
}

export const NAV_ITEMS: NavItem[] = [
  { id: "dashboard", label: "Dashboard", icon: "dashboard", href: "/dashboard" },
  { id: "presensi-guru", label: "Presensi Guru", icon: "teacher", href: "/dashboard/presensi-guru" },
  { id: "presensi-siswa", label: "Presensi Siswa", icon: "student", href: "/dashboard/presensi-siswa" },
  { id: "setoran", label: "Setoran Siswa", icon: "deposit", href: "/dashboard/setoran" },
  { id: "kenaikan-juz", label: "Ujian Kenaikan Juz", icon: "exam", href: "/dashboard/kenaikan-juz" },
  { id: "data-siswa", label: "Data Siswa", icon: "dataStudent", href: "/dashboard/data-siswa", roles: STAFF_ROLES },
  { id: "halaqah", label: "Halaqah", icon: "halaqah", href: "/dashboard/halaqah", roles: STAFF_ROLES },
  { id: "target", label: "Target", icon: "target", href: "/dashboard/target", roles: STAFF_ROLES },
  { id: "pengguna", label: "Pengguna", icon: "users", href: "/dashboard/pengguna", roles: ["super_admin"] },
  { id: "pengaturan", label: "Pengaturan", icon: "settings", href: "/dashboard/pengaturan" },
];

/* Footer links have no page routes yet ("Keluar" will be a sign-out action,
 * not a navigation target), so they keep rendering as inert anchors. */
export const FOOTER_ITEMS: NavItem[] = [
  { id: "bantuan", label: "Bantuan", icon: "help" },
  { id: "keluar", label: "Keluar", icon: "logout" },
];
