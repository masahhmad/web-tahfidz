/* Phone-number handling for the profile form. The backend only accepts the
 * local format ^08[0-9]{8,12}$ (as a string — never a number, or the leading
 * 0 is lost) and rejects 62…, +62…, spaces and dashes, so the input is
 * normalised here before it is validated/sent. */

export const PHONE_REQUIRED_ERROR = "Nomor telepon wajib diisi.";
export const PHONE_FORMAT_ERROR = "Nomor harus diawali 08 dan 10–14 digit.";

/** Strips spaces/dashes/brackets and turns a +62 / 62 prefix into 0. */
export function normalizePhone(input: string): string {
  const cleaned = input.replace(/[^\d+]/g, "");

  if (cleaned.startsWith("+62")) return `0${cleaned.slice(3)}`;
  if (cleaned.startsWith("62")) return `0${cleaned.slice(2)}`;
  return cleaned;
}

/** Expects an already-normalised value. */
export function isValidPhone(value: string): boolean {
  return /^08\d{8,12}$/.test(value);
}
