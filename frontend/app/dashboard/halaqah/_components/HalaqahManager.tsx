"use client";

import { useState } from "react";
import { HalaqahFilterBar } from "./HalaqahFilterBar";
import { AssignHalaqahBar } from "./AssignHalaqahBar";
import { HalaqahTable } from "./HalaqahTable";

/* -------------------------------------------------------------------------
 * HalaqahManager — owns the "Atur Halaqah" flow shared by the filter bar,
 * the assignment bar, and the table's checkbox column: opening "Atur
 * Halaqah" reveals AssignHalaqahBar and switches the table into selection
 * mode; Batal/Simpan both close it and clear the selection.
 * ---------------------------------------------------------------------- */
export function HalaqahManager() {
  const [isAssigning, setIsAssigning] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());

  function toggleStudent(id: number) {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  }

  function closeAssign() {
    setIsAssigning(false);
    setSelectedIds(new Set());
  }

  return (
    <>
      <HalaqahFilterBar onOpenAssign={() => setIsAssigning(true)} />
      {isAssigning && <AssignHalaqahBar onCancel={closeAssign} onSave={closeAssign} />}
      <HalaqahTable isAssigning={isAssigning} selectedIds={selectedIds} onToggleStudent={toggleStudent} />
    </>
  );
}
