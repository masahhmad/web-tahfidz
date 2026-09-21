import { PaginationFooter } from "../../_components/PaginationFooter";
import { UjianRow } from "./UjianRow";

export type UjianRecord = {
  no: number;
  studentName: string;
  className: string;
  juz: number;
  hafalanScore: number | null;
  soalScore: number | null;
  /** The student's own guru pengampu — grades Nilai Hafalan. */
  pengampu: string;
  /** Teacher assigned to examine this student's Nilai Soal (may be the
   * same person as `pengampu`, e.g. testing their own halaqah student). */
  soalExaminer: string;
};

const COLUMNS = ["No", "Nama Siswa", "Kelas", "Juz", "Nilai Hafalan", "Nilai Soal", "Aksi"] as const;

const RECORDS: UjianRecord[] = [
  {
    no: 1,
    studentName: "Ahmad Rasyid",
    className: "7A",
    juz: 1,
    hafalanScore: 85,
    soalScore: 85,
    pengampu: "Ustadz Zaid",
    soalExaminer: "Ahmad",
  },
  {
    no: 2,
    studentName: "Bilal Ramadhan",
    className: "7A",
    juz: 1,
    hafalanScore: null,
    soalScore: null,
    pengampu: "Ahmad",
    soalExaminer: "Ahmad",
  },
];

/* -------------------------------------------------------------------------
 * UjianTable — semantic <table> so screen readers get real row/column
 * relationships; wrapped in its own overflow-x-auto so the wide table
 * scrolls horizontally on mobile/tablet instead of squeezing columns.
 * Nilai Hafalan/Soal are plain read-only text ("Belum ada nilai" or the
 * score) — the only action is each row's Aksi pencil (see UjianRow), which
 * opens a popup with the input(s) that teacher is allowed to grade.
 * ---------------------------------------------------------------------- */
export function UjianTable() {
  return (
    <div className="w-full overflow-hidden rounded-xl border border-card-edge bg-card shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[820px] border-collapse text-center">
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
              <UjianRow key={record.no} record={record} />
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Riwayat setoran halaqah Ustadz Zaid" currentPage={1} totalPages={6} />
    </div>
  );
}
