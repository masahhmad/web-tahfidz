"use client";

import { createPortal } from "react-dom";
import { useModalA11y } from "../../_components/useModalA11y";

/* -------------------------------------------------------------------------
 * ToggleActiveModal — confirmation popup opened from a row's activation
 * button. Wording follows the user's current status: an active user is being
 * deactivated, an inactive one re-activated. Rendered through a portal since
 * it's opened from inside a <tr>/<td> (see EditPenggunaModal).
 * ---------------------------------------------------------------------- */
export function ToggleActiveModal({
  isOpen,
  onClose,
  userName,
  isActive,
  onConfirm,
}: {
  isOpen: boolean;
  onClose: () => void;
  userName: string;
  isActive: boolean;
  onConfirm: () => void;
}) {
  const panelRef = useModalA11y(isOpen, onClose);

  if (!isOpen || typeof document === "undefined") return null;

  const verb = isActive ? "menonaktifkan" : "mengaktifkan";

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="toggle-active-title"
        aria-describedby="toggle-active-desc"
        className="flex w-full max-w-sm flex-col rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="toggle-active-title"
          className="pb-3 text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          {isActive ? "Nonaktifkan Pengguna" : "Aktifkan Pengguna"}
        </h2>
        <p id="toggle-active-desc" className="pb-4 text-center text-[14px] leading-5 text-muted">
          Apakah Anda yakin ingin {verb} pengguna ini?
          <br />
          <span className="font-semibold text-ink">{userName}</span>
        </p>

        <div className="grid grid-cols-2 gap-2">
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg border border-line-strong bg-transparent px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-ink hover:bg-hover"
          >
            Batal
          </button>
          <button
            type="button"
            onClick={onConfirm}
            className="rounded-lg bg-brand px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover"
          >
            Simpan
          </button>
        </div>
      </div>
    </div>,
    document.body,
  );
}
