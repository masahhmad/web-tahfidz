import { PageHeader } from "./_components/PageHeader";
import { AddHafalanForm } from "./_components/AddHafalanForm";
import { SetoranTable } from "./_components/SetoranTable";

export default function SetoranPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <AddHafalanForm />
      <SetoranTable />
    </div>
  );
}
