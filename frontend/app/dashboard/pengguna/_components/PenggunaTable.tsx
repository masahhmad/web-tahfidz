"use client";

import { useState } from "react";
import { NavIcon, icons } from "../../_components/icons";
import { PaginationFooter } from "../../_components/PaginationFooter";
import { EditPenggunaModal, SUPER_ADMIN_LABEL, type PenggunaEdit } from "./EditPenggunaModal";
import { ResetPasswordModal } from "./ResetPasswordModal";
import { ToggleActiveModal } from "./ToggleActiveModal";

export type PenggunaRecord = {
  no: number;
  name: string;
  email: string;
  role: string;
  isActive: boolean;
  /** Admin / super admin who has not filled in a phone number yet (still locked out). */
  needsPhone?: boolean;
};

const COLUMNS = ["No", "Nama", "Email", "Role", "Aksi"] as const;

const INITIAL_RECORDS: PenggunaRecord[] = [
  { no: 1, name: "Ahmad Rasyid", email: "ahmadrasyid@gmail.com", role: "Guru Pengampu", isActive: true },
  {
    no: 2,
    name: "Muhammad Zaid Burhanuddin",
    email: "zaidburhan@gmail.com",
    role: SUPER_ADMIN_LABEL,
    isActive: true,
    needsPhone: true,
  },
];

/* -------------------------------------------------------------------------
 * PenggunaTable — semantic <table> so screen readers get real row/column
 * relationships; wrapped in its own overflow-x-auto so it scrolls
 * horizontally on mobile/tablet instead of squeezing columns. Aksi has
 * reset-password (key), edit (pencil), and activate/deactivate actions. The key opens
 * ResetPasswordModal; the pencil opens EditPenggunaModal (name/email/role). The last button is a stand-in for
 * delete: it opens ToggleActiveModal, and its icon shows the current state
 * (circle-check = active, circle-x = inactive).
 * ---------------------------------------------------------------------- */
export function PenggunaTable() {
  const [records, setRecords] = useState(INITIAL_RECORDS);
  const [editingNo, setEditingNo] = useState<number | null>(null);
  const [resettingNo, setResettingNo] = useState<number | null>(null);
  const [togglingNo, setTogglingNo] = useState<number | null>(null);

  const editing = records.find((record) => record.no === editingNo) ?? null;
  const resetting = records.find((record) => record.no === resettingNo) ?? null;
  const toggling = records.find((record) => record.no === togglingNo) ?? null;

  function handleSaveEdit(next: PenggunaEdit) {
    setRecords((current) => current.map((record) => (record.no === editingNo ? { ...record, ...next } : record)));
    setEditingNo(null);
  }

  function handleConfirmToggle() {
    setRecords((current) =>
      current.map((record) => (record.no === togglingNo ? { ...record, isActive: !record.isActive } : record)),
    );
    setTogglingNo(null);
  }

  function handleSavePassword() {
    // Placeholder until the backend exists; the password is not stored client-side.
    setResettingNo(null);
  }

  return (
    <div className="w-full overflow-hidden rounded-xl border border-card-edge bg-card shadow-[0px_4px_20px_0px_rgba(0,0,0,0.03)]">
      <div className="w-full overflow-x-auto">
        <table className="w-full min-w-[780px] border-collapse text-center">
          <thead className="bg-card/50">
            <tr>
              {COLUMNS.map((label, index) => (
                <th
                  key={label}
                  scope="col"
                  className={`border-b border-line/50 px-6 py-4 text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted whitespace-nowrap ${
                    index === 0 ? "w-[100px] text-left" : ""
                  }`}
                >
                  {label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {records.map((record) => (
              <tr key={record.no} className="border-b border-divider last:border-b-0">
                <td className="px-6 py-2.5 text-left text-[14px] leading-5 text-muted">{record.no}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 font-semibold text-ink whitespace-nowrap">
                  <div className="flex flex-col items-center gap-1">
                    {record.name}
                    {record.needsPhone && (
                      <span className="rounded-full bg-warn px-2 py-0.5 text-[11px] leading-4 font-semibold text-on-warn">
                        Belum isi telp
                      </span>
                    )}
                  </div>
                </td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.email}</td>
                <td className="px-6 py-2.5 text-[14px] leading-5 text-muted whitespace-nowrap">{record.role}</td>
                <td className="px-6 py-2.5">
                  <div className="flex items-center justify-center gap-2">
                    <button
                      type="button"
                      onClick={() => setResettingNo(record.no)}
                      aria-label={`Reset kata sandi ${record.name}`}
                      className="inline-flex items-center justify-center rounded-md p-1.5 text-brand hover:bg-hover"
                    >
                      <NavIcon>{icons.key}</NavIcon>
                    </button>
                    <button
                      type="button"
                      onClick={() => setEditingNo(record.no)}
                      aria-label={`Ubah data ${record.name}`}
                      className="inline-flex items-center justify-center rounded-md p-1.5 text-brand hover:bg-hover"
                    >
                      <NavIcon>{icons.edit}</NavIcon>
                    </button>
                    <button
                      type="button"
                      onClick={() => setTogglingNo(record.no)}
                      aria-label={`${record.isActive ? "Nonaktifkan" : "Aktifkan"} pengguna ${record.name}`}
                      title={record.isActive ? "Nonaktifkan" : "Aktifkan"}
                      className="inline-flex items-center justify-center rounded-md p-1.5 text-brand hover:bg-hover"
                    >
                      <NavIcon>{record.isActive ? icons.circleCheck : icons.circleX}</NavIcon>
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <PaginationFooter summary="Data Seluruh Pengguna" currentPage={1} totalPages={6} />

      {editing && (
        <EditPenggunaModal
          key={editing.no}
          isOpen
          onClose={() => setEditingNo(null)}
          initial={{ name: editing.name, email: editing.email, role: editing.role }}
          onSave={handleSaveEdit}
        />
      )}
      {resetting && (
        <ResetPasswordModal
          key={resetting.no}
          isOpen
          onClose={() => setResettingNo(null)}
          userName={resetting.name}
          onSave={handleSavePassword}
        />
      )}
      {toggling && (
        <ToggleActiveModal
          key={toggling.no}
          isOpen
          onClose={() => setTogglingNo(null)}
          userName={toggling.name}
          isActive={toggling.isActive}
          onConfirm={handleConfirmToggle}
        />
      )}
    </div>
  );
}
