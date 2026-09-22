"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { useCanManageMasterData } from "../../_components/session";
import { AddSiswaModal } from "./AddSiswaModal";

const CLASS_OPTIONS = ["7A", "7B", "8A", "8B", "9A", "9B"];
const PENGAMPU_OPTIONS = ["Ustadz Zaid", "Ustadz Umar", "Ustadzah Aisyah"];

/* -------------------------------------------------------------------------
 * FilterBar — class / pengampu filters with the "Tambah Siswa" action
 * inline to their right from sm up. On mobile the two filters and the
 * action button all stack full-width in a single column. Super admin is
 * read-only here, so the "Tambah Siswa" action is not rendered for them.
 * ---------------------------------------------------------------------- */
export function FilterBar() {
  const [className, setClassName] = useState("");
  const [pengampu, setPengampu] = useState("");
  const [isModalOpen, setIsModalOpen] = useState(false);
  const canManage = useCanManageMasterData();

  return (
    <div className="flex w-full flex-col gap-3 rounded-xl bg-card p-5 shadow-[0px_4px_10px_0px_rgba(0,0,0,0.03)] sm:flex-row sm:items-center sm:justify-between">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label className="relative">
          <span className="sr-only">Pilih Kelas</span>
          <select
            value={className}
            onChange={(event) => setClassName(event.target.value)}
            className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none sm:w-auto"
          >
            <option value="">Pilih Kelas</option>
            {CLASS_OPTIONS.map((value) => (
              <option key={value} value={value}>
                Kelas {value}
              </option>
            ))}
          </select>
          <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
            {icons.chevronDown}
          </NavIcon>
        </label>

        <label className="relative">
          <span className="sr-only">Pengampu</span>
          <select
            value={pengampu}
            onChange={(event) => setPengampu(event.target.value)}
            className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none sm:w-auto"
          >
            <option value="">Semua Pengampu</option>
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
      </div>

      {canManage && (
        <>
          <button
            type="button"
            onClick={() => setIsModalOpen(true)}
            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-6 py-2 text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover sm:w-auto"
          >
            Tambah Siswa
            <NavIcon size={14}>{icons.plus}</NavIcon>
          </button>

          <AddSiswaModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
        </>
      )}
    </div>
  );
}
