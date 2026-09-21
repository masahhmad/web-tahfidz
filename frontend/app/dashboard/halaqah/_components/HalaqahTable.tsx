import { PaginationFooter } from "../../_components/PaginationFooter";

export type HalaqahStudent = {
  no: number;
  name: string;
  className: string;
  nisn: string;
  hafalan: string;
  pengampu: string;
};

const STUDENTS: HalaqahStudent[] = [
  { no: 1, name: "Ahmad Rasyid", className: "7A", nisn: "0987654321", hafalan: "15 Juz", pengampu: "Ustadz Zaid" },
];

/* -------------------------------------------------------------------------
 * HalaqahTable — semantic <table>, wrapped in overflow-x-auto so it scrolls
 * horizontally on mobile/tablet instead of squeezing columns. The Figma
 * frame showed this table in two states (read-only "Guru Pengampu" column
 * vs. an assignment mode with a checkbox column); this consolidates them
 * into one table whose last column switches based on `isAssigning`.
 * ---------------------------------------------------------------------- */
export function HalaqahTable({
  isAssigning,
  selectedIds,
  onToggleStudent,
}: {
  isAssigning: boolean;
  selectedIds: Set<number>;
  onToggleStudent: (id: number) => void;
}) {
  const columns = ["No", "Nama Siswa", "Kelas", "NISN", "Jumlah Hafalan", isAssigning ? "Pilih Siswa" : "Guru Pengampu"];

  return (
    <div className="w-full overflow-hidden rounded-xl border border-card-edge bg-card shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[820px] border-collapse text-center">
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
            {STUDENTS.map((student) => (
              <tr key={student.no} className="border-b border-divider last:border-b-0">
                <td className="px-6 py-2.5 text-left text-[14px] leading-5 text-muted">{student.no}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 font-semibold text-ink whitespace-nowrap">
                  {student.name}
                </td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{student.className}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{student.nisn}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{student.hafalan}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">
                  {isAssigning ? (
                    <input
                      type="checkbox"
                      checked={selectedIds.has(student.no)}
                      onChange={() => onToggleStudent(student.no)}
                      aria-label={`Pilih ${student.name}`}
                      className="size-[14px] rounded-[2px] border border-brand accent-brand"
                    />
                  ) : (
                    student.pengampu
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Data Seluruh Siswa" currentPage={1} totalPages={6} />
    </div>
  );
}
