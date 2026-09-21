import { NavIcon, icons } from "../../_components/icons";
import { PaginationFooter } from "../../_components/PaginationFooter";

export type SetoranRecord = {
  no: number;
  studentName: string;
  className: string;
  juz: number;
  amount: string;
};

const COLUMNS = ["No", "Nama Siswa", "Kelas", "Juz", "Jumlah Setoran", "Aksi"] as const;

const RECORDS: SetoranRecord[] = [
  { no: 1, studentName: "Ahmad Rasyid", className: "7A", juz: 1, amount: "30 baris" },
];

/* -------------------------------------------------------------------------
 * SetoranTable — semantic <table> so screen readers get real row/column
 * relationships; wrapped in its own overflow-x-auto so the wide table
 * scrolls horizontally on mobile/tablet instead of squeezing columns or
 * breaking the page layout.
 * ---------------------------------------------------------------------- */
export function SetoranTable() {
  return (
    <div className="w-full overflow-hidden rounded-xl border border-card-edge bg-card shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[720px] border-collapse text-center">
          <thead className="bg-card/50">
            <tr>
              {COLUMNS.map((label, index) => (
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
                  {record.studentName}
                </td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.className}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.juz}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.amount}</td>
                <td className="px-6 py-2.5">
                  <button
                    type="button"
                    aria-label={`Ubah setoran ${record.studentName}`}
                    className="inline-flex items-center justify-center rounded-md p-1.5 text-muted hover:bg-hover"
                  >
                    <NavIcon>{icons.edit}</NavIcon>
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Riwayat setoran halaqah Ustadz Zaid" currentPage={1} totalPages={6} />
    </div>
  );
}
