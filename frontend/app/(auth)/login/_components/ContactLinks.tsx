"use client";

import type { ReactNode } from "react";
import { getHelpContacts, withWaMessage } from "../../../_lib/contacts";

/* Who a not-yet-logged-in user should contact: the first super admin that has
 * a phone number, else the developer. `null` = nobody to contact (link hidden). */
function pickPublicContact() {
  const contacts = getHelpContacts();
  const superAdmin = contacts.super_admin[0];

  if (superAdmin) return { label: "Super Admin", waUrl: superAdmin.wa_url };
  if (contacts.developer) return { label: "Developer", waUrl: contacts.developer.wa_url };
  return null;
}

/* "Lupa Sandi?" — there is no self-service reset yet, so it opens WhatsApp to
 * the same contact as the help link below the form. */
export function ForgotPasswordLink({ className }: { className?: string }) {
  const contact = pickPublicContact();
  if (!contact) return null;

  return (
    <a
      href={withWaMessage(contact.waUrl, "Halo, saya lupa kata sandi Tahfidz System")}
      target="_blank"
      rel="noopener noreferrer"
      className={className}
    >
      Lupa Sandi?
    </a>
  );
}

/* "Butuh Bantuan? Hubungi …" footer link; the label names whoever it really reaches. */
export function HelpFooterLink({ className, children }: { className?: string; children?: ReactNode }) {
  const contact = pickPublicContact();
  if (!contact) return null;

  return (
    <a
      href={withWaMessage(contact.waUrl, "Halo, saya butuh bantuan Tahfidz System")}
      target="_blank"
      rel="noopener noreferrer"
      className={className}
    >
      {children ?? `Butuh Bantuan? Hubungi ${contact.label}`}
    </a>
  );
}
