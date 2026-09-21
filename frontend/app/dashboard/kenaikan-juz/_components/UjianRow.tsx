"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { EditScoreModal } from "./EditScoreModal";
import type { UjianRecord } from "./UjianTable";

/* Mock of the currently logged-in teacher until real auth/session exists.
 * Swap this for the actual session user once auth is wired up. */
const CURRENT_TEACHER = "Ahmad";

function ScoreValue({ value }: { value: number | null }) {
  return value !== null ? (
    <span className="text-muted">{value}</span>
  ) : (
    <span className="text-soft">Belum ada nilai</span>
  );
}

/* -------------------------------------------------------------------------
 * UjianRow — one student row. Nilai Hafalan/Soal are plain read-only text;
 * the only action is the Aksi pencil, which opens EditScoreModal. Which
 * inputs that modal shows depends on the current teacher's role for this
 * student: the pengampu can grade Hafalan, the assigned soal examiner can
 * grade Soal — a teacher who is both (grading their own halaqah student)
 * gets both fields at once.
 * ---------------------------------------------------------------------- */
export function UjianRow({ record }: { record: UjianRecord }) {
  const [hafalanScore, setHafalanScore] = useState(record.hafalanScore);
  const [soalScore, setSoalScore] = useState(record.soalScore);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const canInputHafalan = CURRENT_TEACHER === record.pengampu;
  const canInputSoal = CURRENT_TEACHER === record.soalExaminer;

  return (
    <tr className="border-b border-divider last:border-b-0">
      <td className="px-6 py-2.5 text-left text-[14px] leading-5 text-muted">{record.no}</td>
      <td className="px-6 py-2.5 text-[14px] leading-5 font-semibold text-ink whitespace-nowrap">
        {record.studentName}
      </td>
      <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.className}</td>
      <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.juz}</td>
      <td className="px-6 py-2.5 text-[14px] leading-5 whitespace-nowrap">
        <ScoreValue value={hafalanScore} />
      </td>
      <td className="px-6 py-2.5 text-[14px] leading-5 whitespace-nowrap">
        <ScoreValue value={soalScore} />
      </td>
      <td className="px-6 py-2.5">
        <div className="flex items-center justify-center">
          <button
            type="button"
            onClick={() => setIsModalOpen(true)}
            aria-label={`Ubah nilai ${record.studentName}`}
            className="inline-flex items-center justify-center rounded-md p-1.5 text-muted hover:bg-hover"
          >
            <NavIcon>{icons.edit}</NavIcon>
          </button>
        </div>

        <EditScoreModal
          key={isModalOpen ? "open" : "closed"}
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          studentName={record.studentName}
          showHafalan={canInputHafalan}
          showSoal={canInputSoal}
          initialHafalan={hafalanScore}
          initialSoal={soalScore}
          onSave={(next) => {
            if (next.hafalan !== undefined) setHafalanScore(next.hafalan);
            if (next.soal !== undefined) setSoalScore(next.soal);
            setIsModalOpen(false);
          }}
        />
      </td>
    </tr>
  );
}
