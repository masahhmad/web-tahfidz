import { StatusBadge, type AttendanceStatus } from "../../_components/StatusBadge";
import { PaginationFooter } from "../../_components/PaginationFooter";

export type AttendanceRecord = {
  no: number;
  studentName: string;
  halaqah: string;
  date: string;
  session: string;
  status: AttendanceStatus;
  note: string;
};

const COLUMNS = ["No", "Nama Siswa", "Halaqah", "Tanggal", "Sesi", "Status", "Keterangan"] as const;

const RECORDS: AttendanceRecord[] = [
  {
    no: 1,
    studentName: "Ahmad Fauzan",
    halaqah: "Al-Fatih",
    date: "10/8/2026",
    session: "Pagi",
    status: "Hadir",
    note: "-",
  },
];

/* -------------------------------------------------------------------------
 * AttendanceTable — semantic <table> so screen readers get real row/column
 * relationships; wrapped in its own overflow-x-auto so the wide table
 * scrolls horizontally on mobile/tablet instead of squeezing columns or
 * breaking the page layout.
 * ---------------------------------------------------------------------- */
export function AttendanceTable() {
  return (
    <div className="w-full overflow-hidden rounded-xl border border-white bg-white shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[720px] border-collapse text-left">
          <thead className="bg-white/50">
            <tr>
              {COLUMNS.map((label) => (
                <th
                  key={label}
                  scope="col"
                  className="border-b border-[rgba(225,227,228,0.5)] px-6 py-4 text-[12px] leading-4 font-semibold tracking-[0.6px] text-[#404944] whitespace-nowrap"
                >
                  {label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {RECORDS.map((record) => (
              <tr key={record.no} className="border-b border-[#eff0f1] last:border-b-0">
                <td className="px-6 py-2.5 text-[14px] leading-5 text-[#404944]">{record.no}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-[#191c1d] whitespace-nowrap">{record.studentName}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-[#404944] whitespace-nowrap">{record.halaqah}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-[#191c1d] whitespace-nowrap">{record.date}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-[#404944] whitespace-nowrap">{record.session}</td>
                <td className="px-6 py-2.5">
                  <StatusBadge status={record.status} />
                </td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-[#404944]">{record.note}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Menampilkan 10 presensi sebelumnya" currentPage={1} totalPages={8} />
    </div>
  );
}
