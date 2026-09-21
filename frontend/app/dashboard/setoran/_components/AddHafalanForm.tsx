"use client";

import { useState, type FormEvent } from "react";
import { NavIcon, icons } from "../../_components/icons";

const STUDENTS = ["Ahmad Rasyid", "Bilal Ramadhan", "Fathimah Azzahra"] as const;
const JUZ_OPTIONS = Array.from({ length: 30 }, (_, i) => i + 1);

/* -------------------------------------------------------------------------
 * AddHafalanForm — inline "Tambah Hafalan" card above the setoran table.
 * Fields stack on mobile/tablet and sit in one row from sm up; the
 * Batal/Simpan actions are right-aligned on desktop, full-width stacked on
 * mobile.
 * ---------------------------------------------------------------------- */
export function AddHafalanForm() {
  const [student, setStudent] = useState("");
  const [rows, setRows] = useState("");
  const [juz, setJuz] = useState("");

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
  }

  function handleReset() {
    setStudent("");
    setRows("");
    setJuz("");
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex w-full flex-col gap-3 rounded-xl bg-card p-5 shadow-[0px_4px_10px_0px_rgba(0,0,0,0.03)]"
    >
      <h3 className="text-[20px] leading-7 font-bold text-ink">Tambah Hafalan</h3>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
        <label className="flex flex-1 flex-col gap-1.5">
          <span className="sr-only">Nama Siswa</span>
          <div className="relative">
            <select
              value={student}
              onChange={(event) => setStudent(event.target.value)}
              className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none"
            >
              <option value="" disabled>
                Nama Siswa
              </option>
              {STUDENTS.map((name) => (
                <option key={name} value={name}>
                  {name}
                </option>
              ))}
            </select>
            <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
              {icons.chevronDown}
            </NavIcon>
          </div>
        </label>

        <label className="flex flex-1 flex-col gap-1.5">
          <span className="sr-only">Jumlah Baris</span>
          <input
            type="text"
            inputMode="numeric"
            value={rows}
            onChange={(event) => setRows(event.target.value)}
            placeholder="Baris"
            className="w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft"
          />
        </label>

        <label className="flex flex-col gap-1.5 sm:w-[200px]">
          <span className="sr-only">Juz</span>
          <div className="relative">
            <select
              value={juz}
              onChange={(event) => setJuz(event.target.value)}
              className="w-full appearance-none rounded-lg border border-line bg-field py-[9px] pr-10 pl-[13px] text-[14px] leading-5 text-ink outline-none"
            >
              <option value="" disabled>
                Juz
              </option>
              {JUZ_OPTIONS.map((value) => (
                <option key={value} value={value}>
                  Juz {value}
                </option>
              ))}
            </select>
            <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
              {icons.chevronDown}
            </NavIcon>
          </div>
        </label>
      </div>

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <button
          type="button"
          onClick={handleReset}
          className="rounded-lg border border-line-strong bg-transparent px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-ink hover:bg-hover sm:w-[100px]"
        >
          Batal
        </button>
        <button
          type="submit"
          className="rounded-lg bg-brand px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover sm:w-[100px]"
        >
          Simpan
        </button>
      </div>
    </form>
  );
}
