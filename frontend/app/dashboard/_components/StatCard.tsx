import { NavIcon, icons, type IconName } from "./icons";

/* -------------------------------------------------------------------------
 * StatCard — the two "Main container" cards in the workspace.
 * ---------------------------------------------------------------------- */
export function StatCard({
  variant,
  label,
  value,
  trendLabel,
  trendIcon,
}: {
  variant: "dark" | "light";
  label: string;
  value: number;
  trendLabel: string;
  trendIcon: IconName;
}) {
  const isDark = variant === "dark";
  return (
    <div
      className={`relative flex flex-col gap-4 overflow-hidden rounded-xl p-6 shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)] ${
        isDark ? "bg-[#003527]" : "bg-white"
      }`}
    >
      {isDark && (
        <div aria-hidden="true" className="absolute -right-10 -bottom-10 size-32 rounded-full bg-[#064e3b] blur-[20px]" />
      )}
      <div className="relative flex items-center justify-between">
        <p className={`text-[12px] leading-4 font-semibold tracking-[0.6px] ${isDark ? "text-white" : "text-[#191c1d]"}`}>
          {label}
        </p>
        <NavIcon size={16} className={isDark ? "text-[#95d3ba]" : "text-[#404944]"}>
          {icons[trendIcon]}
        </NavIcon>
      </div>
      <p className={`relative text-[42px] leading-10 font-bold tracking-[-0.64px] ${isDark ? "text-white" : "text-[#191c1d]"}`}>
        {value}
      </p>
      <div className="relative flex items-center gap-2.5">
        <NavIcon size={10} className={isDark ? "text-[#95d3ba]" : "text-[#191c1d]"}>
          {icons[trendIcon]}
        </NavIcon>
        <p className={`text-[12px] leading-4 tracking-[0.6px] ${isDark ? "text-[#95d3ba]" : "text-[#191c1d]"}`}>
          {trendLabel}
        </p>
      </div>
    </div>
  );
}
