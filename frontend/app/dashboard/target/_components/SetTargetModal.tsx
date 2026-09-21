"use client";

import { useState, type FormEvent } from "react";
import { createPortal } from "react-dom";
import { useModalA11y } from "../../_components/useModalA11y";

/* -------------------------------------------------------------------------
 * SetTargetModal — popup opened from a class row's pencil button. One number
 * input: the total juz every student in that class should reach by the end
 * of the year. Rendered through a portal because the trigger lives inside a
 * <tr>/<td>, where a fixed overlay would be hoisted out of the table by the
 * browser and cause a hydration mismatch.
 * ---------------------------------------------------------------------- */
export function SetTargetModal({
  isOpen,
  onClose,
  className,
  initialTarget,
  onSave,
}: {
  isOpen: boolean;
  onClose: () => void;
  className: string;
  initialTarget: number | null;
  onSave: (target: number) => void;
}) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [target, setTarget] = useState(initialTarget !== null ? String(initialTarget) : "");

  if (!isOpen || typeof document === "undefined") return null;

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (target.trim() === "") return;

    const parsed = Number(target);
    if (Number.isNaN(parsed)) return;

    onSave(parsed);
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="set-target-title"
        className="flex w-full max-w-xs flex-col rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="set-target-title"
          className="pb-3 text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Target {className}
        </h2>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <label htmlFor="target-juz" className="flex flex-col gap-1.5">
            <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted">
              Total Juz <span className="font-normal text-soft">(1-30)</span>
            </span>
            <input
              id="target-juz"
              type="number"
              min={1}
              max={30}
              value={target}
              onChange={(event) => setTarget(event.target.value)}
              placeholder="Masukkan Jumlah Juz"
              title="Total juz yang harus dicapai siswa pada tahun ini"
              className="w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft"
            />
          </label>

          <div className="flex gap-2 pt-1">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 rounded-lg border border-line-strong bg-transparent px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-ink hover:bg-hover"
            >
              Batal
            </button>
            <button
              type="submit"
              className="flex-1 rounded-lg bg-brand px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover"
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
