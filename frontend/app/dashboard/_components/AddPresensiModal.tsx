"use client";

import { useState } from "react";
import type { AttendanceStatus } from "./StatusBadge";
import { useModalA11y } from "./useModalA11y";

const STATUS_OPTIONS: AttendanceStatus[] = ["Hadir", "Izin", "Sakit", "Alpa"];

export type PresensiSubject = { id: string; name: string; meta?: string };

/* -------------------------------------------------------------------------
 * AddPresensiModal — shared "Tambah Presensi" popup for the guru/siswa
 * attendance pages. Same dialog a11y contract as the sidebar's MobileDrawer:
 * role="dialog", ESC to close, Tab focus trap, body scroll lock, focus
 * restored to the trigger on close. Bottom sheet on mobile, centered card
 * from sm: up.
 * ---------------------------------------------------------------------- */
export function AddPresensiModal({
  isOpen,
  onClose,
  subjectLabel,
  subjects,
}: {
  isOpen: boolean;
  onClose: () => void;
  subjectLabel: string;
  subjects: PresensiSubject[];
}) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [statuses, setStatuses] = useState<Record<string, AttendanceStatus>>({});
  const [notes, setNotes] = useState<Record<string, string>>({});

  if (!isOpen) return null;

  function setStatus(id: string, status: AttendanceStatus) {
    setStatuses((prev) => ({ ...prev, [id]: status }));
  }

  function setNote(id: string, note: string) {
    setNotes((prev) => ({ ...prev, [id]: note }));
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
        aria-labelledby="add-presensi-title"
        className="flex max-h-[85dvh] w-full max-w-lg flex-col rounded-2xl bg-white"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b border-[#e1e3e4] px-6 py-5">
          <h2 id="add-presensi-title" className="text-[20px] leading-7 font-bold text-[#191c1d]">
            Tambah Presensi {subjectLabel}
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

        <div className="flex-1 overflow-y-auto px-6 py-5">
          <form className="flex flex-col gap-5">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <label className="flex flex-col gap-1.5">
                <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944]">
                  Tanggal &amp; Waktu
                </span>
                <input
                  type="datetime-local"
                  className="rounded-lg border border-[#e1e3e4] px-[13px] py-[9px] text-[14px] leading-5 text-[#191c1d] outline-none"
                />
              </label>
              <label className="flex flex-col gap-1.5">
                <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944]">Sesi</span>
                <select
                  defaultValue="pagi"
                  className="rounded-lg border border-[#e1e3e4] px-[13px] py-[9px] text-[14px] leading-5 text-[#191c1d] outline-none"
                >
                  <option value="pagi">Sesi Pagi</option>
                  <option value="siang">Sesi Siang</option>
                  <option value="sore">Sesi Sore</option>
                </select>
              </label>
            </div>

            <fieldset className="flex flex-col gap-2">
              <legend className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944]">
                Daftar {subjectLabel}
              </legend>
              <ul className="flex flex-col rounded-xl border border-[#e1e3e4]">
                {subjects.map((subject, index) => (
                  <li
                    key={subject.id}
                    className={`flex flex-col gap-3 px-4 py-3 ${
                      index !== subjects.length - 1 ? "border-b border-[#eff0f1]" : ""
                    }`}
                  >
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div className="min-w-0">
                        <p className="truncate text-[14px] leading-5 font-medium text-[#191c1d]">{subject.name}</p>
                        {subject.meta && (
                          <p className="truncate text-[12px] leading-4 text-[#404944]">{subject.meta}</p>
                        )}
                      </div>

                      <div className="flex flex-wrap gap-1.5" role="group" aria-label={`Status kehadiran ${subject.name}`}>
                        {STATUS_OPTIONS.map((status) => {
                          const active = (statuses[subject.id] ?? "Hadir") === status;
                          return (
                            <button
                              key={status}
                              type="button"
                              aria-pressed={active}
                              onClick={() => setStatus(subject.id, status)}
                              className={`rounded-full border px-3 py-1 text-[12px] leading-4 font-semibold tracking-[0.6px] whitespace-nowrap ${
                                active
                                  ? "border-transparent bg-[#6cf8bb] text-[#00714d]"
                                  : "border-[#e1e3e4] text-[#404944] hover:bg-[#edeeef]"
                              }`}
                            >
                              {status}
                            </button>
                          );
                        })}
                      </div>
                    </div>

                    <label className="flex flex-col gap-1">
                      <span className="sr-only">Keterangan untuk {subject.name}</span>
                      <input
                        type="text"
                        placeholder="Keterangan (opsional)"
                        value={notes[subject.id] ?? ""}
                        onChange={(event) => setNote(subject.id, event.target.value)}
                        className="w-full rounded-lg border border-[#e1e3e4] bg-[#f8f9fa] px-3 py-2 text-[13px] leading-4 text-[#191c1d] outline-none placeholder:text-[#8a9490] focus:bg-white"
                      />
                    </label>
                  </li>
                ))}
              </ul>
            </fieldset>
          </form>
        </div>

        <div className="flex flex-col-reverse gap-3 border-t border-[#e1e3e4] px-6 py-4 sm:flex-row sm:justify-end">
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg border border-[#e1e3e4] px-6 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944] hover:bg-[#edeeef]"
          >
            Batal
          </button>
          <button
            type="button"
            className="rounded-lg bg-[#003527] px-8 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-white shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-[#064e3b]"
          >
            Simpan Presensi
          </button>
        </div>
      </div>
    </div>
  );
}
