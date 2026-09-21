export type AttendanceStatus = "Hadir" | "Izin" | "Sakit" | "Alpa";

const STATUS_STYLES: Record<AttendanceStatus, string> = {
  Hadir: "bg-mint text-on-mint",
  Izin: "bg-hover text-muted",
  Sakit: "bg-warn text-on-warn",
  Alpa: "bg-bad text-on-bad",
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
