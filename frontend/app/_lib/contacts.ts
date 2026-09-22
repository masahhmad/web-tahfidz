/* -------------------------------------------------------------------------
 * Help contacts — who a user can reach on WhatsApp (sidebar "Bantuan" menu
 * and the login page's "Hubungi Super Admin"). The shapes mirror
 * GET /bantuan and GET /bantuan/publik; `getHelpContacts()` returns a local
 * placeholder until those endpoints are wired up.
 * ---------------------------------------------------------------------- */

export type Contact = { id: number; nama: string; telp: string; wa_url: string };
export type DeveloperContact = { nama: string; telp: string; wa_url: string };

export type HelpContacts = {
  admin: Contact[];
  super_admin: Contact[];
  developer: DeveloperContact | null;
};

// Placeholder data. Only accounts that have a phone number come back from the
// API, so `admin` / `super_admin` can legitimately be empty.
const PLACEHOLDER_CONTACTS: HelpContacts = {
  admin: [],
  super_admin: [],
  developer: { nama: "Developer", telp: "082142986689", wa_url: "https://wa.me/6282142986689" },
};

export function getHelpContacts(): HelpContacts {
  return PLACEHOLDER_CONTACTS;
}

/** Appends a pre-filled WhatsApp message to a wa.me URL. */
export function withWaMessage(waUrl: string, message: string): string {
  const separator = waUrl.includes("?") ? "&" : "?";
  return `${waUrl}${separator}text=${encodeURIComponent(message)}`;
}

/** Admins first; falls back to super admins when no admin has a number yet. */
export function resolveAdminContacts(contacts: HelpContacts): Contact[] {
  return contacts.admin.length > 0 ? contacts.admin : contacts.super_admin;
}

export type HelpOption = {
  id: string;
  label: string;
  /** null = nobody to contact yet; render `unavailableText` instead of a link. */
  href: string | null;
  unavailableText?: string;
};

const HELP_MESSAGE = "Halo, saya butuh bantuan Tahfidz System";

/** Rows of the sidebar "Bantuan" dropdown. One admin → "Admin"; several → one row per person. */
export function buildHelpOptions(contacts: HelpContacts): HelpOption[] {
  const admins = resolveAdminContacts(contacts);
  const options: HelpOption[] = [];

  if (admins.length === 0) {
    options.push({ id: "admin", label: "Admin", href: null, unavailableText: "Kontak admin belum tersedia" });
  } else {
    admins.forEach((admin) => {
      options.push({
        id: `admin-${admin.id}`,
        label: admins.length === 1 ? "Admin" : `Admin — ${admin.nama}`,
        href: withWaMessage(admin.wa_url, HELP_MESSAGE),
      });
    });
  }

  options.push(
    contacts.developer
      ? { id: "developer", label: "Developer", href: withWaMessage(contacts.developer.wa_url, HELP_MESSAGE) }
      : { id: "developer", label: "Developer", href: null, unavailableText: "Kontak developer belum tersedia" },
  );

  return options;
}
