"use client";

import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from "react";

/* -------------------------------------------------------------------------
 * Session — the logged-in user for the dashboard, shaped like GET /me.
 *
 * PLACEHOLDER: there is no authentication yet, so the user below is a local
 * stand-in (edit MOCK_USER to try other roles / an already-filled phone).
 * Once the API client exists, SessionProvider should load the user from
 * GET /me and `updateUser` becomes `refreshUser()`; everything that reads
 * `useSession()` keeps working unchanged.
 * ---------------------------------------------------------------------- */
export type Role = "super_admin" | "admin" | "guru_halaqah";

export type SessionUser = {
  id: number;
  nama: string;
  email: string;
  telp: string | null;
  role: Role;
  role_label: string;
  kategori: "ikh" | "akh" | null;
  is_active: boolean;
  /** Admin / super admin whose phone number is still empty. Computed by the backend in production. */
  perlu_lengkapi_telp: boolean;
  jumlah_siswa: number;
};

const ROLE_LABELS: Record<Role, string> = {
  super_admin: "Super Admin",
  admin: "Admin",
  guru_halaqah: "Guru Pengampu",
};

/* NOTE: only admin and super_admin must have a phone number. For every other
 * role (guru_halaqah) the phone stays null and the UI does not ask for it. If
 * a guru is later promoted to admin, the requirement — and the gate — start
 * applying to them from then on. */
export function requiresPhone(role: Role): boolean {
  return role === "admin" || role === "super_admin";
}

// Mirrors the backend rule; only used while the user is mocked locally.
function needsPhone(role: Role, telp: string | null): boolean {
  return requiresPhone(role) && !telp;
}

function buildUser(base: Pick<SessionUser, "id" | "nama" | "email" | "telp" | "role" | "kategori" | "jumlah_siswa">) {
  return {
    ...base,
    role_label: ROLE_LABELS[base.role],
    is_active: true,
    perlu_lengkapi_telp: needsPhone(base.role, base.telp),
  } satisfies SessionUser;
}

const MOCK_USER: SessionUser = buildUser({
  id: 1,
  nama: "Muhammad Zaid Burhanuddin",
  email: "zaidburhan@gmail.com",
  telp: null,
  role: "admin",
  kategori: null,
  jumlah_siswa: 0,
});

type ProfilePatch = { nama?: string; email?: string; telp?: string | null };

type SessionValue = {
  user: SessionUser;
  /** Applies saved profile changes (stand-in for refreshUser() after PUT /me). */
  updateUser: (patch: ProfilePatch) => void;
};

const SessionContext = createContext<SessionValue | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<SessionUser>(MOCK_USER);

  const updateUser = useCallback((patch: ProfilePatch) => {
    setUser((current) => {
      const telp = patch.telp !== undefined ? patch.telp : current.telp;
      return {
        ...current,
        ...patch,
        telp,
        perlu_lengkapi_telp: needsPhone(current.role, telp),
      };
    });
  }, []);

  const value = useMemo(() => ({ user, updateUser }), [user, updateUser]);

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionValue {
  const value = useContext(SessionContext);
  if (!value) throw new Error("useSession must be used inside <SessionProvider>");
  return value;
}

/** Data Siswa / Halaqah / Target are read-only for super admin (no add/edit/delete/assign). */
export function useCanManageMasterData(): boolean {
  return useSession().user.role !== "super_admin";
}
