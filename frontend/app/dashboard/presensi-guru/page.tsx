import { PageHeader } from "./_components/PageHeader";
import { PresensiGuruManager } from "./_components/PresensiGuruManager";

export default function PresensiGuruPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <PresensiGuruManager />
    </div>
  );
}
