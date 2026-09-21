"use client";

import { NavIcon, icons } from "../../_components/icons";
import { PaginationFooter } from "../../_components/PaginationFooter";
import { useCanManageMasterData } from "../../_components/session";

export type SiswaRecord = {
  no: number;
  name: string;
  className: string;
  nisn: string;
  hafalan: string;
};

const COLUMNS = ["No", "Nama Siswa", "Kelas", "NISN", "Jumlah Hafalan", "Laporan Siswa", "Aksi"] as const;

const RECORDS: SiswaRecord[] = [
  { no: 1, name: "Ahmad Rasyid", className: "7A", nisn: "0987654321", hafalan: "15 Juz" },
];

/* -------------------------------------------------------------------------
 * DataSiswaTable — semantic <table> so screen readers get real row/column
 * relationships; wrapped in its own overflow-x-auto so the wide table
 * scrolls horizontally on mobile/tablet instead of squeezing columns.
 * The Aksi column (edit / delete) is not shown to a read-only super admin.
 * ---------------------------------------------------------------------- */
export function DataSiswaTable() {
  const canManage = useCanManageMasterData();
  const columns = canManage ? COLUMNS : COLUMNS.filter((label) => label !== "Aksi");

  return (
    <div className="w-full overflow-hidden rounded-xl border border-card-edge bg-card shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[880px] border-collapse text-center">
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
            {RECORDS.map((record) => (
              <tr key={record.no} className="border-b border-divider last:border-b-0">
                <td className="px-6 py-2.5 text-left text-[14px] leading-5 text-muted">{record.no}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 font-semibold text-ink whitespace-nowrap">
                  {record.name}
                </td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.className}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.nisn}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.hafalan}</td>
                <td className="px-6 py-2.5 whitespace-nowrap">
                  <button
                    type="button"
                    className="inline-flex items-center gap-1.5 text-[14px] leading-5 text-muted hover:text-ink"
                  >
                    Lihat rekap laporan
                    <NavIcon size={14}>{icons.externalLink}</NavIcon>
                  </button>
                </td>
                {canManage && (
                  <td className="px-6 py-2.5">
                    <div className="flex items-center justify-center gap-2">
                      <button
                        type="button"
                        aria-label={`Ubah data ${record.name}`}
                        className="inline-flex items-center justify-center rounded-md p-1.5 text-muted hover:bg-hover"
                      >
                        <NavIcon>{icons.edit}</NavIcon>
                      </button>
                      <button
                        type="button"
                        aria-label={`Hapus data ${record.name}`}
                        className="inline-flex items-center justify-center rounded-md p-1.5 text-muted hover:bg-hover"
                      >
                        <NavIcon>{icons.trash}</NavIcon>
                      </button>
                    </div>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Data Seluruh Siswa" currentPage={1} totalPages={6} />
    </div>
  );
}
