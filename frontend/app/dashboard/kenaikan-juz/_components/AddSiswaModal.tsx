"use client";

import { useState, type FormEvent } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { useModalA11y } from "../../_components/useModalA11y";

const STUDENTS = ["Ahmad Rasyid", "Bilal Ramadhan", "Fathimah Azzahra"] as const;
const JUZ_OPTIONS = Array.from({ length: 30 }, (_, i) => i + 1);

const fieldLabel = "text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted";
const fieldControl =
  "w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 outline-none";

/* -------------------------------------------------------------------------
 * AddSiswaModal — "Tambah Siswa" popup for the Ujian Kenaikan Juz page:
 * pick a student and the juz they're being tested on. Same dialog a11y
 * contract as the other modals (useModalA11y), and every control gets a
 * visible <label> with the design's inline text used as its placeholder
 * option, consistent with the other Tambah Siswa/Pengguna popups.
 * ---------------------------------------------------------------------- */
export function AddSiswaModal({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [student, setStudent] = useState("");
  const [juz, setJuz] = useState("");

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
        aria-labelledby="add-siswa-ujian-title"
        className="flex w-full max-w-xs flex-col rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="add-siswa-ujian-title"
          className="pb-3 text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Tambah Siswa
        </h2>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Siswa</span>
            <div className="relative">
              <select
                value={student}
                onChange={(event) => setStudent(event.target.value)}
                className={`${fieldControl} ${student === "" ? "text-soft" : "text-ink"}`}
              >
                <option value="" disabled>
                  Pilih Siswa
                </option>
                {STUDENTS.map((name) => (
                  <option key={name} value={name} className="text-ink">
                    {name}
                  </option>
                ))}
              </select>
              <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                {icons.chevronDown}
              </NavIcon>
            </div>
          </label>

          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Juz</span>
            <div className="relative">
              <select
                value={juz}
                onChange={(event) => setJuz(event.target.value)}
                className={`${fieldControl} ${juz === "" ? "text-soft" : "text-ink"}`}
              >
                <option value="" disabled>
                  Pilih Juz
                </option>
                {JUZ_OPTIONS.map((value) => (
                  <option key={value} value={value} className="text-ink">
                    Juz {value}
                  </option>
                ))}
              </select>
              <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                {icons.chevronDown}
              </NavIcon>
            </div>
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
    </div>
  );
}
