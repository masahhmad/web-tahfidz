"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { AddSiswaModal } from "./AddSiswaModal";

export function PageHeader() {
  const [isModalOpen, setIsModalOpen] = useState(false);

  return (
    <div className="flex w-full flex-col gap-4 pb-2 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex flex-col gap-2">
        <h2 className="text-[28px] leading-[36px] font-bold tracking-[-0.64px] text-ink sm:text-[32px] sm:leading-10">
          Ujian Kenaikan Juz
        </h2>
        <p className="text-[16px] leading-6 text-muted">Input ujian kenaikan juz kegiatan tahfidz.</p>
      </div>

      <button
        type="button"
        onClick={() => setIsModalOpen(true)}
        className="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand px-6 py-2 text-[12px] leading-5 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover"
      >
        Tambah Siswa
        <NavIcon size={14}>{icons.plus}</NavIcon>
      </button>

      <AddSiswaModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </div>
  );
}
