import { PageHeader } from "./_components/PageHeader";
import { FilterBar } from "./_components/FilterBar";
import { DataSiswaTable } from "./_components/DataSiswaTable";

export default function DataSiswaPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <FilterBar />
      <DataSiswaTable />
    </div>
  );
}
