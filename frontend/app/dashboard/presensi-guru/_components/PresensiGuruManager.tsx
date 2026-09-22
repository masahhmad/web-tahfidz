"use client";

import { useState } from "react";
import { AttendanceTable, type AttendanceRecord } from "./AttendanceTable";
import { FilterBar } from "./FilterBar";

const INITIAL_RECORDS: AttendanceRecord[] = [
  { no: 1, date: "10/8/2026", session: "Pagi", status: "Hadir", time: "05.00", location: "-6.200000,106.800000", note: "-" },
];

/* Placeholder formatters until the API exists: the real date and time come
 * from the server timestamp (`created_at`), not from the browser. */
function formatDate(date: Date) {
  return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
}

function formatTime(date: Date) {
  return `${String(date.getHours()).padStart(2, "0")}.${String(date.getMinutes()).padStart(2, "0")}`;
}

/* -------------------------------------------------------------------------
 * PresensiGuruManager — owns the presensi list so a presensi saved from the
 * filter bar's modal (which captures the user's coordinates) shows up in the
 * table right away, newest first.
 * ---------------------------------------------------------------------- */
export function PresensiGuruManager() {
  const [records, setRecords] = useState(INITIAL_RECORDS);

  return (
    <>
      <FilterBar
        onCreate={(presensi) => {
          const now = new Date();
          setRecords((current) => [
            {
              no: current.length + 1,
              date: formatDate(now),
              session: presensi.session,
              status: presensi.status,
              time: formatTime(now),
              location: presensi.lokasi,
              note: presensi.note || "-",
            },
            ...current,
          ]);
        }}
      />
      <AttendanceTable records={records} />
    </>
  );
}
