"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";

const PENGAMPU_OPTIONS = ["Ustadz Zaid", "Ustadz Umar", "Ustadzah Aisyah"];
const HALAQAH_OPTIONS = ["Kelas 7A-1", "Kelas 7A-2", "Kelas 7B-1", "Kelas 8A-1"];

/* -------------------------------------------------------------------------
 * AssignHalaqahBar — appears once "Atur Halaqah" is opened: pick the
 * pengampu + target halaqah group, then Batal/Simpan the student selection
 * made in the table below. Fields stack on mobile, sit in one row from sm.
 * ---------------------------------------------------------------------- */
export function AssignHalaqahBar({ onCancel, onSave }: { onCancel: () => void; onSave: () => void }) {
  const [pengampu, setPengampu] = useState(PENGAMPU_OPTIONS[0]);
  const [halaqah, setHalaqah] = useState(HALAQAH_OPTIONS[0]);

  return (
    <div className="flex w-full flex-col gap-3 rounded-xl bg-card p-5 shadow-[0px_4px_10px_0px_rgba(0,0,0,0.03)] sm:flex-row sm:items-center sm:justify-between">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label className="relative">
          <span className="sr-only">Guru Pengampu</span>
          <select
            value={pengampu}
            onChange={(event) => setPengampu(event.target.value)}
            className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none sm:w-auto"
          >
            {PENGAMPU_OPTIONS.map((value) => (
              <option key={value} value={value}>
                {value}
              </option>
            ))}
          </select>
          <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
            {icons.chevronDown}
          </NavIcon>
        </label>

        <label className="relative">
          <span className="sr-only">Kelompok Halaqah</span>
          <select
            value={halaqah}
            onChange={(event) => setHalaqah(event.target.value)}
            className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none sm:w-auto"
          >
            {HALAQAH_OPTIONS.map((value) => (
              <option key={value} value={value}>
                {value}
              </option>
            ))}
          </select>
          <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
            {icons.chevronDown}
          </NavIcon>
        </label>
      </div>

      <div className="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
        <button
          type="button"
          onClick={onCancel}
          className="w-full rounded-lg border border-line bg-field px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-brand hover:bg-hover sm:w-auto"
        >
          Batal
        </button>
        <button
          type="button"
          onClick={onSave}
          className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-6 py-2 text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover sm:w-auto"
        >
          Simpan
        </button>
      </div>
    </div>
  );
}
