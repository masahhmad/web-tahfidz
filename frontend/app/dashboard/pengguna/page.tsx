import { PageHeader } from "./_components/PageHeader";
import { FilterBar } from "./_components/FilterBar";
import { PenggunaTable } from "./_components/PenggunaTable";

export default function PenggunaPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <FilterBar />
      <PenggunaTable />
    </div>
  );
}
