"use client";

import { useCallback, useRef, useState, type FormEvent } from "react";
import { useModalA11y } from "../../_components/useModalA11y";
import {
  formatCoordinates,
  getCurrentCoordinates,
  LocationError,
  LOCATION_FALLBACK_ERROR,
} from "../../_lib/geolocation";

const STATUS_OPTIONS = ["Hadir", "Izin", "Sakit", "Alpa"] as const;
type GuruAttendanceStatus = (typeof STATUS_OPTIONS)[number];

/** What "Simpan Presensi" hands back. The time is not included: the server stamps it on creation. */
export type NewPresensiGuru = {
  status: GuruAttendanceStatus;
  note: string;
  /** "latitude,longitude" of the user at the moment of saving. */
  lokasi: string;
};

/* -------------------------------------------------------------------------
 * AddPresensiGuruModal — single-entry "Tambah Presensi" popup for a guru's
 * own attendance in one session (header = session name, a Kehadiran select,
 * then Keterangan). Keterangan is optional for "Hadir" and required for any
 * other status, enforced both visually (asterisk, placeholder) and natively
 * via the `required` attribute on the textarea.
 *
 * Saving also captures where the user is: pressing "Simpan Presensi" asks the
 * browser for the current coordinates (permission prompt on first use) and
 * only then calls `onSubmit`. If the location can't be read (denied, GPS off,
 * timeout) the modal stays open with a message so the user can fix it and
 * retry — closing the modal while it is still locating discards the result.
 * ---------------------------------------------------------------------- */
export function AddPresensiGuruModal({
  isOpen,
  onClose,
  sessionName,
  onSubmit,
}: {
  isOpen: boolean;
  onClose: () => void;
  sessionName: string;
  onSubmit: (presensi: NewPresensiGuru) => void;
}) {
  const [status, setStatus] = useState<GuruAttendanceStatus>("Hadir");
  const [note, setNote] = useState("");
  const [isLocating, setIsLocating] = useState(false);
  const [locationError, setLocationError] = useState<string | null>(null);
  // Bumped on every save attempt and on close, so a slow location result that
  // arrives after the user cancelled (or retried) is ignored.
  const attemptRef = useRef(0);
  const isNoteRequired = status !== "Hadir";

  const handleClose = useCallback(() => {
    attemptRef.current += 1;
    setIsLocating(false);
    setLocationError(null);
    onClose();
  }, [onClose]);

  const panelRef = useModalA11y(isOpen, handleClose);

  if (!isOpen) return null;

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (isLocating) return;

    const attempt = ++attemptRef.current;
    setLocationError(null);
    setIsLocating(true);

    try {
      const coordinates = await getCurrentCoordinates();
      if (attempt !== attemptRef.current) return;

      onSubmit({ status, note: note.trim(), lokasi: formatCoordinates(coordinates) });
      setStatus("Hadir");
      setNote("");
      setIsLocating(false);
      onClose();
    } catch (error) {
      if (attempt !== attemptRef.current) return;

      setLocationError(error instanceof LocationError ? error.message : LOCATION_FALLBACK_ERROR);
      setIsLocating(false);
    }
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      onClick={handleClose}
    >
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-presensi-guru-title"
        className="flex w-full max-w-md flex-col rounded-2xl bg-modal"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b border-line px-6 py-5">
          <h2 id="add-presensi-guru-title" className="text-[20px] leading-7 font-bold text-ink">
            {sessionName}
          </h2>
          <button
            type="button"
            onClick={handleClose}
            aria-label="Tutup"
            className="rounded p-1 text-muted hover:bg-hover"
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex flex-col gap-5 px-6 py-5">
          <label className="flex flex-col gap-1.5">
            <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted">Kehadiran</span>
            <select
              value={status}
              onChange={(event) => setStatus(event.target.value as GuruAttendanceStatus)}
              className="rounded-lg border border-line px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none"
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option} value={option}>
                  {option}
                </option>
              ))}
            </select>
          </label>

          <label className="flex flex-col gap-1.5">
            <span className="text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted">
              Keterangan{" "}
              {isNoteRequired ? (
                <span className="text-on-bad">*</span>
              ) : (
                <span className="font-normal text-soft">(opsional)</span>
              )}
            </span>
            <textarea
              rows={4}
              value={note}
              onChange={(event) => setNote(event.target.value)}
              required={isNoteRequired}
              placeholder={
                isNoteRequired ? "Wajib diisi untuk status selain Hadir" : "Tambahkan keterangan (opsional)"
              }
              className="resize-none rounded-lg border border-line px-[13px] py-[9px] text-[14px] leading-5 text-ink outline-none"
            />
          </label>

          {locationError ? (
            <p role="alert" className="rounded-lg bg-bad px-3 py-2 text-[12px] leading-4 font-medium text-on-bad">
              {locationError}
            </p>
          ) : (
            <p className="text-[12px] leading-4 text-soft">
              Lokasi Anda akan dicatat saat presensi disimpan. Izinkan akses lokasi jika diminta browser.
            </p>
          )}

          <div className="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={handleClose}
              className="rounded-lg border border-line px-6 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted hover:bg-hover"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLocating}
              aria-busy={isLocating}
              className="rounded-lg bg-brand px-8 py-[9.5px] text-center text-[12px] leading-4 font-semibold tracking-[0.6px] text-on-brand shadow-[0px_1px_1px_0px_rgba(0,0,0,0.05)] hover:bg-brand-hover disabled:opacity-60"
            >
              {isLocating ? "Mengambil lokasi…" : "Simpan Presensi"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
