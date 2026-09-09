import type { ReactNode } from "react";

/* -------------------------------------------------------------------------
 * NavIcon — small inline icon wrapper, atomic building block reused by
 * every sidebar/header entry so icon sizing stays consistent.
 * ---------------------------------------------------------------------- */
export function NavIcon({
  children,
  size = 18,
  className = "",
}: {
  children: ReactNode;
  size?: number;
  className?: string;
}) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.8}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      className={`shrink-0 ${className}`}
    >
      {children}
    </svg>
  );
}

/* Individual icon glyphs, kept tiny so each NAV_ITEMS entry stays declarative */
export const icons = {
  dashboard: (
    <>
      <rect x="3" y="3" width="7" height="7" rx="1.5" />
      <rect x="14" y="3" width="7" height="7" rx="1.5" />
      <rect x="3" y="14" width="7" height="7" rx="1.5" />
      <rect x="14" y="14" width="7" height="7" rx="1.5" />
    </>
  ),
  teacher: (
    <>
      <circle cx="9" cy="7" r="3.5" />
      <path d="M2.5 20c0-3.6 2.9-6.5 6.5-6.5s6.5 2.9 6.5 6.5" />
      <path d="M16 4.5c1.7.4 3 2 3 3.9 0 1.9-1.3 3.5-3 3.9" />
      <path d="M21.5 20c0-2.7-1.7-5-4-6" />
    </>
  ),
  student: (
    <>
      <circle cx="12" cy="8" r="3.5" />
      <path d="M4.5 20c0-4.1 3.4-7.5 7.5-7.5s7.5 3.4 7.5 7.5" />
    </>
  ),
  deposit: (
    <>
      <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5z" />
      <path d="M8 9h8M8 13h8M8 17h5" />
    </>
  ),
  exam: (
    <>
      <path d="M6 3.5h9l3 3v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1Z" />
      <path d="m9 13 2 2 4-4.5" />
    </>
  ),
  dataStudent: (
    <>
      <ellipse cx="12" cy="5.5" rx="7.5" ry="2.5" />
      <path d="M4.5 5.5V12c0 1.4 3.4 2.5 7.5 2.5s7.5-1.1 7.5-2.5V5.5" />
      <path d="M4.5 12v6.5c0 1.4 3.4 2.5 7.5 2.5s7.5-1.1 7.5-2.5V12" />
    </>
  ),
  halaqah: (
    <>
      <circle cx="12" cy="12" r="3" />
      <circle cx="12" cy="4" r="1.6" />
      <circle cx="19" cy="8.5" r="1.6" />
      <circle cx="19" cy="15.5" r="1.6" />
      <circle cx="12" cy="20" r="1.6" />
      <circle cx="5" cy="15.5" r="1.6" />
      <circle cx="5" cy="8.5" r="1.6" />
    </>
  ),
  target: (
    <>
      <path d="M3 17.5 9 11l3.5 3.5L21 6" />
      <path d="M15 6h6v6" />
    </>
  ),
  users: (
    <>
      <circle cx="8.5" cy="8" r="3" />
      <path d="M2.5 20c0-3.3 2.7-6 6-6s6 2.7 6 6" />
      <circle cx="17.5" cy="9" r="2.3" />
      <path d="M15.5 13.2c2.6.4 4.5 2.6 4.5 5.3" />
    </>
  ),
  settings: (
    <>
      <circle cx="12" cy="12" r="3.2" />
      <path d="M12 3v2.2M12 18.8V21M21 12h-2.2M5.2 12H3M18.4 5.6l-1.5 1.5M7.1 16.9l-1.5 1.5M18.4 18.4l-1.5-1.5M7.1 7.1 5.6 5.6" />
    </>
  ),
  help: (
    <>
      <circle cx="12" cy="12" r="9" />
      <path d="M9.5 9.3a2.5 2.5 0 1 1 3.4 2.3c-.9.4-1.4.9-1.4 1.9" />
      <path d="M12 17h.01" />
    </>
  ),
  logout: (
    <>
      <path d="M9 20H5.5A1.5 1.5 0 0 1 4 18.5v-13A1.5 1.5 0 0 1 5.5 4H9" />
      <path d="M16 16.5 21 12l-5-4.5" />
      <path d="M21 12H9" />
    </>
  ),
  search: <circle cx="11" cy="11" r="7.5" />,
  bell: (
    <>
      <path d="M6 10a6 6 0 0 1 12 0v4.5l1.8 2.5H4.2L6 14.5Z" />
      <path d="M10 19.5a2 2 0 0 0 4 0" />
    </>
  ),
  mail: (
    <>
      <rect x="3" y="5.5" width="18" height="13" rx="1.5" />
      <path d="m4 6.5 8 6.5 8-6.5" />
    </>
  ),
  moon: <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z" />,
  menu: <path d="M3.5 6.5h17M3.5 12h17M3.5 17.5h17" />,
  close: <path d="M6 6l12 12M18 6 6 18" />,
  trendUp: <path d="M4 15 10 9l4 4 6-7" />,
  trendDown: <path d="M4 8 10 14l4-4 6 7" />,
};

export type IconName = keyof typeof icons;
