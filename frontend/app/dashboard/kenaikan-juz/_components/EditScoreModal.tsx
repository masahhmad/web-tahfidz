"use client";

import { useState, type FormEvent } from "react";
import { createPortal } from "react-dom";
import { useModalA11y } from "../../_components/useModalA11y";

/* -------------------------------------------------------------------------
 * EditScoreModal — popup opened from a student's Aksi/pencil button. Which
 * of its two inputs (Nilai Hafalan / Nilai Soal) appear depends on the
 * current teacher's role for that student: the pengampu can grade Hafalan,
 * the assigned examiner can grade Soal, and a teacher who is both (their
 * own halaqah student) gets both fields — see UjianRow for that logic.
 *
 * Rendered through a portal since it's opened from inside a <tr>/<td>: a
 * fixed-position overlay can't be a direct DOM child of a table row without
 * the browser hoisting it out and causing a hydration mismatch.
 * ---------------------------------------------------------------------- */
export function EditScoreModal({
  isOpen,
  onClose,
  studentName,
  showHafalan,
  showSoal,
  initialHafalan,
  initialSoal,
  onSave,
}: {
  isOpen: boolean;
  onClose: () => void;
  studentName: string;
  showHafalan: boolean;
  showSoal: boolean;
  initialHafalan: number | null;
  initialSoal: number | null;
  onSave: (next: { hafalan?: number; soal?: number }) => void;
}) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [hafalan, setHafalan] = useState(initialHafalan !== null ? String(initialHafalan) : "");
  const [soal, setSoal] = useState(initialSoal !== null ? String(initialSoal) : "");

  if (!isOpen || typeof document === "undefined") return null;

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const next: { hafalan?: number; soal?: number } = {};

    if (showHafalan && hafalan.trim() !== "") {
      const parsed = Number(hafalan);
      if (!Number.isNaN(parsed)) next.hafalan = parsed;
    }
    if (showSoal && soal.trim() !== "") {
      const parsed = Number(soal);
      if (!Number.isNaN(parsed)) next.soal = parsed;
    }

    onSave(next);
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-score-title"
        className="flex w-full max-w-xs flex-col rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="edit-score-title"
          className="text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Masukkan Nilai
        </h2>
        <p className="pb-3 text-center text-[14px] leading-5 text-muted">{studentName}</p>

        {showHafalan || showSoal ? (
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            {showHafalan && (
              <label htmlFor="nilai-hafalan" className="flex flex-col gap-1.5">
                <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted">
                  Nilai Hafalan <span className="font-normal text-soft">(0-100)</span>
                </span>
                <input
                  id="nilai-hafalan"
                  type="number"
                  min={0}
                  max={100}
                  value={hafalan}
                  onChange={(event) => setHafalan(event.target.value)}
                  placeholder="Masukkan Nilai"
                  title="Nilai Hafalan (0-100)"
                  className="w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft"
                />
              </label>
            )}

            {showSoal && (
              <label htmlFor="nilai-soal" className="flex flex-col gap-1.5">
                <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted">
                  Nilai Soal <span className="font-normal text-soft">(0-100)</span>
                </span>
                <input
                  id="nilai-soal"
                  type="number"
                  min={0}
                  max={100}
                  value={soal}
                  onChange={(event) => setSoal(event.target.value)}
                  placeholder="Masukkan Nilai"
                  title="Nilai Soal (0-100)"
                  className="w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft"
                />
              </label>
            )}

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
        ) : (
          <div className="flex flex-col gap-4">
            <p className="text-center text-[14px] leading-5 text-muted">
              Anda tidak berwenang menginput nilai untuk siswa ini.
            </p>
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-line-strong bg-transparent px-6 py-[9px] text-center text-[12px] leading-5 font-semibold tracking-[0.6px] text-ink hover:bg-hover"
            >
              Tutup
            </button>
          </div>
        )}
      </div>
    </div>,
    document.body,
  );
}
