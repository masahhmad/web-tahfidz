export function PaginationFooter({
  summary,
  currentPage,
  totalPages,
}: {
  summary: string;
  currentPage: number;
  totalPages: number;
}) {
  const pages = Array.from({ length: totalPages }, (_, i) => i + 1);

  return (
    <div className="flex w-full flex-col items-start gap-3 border-t border-line/50 bg-card/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
      <p className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted">{summary}</p>

      <nav aria-label="Pagination" className="flex items-center gap-1 overflow-x-auto text-[12px] leading-4 tracking-[0.6px] text-muted">
        <button type="button" aria-label="Halaman sebelumnya" className="rounded px-1.5 py-1 hover:bg-hover disabled:opacity-40" disabled={currentPage === 1}>
          {"<"}
        </button>
        {pages.map((page) => (
          <button
            key={page}
            type="button"
            aria-current={page === currentPage ? "page" : undefined}
            className={`rounded px-2 py-1 ${page === currentPage ? "font-bold text-ink" : "hover:bg-hover"}`}
          >
            {page}
          </button>
        ))}
        <span aria-hidden="true">...</span>
        <button type="button" aria-label="Halaman berikutnya" className="rounded px-1.5 py-1 hover:bg-hover">
          {">"}
        </button>
      </nav>
    </div>
  );
}
