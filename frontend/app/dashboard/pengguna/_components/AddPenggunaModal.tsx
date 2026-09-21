"use client";

import { useState, type FormEvent } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { useModalA11y } from "../../_components/useModalA11y";

// Role yang bisa dibuat lewat UI (backend: admin, guru_halaqah). super_admin hanya dari seeder.
export const ROLE_OPTIONS = ["Admin", "Guru Pengampu"];
export const KATEGORI_OPTIONS = ["Ikhwan", "Akhwat"];

const fieldLabel = "text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted";
const fieldControl =
  "w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft";

/* -------------------------------------------------------------------------
 * AddPenggunaModal — "Tambah Pengguna Baru" popup opened from the Tambah
 * Pengguna button. Same dialog a11y contract as the other modals
 * (useModalA11y). As with AddSiswaModal, every control gets a visible
 * <label>; the design's inline text becomes each control's placeholder.
 * ---------------------------------------------------------------------- */
export function AddPenggunaModal({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [role, setRole] = useState("");
  const [kategori, setKategori] = useState("");
  const [password, setPassword] = useState("");

  if (!isOpen) return null;

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    onClose();
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-pengguna-title"
        className="flex max-h-[90dvh] w-full max-w-sm flex-col overflow-y-auto rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="add-pengguna-title"
          className="pb-3 text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Tambah Pengguna Baru
        </h2>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Nama</span>
            <input
              type="text"
              value={name}
              onChange={(event) => setName(event.target.value)}
              placeholder="Nama"
              className={fieldControl}
            />
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Email</span>
            <input
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="Email"
              className={fieldControl}
            />
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Kata Sandi</span>
            <input
              type="password"
              required
              minLength={8}
              autoComplete="new-password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="Minimal 8 karakter"
              className={fieldControl}
            />
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Role</span>
            <div className="relative">
              <select
                value={role}
                onChange={(event) => setRole(event.target.value)}
                className={`${fieldControl} appearance-none pr-10 ${role === "" ? "text-soft" : ""}`}
              >
                <option value="" disabled>
                  Role
                </option>
                {ROLE_OPTIONS.map((value) => (
                  <option key={value} value={value} className="text-ink">
                    {value}
                  </option>
                ))}
              </select>
              <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                {icons.chevronDown}
              </NavIcon>
            </div>
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Kategori</span>
            <div className="relative">
              <select
                value={kategori}
                required
                onChange={(event) => setKategori(event.target.value)}
                className={`${fieldControl} appearance-none pr-10 ${kategori === "" ? "text-soft" : ""}`}
              >
                <option value="" disabled>
                  Kategori
                </option>
                {KATEGORI_OPTIONS.map((value) => (
                  <option key={value} value={value} className="text-ink">
                    {value}
                  </option>
                ))}
              </select>
              <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                {icons.chevronDown}
              </NavIcon>
            </div>
          </label>

          <div className="grid grid-cols-2 gap-2 pt-1">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-line-strong bg-transparent px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-ink hover:bg-hover"
            >
              Batal
            </button>
            <button
              type="submit"
              className="rounded-lg bg-brand px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
