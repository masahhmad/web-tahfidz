"use client";

import { useState, type FormEvent } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { useModalA11y } from "../../_components/useModalA11y";

const CLASS_OPTIONS = ["7A", "7B", "8A", "8B", "9A", "9B"];

const CSV_TEMPLATE = ["nama_siswa,kelas,nisn,jumlah_hafalan", "Ahmad Rasyid,7A,0987654321,15 Juz", ""].join("\n");

function downloadCsvTemplate() {
  const blob = new Blob([CSV_TEMPLATE], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = "template-data-siswa.csv";
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

const fieldLabel = "text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted";
const fieldControl =
  "w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none placeholder:text-soft";

/* -------------------------------------------------------------------------
 * AddSiswaModal — "Tambah Siswa" popup for the data-siswa page. Same dialog
 * a11y contract as the other modals (useModalA11y). Unlike the Figma frame
 * every control has a visible <label>; the text the design showed inside
 * each control is used as its placeholder. The method select toggles
 * between the manual fields and a CSV upload.
 * ---------------------------------------------------------------------- */
export function AddSiswaModal({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const panelRef = useModalA11y(isOpen, onClose);
  const [method, setMethod] = useState<"manual" | "csv">("manual");
  const [name, setName] = useState("");
  const [className, setClassName] = useState("");
  const [nisn, setNisn] = useState("");
  const [hafalan, setHafalan] = useState("");

  if (!isOpen) return null;

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    onClose();
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      onClick={onClose}
    >
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-siswa-title"
        className="flex max-h-[90dvh] w-full max-w-md flex-col overflow-y-auto rounded-xl bg-modal p-6"
        onClick={(event) => event.stopPropagation()}
      >
        <h2
          id="add-siswa-title"
          className="pb-3 text-center text-[18px] leading-6 font-semibold uppercase text-ink"
        >
          Tambah Siswa
        </h2>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <label className="flex flex-col gap-1.5">
            <span className={fieldLabel}>Metode Input</span>
            <div className="relative">
              <select
                value={method}
                onChange={(event) => setMethod(event.target.value as "manual" | "csv")}
                className={`${fieldControl} appearance-none pr-10`}
              >
                <option value="manual">Masukkan Manual</option>
                <option value="csv">Upload CSV</option>
              </select>
              <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                {icons.chevronDown}
              </NavIcon>
            </div>
          </label>

          {method === "manual" ? (
            <>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label className="flex flex-col gap-1.5">
                  <span className={fieldLabel}>Nama Siswa</span>
                  <input
                    type="text"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder="Nama Siswa"
                    className={fieldControl}
                  />
                </label>

                <label className="flex flex-col gap-1.5">
                  <span className={fieldLabel}>Kelas</span>
                  <div className="relative">
                    <select
                      value={className}
                      onChange={(event) => setClassName(event.target.value)}
                      className={`${fieldControl} appearance-none pr-10 ${className === "" ? "text-soft" : ""}`}
                    >
                      <option value="" disabled>
                        Kelas
                      </option>
                      {CLASS_OPTIONS.map((value) => (
                        <option key={value} value={value} className="text-ink">
                          Kelas {value}
                        </option>
                      ))}
                    </select>
                    <NavIcon className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-icon">
                      {icons.chevronDown}
                    </NavIcon>
                  </div>
                </label>
              </div>

              <label className="flex flex-col gap-1.5">
                <span className={fieldLabel}>NISN</span>
                <input
                  type="text"
                  inputMode="numeric"
                  value={nisn}
                  onChange={(event) => setNisn(event.target.value)}
                  placeholder="NISN"
                  className={fieldControl}
                />
              </label>

              <label className="flex flex-col gap-1.5">
                <span className={fieldLabel}>Jumlah Hafalan</span>
                <input
                  type="text"
                  value={hafalan}
                  onChange={(event) => setHafalan(event.target.value)}
                  placeholder="Jumlah Hafalan"
                  className={fieldControl}
                />
              </label>
            </>
          ) : (
            <div className="flex flex-col gap-1.5">
              <div className="flex items-center justify-between gap-3">
                <span className={fieldLabel}>File CSV</span>
                <button
                  type="button"
                  onClick={downloadCsvTemplate}
                  className="inline-flex items-center gap-1.5 rounded-md text-[12px] leading-4 font-semibold tracking-[0.6px] text-brand hover:underline"
                >
                  <NavIcon size={14}>{icons.download}</NavIcon>
                  Unduh Template
                </button>
              </div>
              <label className="flex flex-col">
                <span className="sr-only">Pilih file CSV</span>
                <input
                  type="file"
                  accept=".csv"
                  className="w-full rounded-lg border border-line bg-field px-[13px] py-[9px] text-[14px] leading-5 text-muted outline-none file:mr-3 file:rounded-md file:border-0 file:bg-hover file:px-3 file:py-1 file:text-[12px] file:font-semibold file:text-ink"
                />
              </label>
            </div>
          )}

          <div className="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onClose}
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
      </div>
    </div>
  );
}
