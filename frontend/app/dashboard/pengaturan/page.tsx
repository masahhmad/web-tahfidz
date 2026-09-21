import { PageHeader } from "./_components/PageHeader";
import { ProfileCard } from "./_components/ProfileCard";

export default function PengaturanPage() {
  return (
    <div className="flex w-full flex-col gap-6 p-4 sm:p-8">
      <PageHeader />
      <ProfileCard />
    </div>
  );
}
