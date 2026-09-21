export function PageHeader() {
  return (
    <div className="flex w-full flex-col gap-2 pb-2">
      <h2 className="text-[28px] leading-[36px] font-bold tracking-[-0.64px] text-ink sm:text-[32px] sm:leading-10">
        Presensi Siswa
      </h2>
      <p className="text-[16px] leading-6 text-muted">
        Input kehadiran siswa untuk kegiatan tahfidz hari ini.
      </p>
    </div>
  );
}
