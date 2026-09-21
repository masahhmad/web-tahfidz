"use client";

import { useSyncExternalStore } from "react";
import { NavIcon, icons } from "./icons";

const STORAGE_KEY = "theme";

/* The <html class="dark"> flag is the source of truth (set before first paint
 * by the inline script in app/layout.tsx). Subscribing to it keeps every
 * toggle in sync and avoids a state-in-effect on mount. */
function subscribe(onChange: () => void) {
  const observer = new MutationObserver(onChange);
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
  return () => observer.disconnect();
}

const getSnapshot = () => document.documentElement.classList.contains("dark");
const getServerSnapshot = () => false;

/* -------------------------------------------------------------------------
 * ThemeToggle — header button switching light/dark. The choice is saved in
 * localStorage only when it differs from the OS setting; otherwise the site
 * keeps following the system (see THEME_SCRIPT in app/layout.tsx).
 * ---------------------------------------------------------------------- */
export function ThemeToggle({ className = "" }: { className?: string }) {
  const isDark = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);

  function toggle() {
    const next = !isDark;
    document.documentElement.classList.toggle("dark", next);
    try {
      // Picking the theme the OS already uses clears the override, so the
      // site goes back to following the system.
      if (next === matchMedia("(prefers-color-scheme: dark)").matches) {
        localStorage.removeItem(STORAGE_KEY);
      } else {
        localStorage.setItem(STORAGE_KEY, next ? "dark" : "light");
      }
    } catch {
      // Storage can be blocked (private mode); the toggle still works for this visit.
    }
  }

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={isDark ? "Ganti ke mode terang" : "Ganti ke mode gelap"}
      title={isDark ? "Mode terang" : "Mode gelap"}
      className={`flex items-center justify-center rounded-full p-2 text-ink hover:bg-hover ${className}`}
    >
      <NavIcon size={18}>{isDark ? icons.sun : icons.moon}</NavIcon>
    </button>
  );
}
