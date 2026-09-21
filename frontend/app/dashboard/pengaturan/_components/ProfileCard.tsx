"use client";

import { useEffect, useState } from "react";
import { EditPenggunaModal, type PenggunaEdit } from "../../pengguna/_components/EditPenggunaModal";
import { ResetPasswordModal } from "../../pengguna/_components/ResetPasswordModal";
import { requiresPhone, useSession } from "../../_components/session";

/* -------------------------------------------------------------------------
 * ProfileCard — the logged-in user's profile summary: avatar (a generic
 * placeholder silhouette until a real photo is wired to the profile API),
 * name, email, role, phone number (admin / super admin only), halaqah count (guru pengampu only), and
 * the Edit Pengguna / Ganti Password actions, which open the same
 * EditPenggunaModal / ResetPasswordModal used on the Pengguna page. Saving
 * the phone number here is what lifts the dashboard's phone-number gate.
 * Centered card, buttons stack full-width on mobile and sit side by side
 * from sm up.
 * ---------------------------------------------------------------------- */
export function ProfileCard() {
  const { user, updateUser } = useSession();
  const [isEditing, setIsEditing] = useState(false);
  const [isResetting, setIsResetting] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  // Only admin / super admin have a phone number (and must fill it in); other
  // roles keep it null and never see the field. It becomes mandatory for a user
  // as soon as their role is changed to admin.
  const hasPhone = requiresPhone(user.role);

  useEffect(() => {
    if (!notice) return;
    const timer = setTimeout(() => setNotice(null), 4000);
    return () => clearTimeout(timer);
  }, [notice]);

  function handleSaveEdit(next: PenggunaEdit) {
    updateUser({ nama: next.name, email: next.email, ...(hasPhone ? { telp: next.telp || null } : {}) });
    setIsEditing(false);
    setNotice("Profil berhasil disimpan.");
  }

  function handleSavePassword() {
    // Placeholder until the backend exists; the password is not stored client-side.
    setIsResetting(false);
    setNotice("Kata sandi berhasil diganti.");
  }

  return (
    <div className="flex w-full flex-col items-center gap-3 rounded-xl bg-card p-5 text-center shadow-[0px_4px_10px_0px_rgba(0,0,0,0.03)] sm:p-8">
      <div className="flex size-[140px] items-end justify-center overflow-hidden rounded-xl bg-avatar sm:size-[208px]">
        <svg viewBox="0 0 208 208" className="size-full" aria-hidden="true">
          <circle cx="104" cy="82" r="38" className="fill-card" />
          <path d="M20 208c0-58 37.6-92 84-92s84 34 84 92z" className="fill-card" />
        </svg>
      </div>

      <div className="flex flex-col items-center gap-1.5">
        <p className="text-[16px] leading-6 font-medium text-ink">{user.nama}</p>
        <p className="text-[16px] leading-6 text-muted">{user.email}</p>
        <p className="text-[16px] leading-6 text-muted">{user.role_label}</p>
        {hasPhone &&
          (user.telp ? (
            <p className="text-[16px] leading-6 text-muted">{user.telp}</p>
          ) : (
            <p className="text-[16px] leading-6 font-medium text-danger">Nomor telepon belum diisi</p>
          ))}
        {user.role === "guru_halaqah" && (
          <p className="text-[16px] leading-6 text-muted">Mengampu {user.jumlah_siswa} siswa</p>
        )}
      </div>

      {notice && (
        <p role="status" className="rounded-lg bg-mint px-4 py-2 text-[14px] leading-5 font-medium text-on-mint">
          {notice}
        </p>
      )}

      <div className="flex w-full flex-col gap-2 pt-1 sm:w-auto sm:flex-row">
        <button
          type="button"
          onClick={() => setIsEditing(true)}
          className="w-full rounded-lg border border-line-strong bg-transparent px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-ink hover:bg-hover sm:w-auto"
        >
          Edit Pengguna
        </button>
        <button
          type="button"
          onClick={() => setIsResetting(true)}
          className="w-full rounded-lg bg-brand px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover sm:w-auto"
        >
          Ganti Password
        </button>
      </div>

      {isEditing && (
        <EditPenggunaModal
          isOpen
          onClose={() => setIsEditing(false)}
          initial={{ name: user.nama, email: user.email, role: user.role_label, telp: user.telp ?? "" }}
          roleEditable={false}
          showPhone={hasPhone}
          phoneRequired={hasPhone}
          onSave={handleSaveEdit}
        />
      )}
      {isResetting && (
        <ResetPasswordModal
          isOpen
          onClose={() => setIsResetting(false)}
          userName={user.nama}
          requireCurrent
          onSave={handleSavePassword}
        />
      )}
    </div>
  );
}
