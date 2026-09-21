import { PageHeader } from "./_components/PageHeader";
import { UjianTable } from "./_components/UjianTable";

export default function KenaikanJuzPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <UjianTable />
    </div>
  );
}
