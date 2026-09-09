"use client";

import { useState } from "react";
import { AddPresensiGuruModal } from "./AddPresensiGuruModal";

const SESSION_OPTIONS = [
  { value: "pagi", label: "Sesi Pagi" },
  { value: "siang", label: "Sesi Siang" },
  { value: "sore", label: "Sesi Sore" },
] as const;

/* -------------------------------------------------------------------------
 * FilterBar — date/time + session filters plus the "Buat Presensi" action,
 * which opens AddPresensiGuruModal for the currently selected session.
 * Stacks vertically on mobile/tablet, sits in one row from sm up.
 * ---------------------------------------------------------------------- */
export function FilterBar() {
  const [session, setSession] = useState<(typeof SESSION_OPTIONS)[number]["value"]>("pagi");
  const [isModalOpen, setIsModalOpen] = useState(false);

  const sessionLabel = SESSION_OPTIONS.find((option) => option.value === session)?.label ?? "Sesi Pagi";

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
          <span className="sr-only">Sesi</span>
          <select
            value={session}
            onChange={(event) => setSession(event.target.value as (typeof SESSION_OPTIONS)[number]["value"])}
            className="w-full min-w-[100px] bg-transparent text-[14px] leading-5 text-[#191c1d] outline-none sm:w-auto"
          >
            {SESSION_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
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

      <AddPresensiGuruModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} sessionName={sessionLabel} />
    </div>
  );
}
