"use client";

import { useEffect, useRef, type RefObject } from "react";

/* -------------------------------------------------------------------------
 * useModalA11y — shared dialog a11y contract for popups/drawers: ESC to
 * close, Tab focus trap within the panel, body scroll lock while open, and
 * focus restored to the trigger element on close.
 * ---------------------------------------------------------------------- */
export function useModalA11y(isOpen: boolean, onClose: () => void): RefObject<HTMLDivElement | null> {
  const panelRef = useRef<HTMLDivElement>(null);
  const previouslyFocused = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!isOpen) return;

    previouslyFocused.current = document.activeElement as HTMLElement;
    const panel = panelRef.current;
    const focusable = panel?.querySelectorAll<HTMLElement>(
      'a[href], button:not([disabled]), select, textarea, input, [tabindex]:not([tabindex="-1"])',
    );
    focusable?.[0]?.focus();

    document.body.style.overflow = "hidden";

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") {
        onClose();
        return;
      }
      if (event.key !== "Tab" || !focusable || focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = "";
      previouslyFocused.current?.focus();
    };
  }, [isOpen, onClose]);

  return panelRef;
}
