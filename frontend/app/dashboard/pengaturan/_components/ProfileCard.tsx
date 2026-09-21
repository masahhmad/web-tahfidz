"use client";

import { useState } from "react";
import { EditPenggunaModal, type PenggunaEdit } from "../../pengguna/_components/EditPenggunaModal";
import { ResetPasswordModal } from "../../pengguna/_components/ResetPasswordModal";

/* -------------------------------------------------------------------------
 * ProfileCard — the logged-in user's profile summary: avatar (a generic
 * placeholder silhouette until a real photo is wired to the profile API),
 * name, email, role, halaqah count, and the Edit Pengguna / Ganti Password
 * actions, which open the same EditPenggunaModal / ResetPasswordModal used
 * on the Pengguna page. Centered card, buttons stack full-width on mobile and sit side
 * by side from sm up.
 * ---------------------------------------------------------------------- */
export function ProfileCard() {
  const [profile, setProfile] = useState<PenggunaEdit>({
    name: "Muhammad Zaid Burhanuddin",
    email: "zaidburhan@gmail.com",
    role: "Guru Pengampu",
  });
  const [isEditing, setIsEditing] = useState(false);
  const [isResetting, setIsResetting] = useState(false);

  function handleSaveEdit(next: PenggunaEdit) {
    setProfile(next);
    setIsEditing(false);
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
        <p className="text-[16px] leading-6 font-medium text-ink">{profile.name}</p>
        <p className="text-[16px] leading-6 text-muted">{profile.email}</p>
        <p className="text-[16px] leading-6 text-muted">{profile.role}</p>
        <p className="text-[16px] leading-6 text-muted">Mengampu 16 siswa</p>
      </div>

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
          initial={profile}
          roleEditable={false}
          onSave={handleSaveEdit}
        />
      )}
      {isResetting && (
        <ResetPasswordModal
          isOpen
          onClose={() => setIsResetting(false)}
          userName={profile.name}
          requireCurrent
          // Placeholder until the backend exists; the password is not stored client-side.
          onSave={() => setIsResetting(false)}
        />
      )}
    </div>
  );
}
