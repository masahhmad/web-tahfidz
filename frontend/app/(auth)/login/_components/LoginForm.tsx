"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { NavIcon, icons } from "../../../dashboard/_components/icons";
import { ForgotPasswordLink } from "./ContactLinks";

const label = "text-[12px] leading-4 font-semibold tracking-[0.6px] text-muted uppercase";
const control =
  "w-full rounded-xl border border-line bg-field/50 py-3 pl-10 text-[14px] leading-5 text-ink outline-none placeholder:text-soft focus:border-brand focus:ring-2 focus:ring-brand/20";

/* -------------------------------------------------------------------------
 * LoginForm — email + password (with show/hide toggle), remember-me and the
 * submit action. Every control has a real <label>; the password toggle is a
 * button with aria-pressed so it's usable by keyboard/screen reader.
 * ---------------------------------------------------------------------- */
export function LoginForm() {
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    // Placeholder until real authentication exists: go straight to the dashboard.
    router.push("/dashboard");
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4 pt-1">
      <div className="flex flex-col gap-1.5">
        <label htmlFor="email" className={label}>
          Email
        </label>
        <div className="relative">
          <NavIcon size={16} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-icon">
            {icons.student}
          </NavIcon>
          <input
            id="email"
            name="email"
            type="email"
            autoComplete="username"
            required
            placeholder="contoh: ustadz.ahmad@pesantren.id"
            className={`${control} pr-4`}
          />
        </div>
      </div>

      <div className="flex flex-col gap-1.5">
        <div className="flex items-center justify-between gap-3">
          <label htmlFor="password" className={label}>
            Password
          </label>
          <ForgotPasswordLink className="text-[12px] leading-4 font-medium text-on-mint hover:underline" />
        </div>
        <div className="relative">
          <NavIcon size={16} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-icon">
            {icons.lock}
          </NavIcon>
          <input
            id="password"
            name="password"
            type={showPassword ? "text" : "password"}
            autoComplete="current-password"
            required
            placeholder="Masukkan kata sandi akun"
            className={`${control} pr-11`}
          />
          <button
            type="button"
            onClick={() => setShowPassword((visible) => !visible)}
            aria-label={showPassword ? "Sembunyikan kata sandi" : "Tampilkan kata sandi"}
            aria-pressed={showPassword}
            className="absolute top-1/2 right-2 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-icon hover:text-ink"
          >
            <NavIcon size={16}>{showPassword ? icons.eye : icons.eyeOff}</NavIcon>
          </button>
        </div>
      </div>

      <label className="flex w-fit cursor-pointer items-center gap-2 py-1 text-[12px] leading-4 font-medium text-muted">
        <input type="checkbox" name="remember" defaultChecked className="size-[18px] rounded accent-brand" />
        Ingat saya di perangkat ini
      </label>

      <button
        type="submit"
        className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-6 py-3 text-[16px] leading-6 font-semibold text-on-brand shadow-[0px_4px_7px_0px_rgba(0,53,39,0.25)] hover:bg-brand-hover"
      >
        Masuk ke Sistem
        <NavIcon size={16}>{icons.arrowRight}</NavIcon>
      </button>
    </form>
  );
}
