import { PageHeader } from "./_components/PageHeader";
import { HalaqahManager } from "./_components/HalaqahManager";

export default function HalaqahPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <HalaqahManager />
    </div>
  );
}
