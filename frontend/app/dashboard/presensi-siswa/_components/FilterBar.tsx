"use client";

import { useState } from "react";
import { AddPresensiModal, type PresensiSubject } from "../../_components/AddPresensiModal";

const STUDENTS: PresensiSubject[] = [
  { id: "1", name: "Ahmad Fauzan", meta: "Halaqah Al-Fatih" },
  { id: "2", name: "Bilal Ramadhan", meta: "Halaqah Al-Fatih" },
  { id: "3", name: "Fathimah Azzahra", meta: "Halaqah An-Nur" },
];

/* -------------------------------------------------------------------------
 * FilterBar — date/time + halaqah filters plus the "Buat Presensi" action,
 * which opens the shared AddPresensiModal. Stacks vertically on
 * mobile/tablet, sits in one row from sm up.
 * ---------------------------------------------------------------------- */
export function FilterBar() {
  const [isModalOpen, setIsModalOpen] = useState(false);

  return (
    <div className="flex w-full flex-col items-stretch gap-4 rounded-xl bg-white p-5 shadow-[0px_4px_10px_0px_rgba(0,0,0,0.03)] sm:flex-row sm:items-center sm:justify-between">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center">
        <label className="flex items-center rounded-lg border border-[#e1e3e4] bg-white px-[13px] py-[9px]">
          <span className="sr-only">Tanggal dan waktu presensi</span>
          <input
            type="datetime-local"
            className="w-full min-w-0 bg-transparent text-[14px] leading-5 text-[#191c1d] outline-none sm:w-auto"
          />
        </label>

        <label className="flex items-center rounded-lg border border-[#e1e3e4] bg-white px-[13px] py-[9px]">
          <span className="sr-only">Halaqah</span>
          <select
            defaultValue="semua"
            className="w-full min-w-[100px] bg-transparent text-[14px] leading-5 text-[#191c1d] outline-none sm:w-auto"
          >
            <option value="semua">Semua Halaqah</option>
            <option value="al-fatih">Halaqah Al-Fatih</option>
            <option value="an-nur">Halaqah An-Nur</option>
          </select>
        </label>
      </div>

      <button
        type="button"
        onClick={() => setIsModalOpen(true)}
        className="rounded-lg bg-[#003527] px-8 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-white shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-[#064e3b]"
      >
        Buat Presensi
      </button>

      <AddPresensiModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        subjectLabel="Siswa"
        subjects={STUDENTS}
      />
    </div>
  );
}
