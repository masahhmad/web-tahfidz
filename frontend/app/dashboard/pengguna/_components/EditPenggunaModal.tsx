"use client";

import { useState, type FormEvent } from "react";
import { createPortal } from "react-dom";
import { NavIcon, icons } from "../../_components/icons";
import { useModalA11y } from "../../_components/useModalA11y";
import { isValidPhone, normalizePhone, PHONE_FORMAT_ERROR, PHONE_REQUIRED_ERROR } from "../../_lib/phone";
import { ROLE_OPTIONS } from "./AddPenggunaModal";

const fieldLabel = "text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted";
const fieldControl =
  "w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft";

// A super admin's role can never be changed (the backend rejects it), and it is not a role the UI can assign.
export const SUPER_ADMIN_LABEL = "Super Admin";

/** `telp` is only part of the form (and the result) when `showPhone` is on. */
export type PenggunaEdit = { name: string; email: string; role: string; telp?: string };

/* -------------------------------------------------------------------------
 * EditPenggunaModal — popup opened from a row's pencil button to change the
 * user's name, email, and role. Passwords are handled separately by
 * ResetPasswordModal (key button). Pass roleEditable={false} where the user
 * may not change their own role (Pengaturan): the role select is then shown
 * disabled — it is also locked for a super admin row. Pass showPhone on
 * Pengaturan only (the Pengguna page has no phone field); the number is
 * normalised to 08xxxxxxxxxx and validated before saving, and
 * phoneRequired makes it mandatory (admin / super admin). Rendered through a portal since it's
 * opened from inside a <tr>/<td>, where a fixed overlay would be hoisted out
 * of the table and cause a hydration mismatch.
 * ---------------------------------------------------------------------- */
export function EditPenggunaModal({
  isOpen,
  onClose,
  initial,
  roleEditable = true,
  showPhone = false,
  phoneRequired = false,
  onSave,
}: {
  isOpen: boolean;
  onClose: () => void;
  initial: PenggunaEdit;
  roleEditable?: boolean;
  showPhone?: boolean;
  phoneRequired?: boolean;
  onSave: (next: PenggunaEdit) => void;
}) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [name, setName] = useState(initial.name);
  const [email, setEmail] = useState(initial.email);
  const [role, setRole] = useState(initial.role);
  const [telp, setTelp] = useState(initial.telp ?? "");
  const [telpError, setTelpError] = useState<string | null>(null);

  if (!isOpen || typeof document === "undefined") return null;

  const roleLocked = !roleEditable || initial.role === SUPER_ADMIN_LABEL;
  const roleOptions = ROLE_OPTIONS.includes(role) ? ROLE_OPTIONS : [role, ...ROLE_OPTIONS];

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const next: PenggunaEdit = { name: name.trim(), email: email.trim(), role };

    if (showPhone) {
      const normalized = normalizePhone(telp);
      if (normalized === "" && phoneRequired) {
        setTelpError(PHONE_REQUIRED_ERROR);
        return;
      }
      if (normalized !== "" && !isValidPhone(normalized)) {
        setTelpError(PHONE_FORMAT_ERROR);
        return;
      }
      next.telp = normalized;
    }

    onSave(next);
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-pengguna-title"
        className="flex max-h-[90dvh] w-full max-w-sm flex-col overflow-y-auto rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="edit-pengguna-title"
          className="pb-3 text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Ubah Data Pengguna
        </h2>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Nama</span>
            <input
              type="text"
              required
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
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="Email"
              className={fieldControl}
            />
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Role</span>
            <div className="relative">
              <select
                value={role}
                onChange={(event) => setRole(event.target.value)}
                disabled={roleLocked}
                className={`${fieldControl} appearance-none pr-10 disabled:cursor-not-allowed disabled:bg-hover-soft disabled:text-soft`}
              >
                {roleOptions.map((value) => (
                  <option key={value} value={value}>
                    {value}
                  </option>
                ))}
              </select>
              <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                {icons.chevronDown}
              </NavIcon>
            </div>
          </label>

          {showPhone && (
            <label className="flex flex-col gap-1.5">
              <span className={fieldLabel}>Nomor Telepon</span>
              <input
                type="tel"
                inputMode="tel"
                autoComplete="tel"
                aria-required={phoneRequired}
                aria-invalid={telpError !== null}
                aria-describedby="edit-pengguna-telp-hint"
                value={telp}
                onChange={(event) => {
                  setTelp(event.target.value);
                  setTelpError(null);
                }}
                placeholder="08xxxxxxxxxx"
                className={`${fieldControl} ${telpError ? "border-danger" : ""}`}
              />
              {telpError ? (
                <span id="edit-pengguna-telp-hint" role="alert" className="text-[12px] leading-4 text-danger">
                  {telpError}
                </span>
              ) : (
                <span id="edit-pengguna-telp-hint" className="text-[12px] leading-4 text-soft">
                  {phoneRequired ? "Wajib diisi. " : ""}Contoh: 0821-4298-6689
                </span>
              )}
            </label>
          )}

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
    </div>,
    document.body,
  );
}
