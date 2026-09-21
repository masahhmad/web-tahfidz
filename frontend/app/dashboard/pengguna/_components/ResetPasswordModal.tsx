"use client";

import { useState, type FormEvent } from "react";
import { createPortal } from "react-dom";
import { useModalA11y } from "../../_components/useModalA11y";

const fieldLabel = "text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted";
const fieldControl =
  "w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft";

/* -------------------------------------------------------------------------
 * ResetPasswordModal — popup opened from a row's key button. Two inputs:
 * the new password and its confirmation; saving is blocked (with an inline
 * error) until both match. With `requireCurrent` (the user's own password on
 * Pengaturan) it also asks for the current password, which the API requires
 * (`current_password`); resetting someone else's password (Pengguna) does not.
 * Rendered through a portal for the same reason as
 * EditPenggunaModal — it is opened from inside a table row.
 * ---------------------------------------------------------------------- */
export function ResetPasswordModal({
  isOpen,
  onClose,
  userName,
  onSave,
  requireCurrent = false,
}: {
  isOpen: boolean;
  onClose: () => void;
  userName: string;
  onSave: (password: string, currentPassword?: string) => void;
  requireCurrent?: boolean;
}) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [current, setCurrent] = useState("");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");

  if (!isOpen || typeof document === "undefined") return null;

  const mismatch = confirm !== "" && password !== confirm;

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (password === "" || password !== confirm) return;
    onSave(password, requireCurrent ? current : undefined);
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="reset-password-title"
        className="flex max-h-[90dvh] w-full max-w-sm flex-col overflow-y-auto rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="reset-password-title"
          className="text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Ganti Kata Sandi
        </h2>
        <p className="pb-3 text-center text-[14px] leading-5 text-muted">{userName}</p>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          {requireCurrent && (
            <label className="flex flex-col gap-1.5">
              <span className={fieldLabel}>Kata Sandi Saat Ini</span>
              <input
                type="password"
                required
                autoComplete="current-password"
                value={current}
                onChange={(event) => setCurrent(event.target.value)}
                placeholder="Kata Sandi Saat Ini"
                className={fieldControl}
              />
            </label>
          )}

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Kata Sandi Baru</span>
            <input
              type="password"
              required
              autoComplete="new-password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="Kata Sandi"
              className={fieldControl}
            />
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Konfirmasi Kata Sandi</span>
            <input
              type="password"
              required
              autoComplete="new-password"
              value={confirm}
              onChange={(event) => setConfirm(event.target.value)}
              placeholder="Konfirmasi Kata Sandi"
              aria-invalid={mismatch}
              aria-describedby={mismatch ? "reset-password-error" : undefined}
              className={`${fieldControl} ${mismatch ? "border-danger" : ""}`}
            />
            {mismatch && (
              <span id="reset-password-error" role="alert" className="text-[12px] leading-4 text-danger">
                Konfirmasi kata sandi tidak cocok.
              </span>
            )}
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
              disabled={mismatch}
              className="rounded-lg bg-brand px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover disabled:opacity-50"
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
