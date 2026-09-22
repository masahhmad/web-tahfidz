/* Browser geolocation for presensi: the attendance records where the user was
 * when they pressed save. Needs a secure context (HTTPS or localhost) and the
 * user's permission, so every failure has a message that says what to do. */

export type Coordinates = { latitude: number; longitude: number };

/** An error whose message is already written for the user (Indonesian). */
export class LocationError extends Error {}

const MESSAGES = {
  unsupported: "Browser ini tidak mendukung lokasi. Gunakan browser lain lalu coba lagi.",
  denied: "Izin lokasi ditolak. Izinkan akses lokasi di pengaturan browser, lalu coba lagi.",
  unavailable: "Lokasi tidak dapat ditemukan. Pastikan GPS/lokasi perangkat aktif, lalu coba lagi.",
  timeout: "Pengambilan lokasi terlalu lama. Coba lagi.",
  unknown: "Gagal mengambil lokasi. Coba lagi.",
} as const;

function messageFor(error: GeolocationPositionError): string {
  switch (error.code) {
    case error.PERMISSION_DENIED:
      return MESSAGES.denied;
    case error.POSITION_UNAVAILABLE:
      return MESSAGES.unavailable;
    case error.TIMEOUT:
      return MESSAGES.timeout;
    default:
      return MESSAGES.unknown;
  }
}

/** Asks the browser for a fresh position (never a cached one). */
export function getCurrentCoordinates(): Promise<Coordinates> {
  return new Promise((resolve, reject) => {
    if (typeof navigator === "undefined" || !("geolocation" in navigator)) {
      reject(new LocationError(MESSAGES.unsupported));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => resolve({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
      (error) => reject(new LocationError(messageFor(error))),
      { enableHighAccuracy: true, timeout: 10_000, maximumAge: 0 },
    );
  });
}

/** "latitude,longitude" — the string the API stores in `lokasi` (max 100 chars). */
export function formatCoordinates({ latitude, longitude }: Coordinates): string {
  return `${latitude.toFixed(6)},${longitude.toFixed(6)}`;
}

export const LOCATION_FALLBACK_ERROR = MESSAGES.unknown;
