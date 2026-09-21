import { PageHeader } from "./_components/PageHeader";
import { FilterBar } from "./_components/FilterBar";
import { AttendanceTable } from "./_components/AttendanceTable";

export default function PresensiSiswaPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <FilterBar />
      <AttendanceTable />
    </div>
  );
}
