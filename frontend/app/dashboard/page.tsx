import { StatCard } from "./_components/StatCard";

export default function DashboardPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <div className="flex items-end pb-2">
        <div className="flex flex-col gap-2">
          <h2 className="text-[28px] leading-[36px] font-bold tracking-[-0.64px] text-[#191c1d] sm:text-[32px] sm:leading-10">
            Dashboard
          </h2>
          <p className="text-[16px] leading-6 text-[#404944]">Rencanakan, prioritaskan, dan kembangkan</p>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard variant="dark" label="Hafalan Tercapai" value={128} trendLabel="Bertambah dari bulan lalu" trendIcon="trendUp" />
        <StatCard variant="light" label="Hafalan Belum Tercapai" value={46} trendLabel="Berkurang dari bulan lalu" trendIcon="trendDown" />
      </div>
    </div>
  );
}
