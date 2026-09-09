export type AttendanceStatus = "Hadir" | "Izin" | "Sakit" | "Alpa";

const STATUS_STYLES: Record<AttendanceStatus, string> = {
  Hadir: "bg-[#6cf8bb] text-[#00714d]",
  Izin: "bg-[#edeeef] text-[#404944]",
  Sakit: "bg-[#fdecc8] text-[#8a5a00]",
  Alpa: "bg-[#fbd1d1] text-[#a01818]",
};

export function StatusBadge({ status }: { status: AttendanceStatus }) {
  return (
    <span
      className={`inline-flex items-center rounded-full border border-[rgba(191,201,195,0.3)] px-[17px] py-[6.5px] text-[12px] leading-4 font-semibold tracking-[0.6px] whitespace-nowrap ${STATUS_STYLES[status]}`}
    >
      {status}
    </span>
  );
}
