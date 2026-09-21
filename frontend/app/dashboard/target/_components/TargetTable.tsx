"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { PaginationFooter } from "../../_components/PaginationFooter";
import { useCanManageMasterData } from "../../_components/session";
import { SetTargetModal } from "./SetTargetModal";

export type TargetRecord = {
  no: number;
  className: string;
  /** Total juz the class must reach this year; null until it is set. */
  target: number | null;
};

const COLUMNS = ["No", "Kelas", "Target Hafalan", "Aksi"] as const;

const INITIAL_RECORDS: TargetRecord[] = [{ no: 1, className: "Kelas 7A-1", target: null }];

/* -------------------------------------------------------------------------
 * TargetTable — semantic <table> so screen readers get real row/column
 * relationships; wrapped in its own overflow-x-auto so it scrolls
 * horizontally on mobile/tablet instead of squeezing columns. A class with
 * no target set shows a muted "Belum Diatur" instead of a value; the pencil
 * opens SetTargetModal to set that class's juz target for the year. The
 * Aksi column is not shown to a read-only super admin.
 * ---------------------------------------------------------------------- */
export function TargetTable() {
  const [records, setRecords] = useState(INITIAL_RECORDS);
  const [editingNo, setEditingNo] = useState<number | null>(null);
  const canManage = useCanManageMasterData();
  const columns = canManage ? COLUMNS : COLUMNS.filter((label) => label !== "Aksi");

  const editing = records.find((record) => record.no === editingNo) ?? null;

  function handleSave(target: number) {
    setRecords((current) =>
      current.map((record) => (record.no === editingNo ? { ...record, target } : record)),
    );
    setEditingNo(null);
  }

  return (
    <div className="w-full overflow-hidden rounded-xl border border-card-edge bg-card shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[600px] border-collapse text-center">
          <thead className="bg-card/50">
            <tr>
              {columns.map((label, index) => (
                <th
                  key={label}
                  scope="col"
                  className={`border-b border-line/50 px-6 py-4 text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted whitespace-nowrap ${
                    index === 0 ? "w-[100px] text-left" : ""
                  }`}
                >
                  {label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {records.map((record) => (
              <tr key={record.no} className="border-b border-divider last:border-b-0">
                <td className="px-6 py-2.5 text-left text-[14px] leading-5 text-muted">{record.no}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 font-semibold text-ink whitespace-nowrap">
                  {record.className}
                </td>
                <td className="px-6 py-2.5 text-[14px] leading-5 whitespace-nowrap">
                  {record.target !== null ? (
                    <span className="text-muted">{record.target} Juz</span>
                  ) : (
                    <span className="text-soft">Belum Diatur</span>
                  )}
                </td>
                {canManage && (
                  <td className="px-6 py-2.5">
                    <button
                      type="button"
                      onClick={() => setEditingNo(record.no)}
                      aria-label={`Atur target hafalan ${record.className}`}
                      className="inline-flex items-center justify-center rounded-md p-1.5 text-brand hover:bg-hover"
                    >
                      <NavIcon>{icons.edit}</NavIcon>
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Data Seluruh Siswa" currentPage={1} totalPages={6} />

      {canManage && editing && (
        <SetTargetModal
          key={editing.no}
          isOpen
          onClose={() => setEditingNo(null)}
          className={editing.className}
          initialTarget={editing.target}
          onSave={handleSave}
        />
      )}
    </div>
  );
}
