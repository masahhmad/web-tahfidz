"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { AddPenggunaModal } from "./AddPenggunaModal";

const ROLE_OPTIONS = ["Admin", "Guru Pengampu", "Wali Kelas"];

/* -------------------------------------------------------------------------
 * FilterBar — role filter on the left, "Tambah Pengguna" action on the
 * right. Stacks full-width on mobile, sits in one row from sm up.
 * ---------------------------------------------------------------------- */
export function FilterBar() {
  const [role, setRole] = useState("");
  const [isModalOpen, setIsModalOpen] = useState(false);

  return (
    <div className="flex w-full flex-col gap-3 rounded-xl bg-card p-5 shadow-[0px_4px_10px_0px_rgba(0,0,0,0.03)] sm:flex-row sm:items-center sm:justify-between">
      <label className="relative">
        <span className="sr-only">Filter Pengguna</span>
        <select
          value={role}
          onChange={(event) => setRole(event.target.value)}
          className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none sm:w-auto"
        >
          <option value="">Semua Pengguna</option>
          {ROLE_OPTIONS.map((value) => (
            <option key={value} value={value}>
              {value}
            </option>
          ))}
        </select>
        <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
          {icons.chevronDown}
        </NavIcon>
      </label>

      <button
        type="button"
        onClick={() => setIsModalOpen(true)}
        className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-6 py-2 text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover sm:w-auto"
      >
        Tambah Pengguna
        <NavIcon size={14}>{icons.plus}</NavIcon>
      </button>

      <AddPenggunaModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </div>
  );
}
