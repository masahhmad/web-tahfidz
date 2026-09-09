"use client";

import { useState, type FormEvent } from "react";
import { useModalA11y } from "../../_components/useModalA11y";

const STATUS_OPTIONS = ["Hadir", "Izin", "Sakit", "Alpa"] as const;
type GuruAttendanceStatus = (typeof STATUS_OPTIONS)[number];

/* -------------------------------------------------------------------------
 * AddPresensiGuruModal — single-entry "Tambah Presensi" popup for a guru's
 * own attendance in one session (header = session name, a Kehadiran select,
 * then Keterangan). Keterangan is optional for "Hadir" and required for any
 * other status, enforced both visually (asterisk, placeholder) and natively
 * via the `required` attribute on the textarea.
 * ---------------------------------------------------------------------- */
export function AddPresensiGuruModal({
  isOpen,
  onClose,
  sessionName,
}: {
  isOpen: boolean;
  onClose: () => void;
  sessionName: string;
}) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [status, setStatus] = useState<GuruAttendanceStatus>("Hadir");
  const [note, setNote] = useState("");
  const isNoteRequired = status !== "Hadir";

  if (!isOpen) return null;

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    onClose();
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      onClick={onClose}
    >
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-presensi-guru-title"
        className="flex w-full max-w-md flex-col rounded-2xl bg-white"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b border-[#e1e3e4] px-6 py-5">
          <h2 id="add-presensi-guru-title" className="text-[20px] leading-7 font-bold text-[#191c1d]">
            {sessionName}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Tutup"
            className="rounded p-1 text-[#404944] hover:bg-[#edeeef]"
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex flex-col gap-5 px-6 py-5">
          <label className="flex flex-col gap-1.5">
            <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944]">Kehadiran</span>
            <select
              value={status}
              onChange={(event) => setStatus(event.target.value as GuruAttendanceStatus)}
              className="rounded-lg border border-[#e1e3e4] px-[13px] py-[9px] text-[14px] leading-5 text-[#191c1d] outline-none"
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option} value={option}>
                  {option}
                </option>
              ))}
            </select>
          </label>

          <label className="flex flex-col gap-1.5">
            <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944]">
              Keterangan{" "}
              {isNoteRequired ? (
                <span className="text-[#a01818]">*</span>
              ) : (
                <span className="font-normal text-[#8a9490]">(opsional)</span>
              )}
            </span>
            <textarea
              rows={4}
              value={note}
              onChange={(event) => setNote(event.target.value)}
              required={isNoteRequired}
              placeholder={
                isNoteRequired ? "Wajib diisi untuk status selain Hadir" : "Tambahkan keterangan (opsional)"
              }
              className="resize-none rounded-lg border border-[#e1e3e4] px-[13px] py-[9px] text-[14px] leading-5 text-[#191c1d] outline-none"
            />
          </label>

          <div className="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-[#e1e3e4] px-6 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944] hover:bg-[#edeeef]"
            >
              Batal
            </button>
            <button
              type="submit"
              className="rounded-lg bg-[#003527] px-8 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-white shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-[#064e3b]"
            >
              Simpan Presensi
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
