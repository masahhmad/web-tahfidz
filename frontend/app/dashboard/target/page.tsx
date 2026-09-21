import { PageHeader } from "./_components/PageHeader";
import { TargetTable } from "./_components/TargetTable";

export default function TargetPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <TargetTable />
    </div>
  );
}
