# Frontend Task — Revisi Web Tahfidz

Turunan teknis untuk frontend dari [`OVERALL_TASK.md`](./OVERALL_TASK.md). Frontend masih memakai data mock di semua komponen (belum ada pemanggilan API sama sekali — lihat `api-spec.md` §8), jadi setiap tugas di sini punya dua lapis: **(a)** ubah UI/komponen ke bentuk yang benar, **(b)** siapkan agar gampang disambungkan ke endpoint backend yang berubah (`BACKEND_TASK.md`) begitu API client dibuat.

Urutan disarankan: §3 → §1 → §2 (alasan di `OVERALL_TASK.md`).

---

## 1. Sesi tahfidz otomatis

**Tujuan:** form presensi tidak lagi punya kontrol tanggal/sesi manual; sesi ditampilkan sebagai hasil deteksi (read-only), sama untuk guru dan siswa.

### 1.1 Hapus input yang dekoratif
- `presensi-guru/_components/FilterBar.tsx`: hapus `<input type="datetime-local">` yang sekarang tidak terhubung ke apa pun.
- `presensi-siswa/_components/FilterBar.tsx`: sama — hapus `<input type="datetime-local">` dekoratif.
- Kedua `FilterBar` ini nantinya diisi ulang dengan filter **history** yang sungguhan (tanggal saja, bukan datetime — lihat pola di §2), bukan dihapus total.

### 1.2 Satukan logika sesi guru vs siswa
Sekarang ada dua mekanisme sesi yang berbeda:
- Guru: `<select>` di `FilterBar` (`presensi-guru`) menentukan `sessionLabel` yang diteruskan sebagai **judul modal** (`AddPresensiGuruModal`), lalu ikut dikirim balik lewat `onCreate`.
- Siswa: `<select defaultValue="pagi">` **di dalam** `AddPresensiModal` (`_components/AddPresensiModal.tsx`, dipakai bersama tapi form-nya beda dari punya guru).

Ganti keduanya dengan satu hook/util bersama, mis. `_lib/sesiTahfidz.ts`:
```ts
export type Sesi = "pagi" | "siang" | "sore";
export function useSesiSaatIni(): { sesi: Sesi | null; label: string } { /* … */ }
```
- Selama backend belum terhubung, hitung dari jam **browser** (`new Date()`) sebagai placeholder; setelah API client ada, pindahkan sumber kebenaran ke respons server (§1.5 `BACKEND_TASK.md` menegaskan sesi ditentukan server, klien tidak mengirimnya — nilai di frontend ini hanya untuk **menampilkan** sesi sebelum submit, bukan yang dikirim ke API).
- Dipakai baik oleh `presensi-guru` maupun `presensi-siswa` — hapus `<select>` sesi manual dari `AddPresensiModal` dan dari `FilterBar` guru; ganti dengan teks statis "Sesi saat ini: **Pagi**" di header modal (menggantikan `sessionName` yang sekarang dikendalikan lewat dropdown).
- Bila `sesi === null` (di luar jam manapun, lihat keputusan #2 `OVERALL_TASK.md`), modal menampilkan pesan blokir ("Bukan jam sesi tahfidz sekarang") alih-alih form, dan tombol "Buat Presensi" di `FilterBar` dinonaktifkan — pola serupa `EditScoreModal.tsx` yang sudah menampilkan pesan pengganti form saat pengguna tidak berwenang (`showHafalan || showSoal ? <form> : <p>…</p>`).

### 1.3 `AddPresensiGuruModal.tsx` & `AddPresensiModal.tsx`
- Hapus prop/logic yang bergantung pada sesi terpilih dari luar (`sessionName` yang sekarang datang dari `FilterBar`'s `<select>`) — ganti sumbernya jadi hook §1.2.
- `NewPresensiGuru` (tipe yang dikembalikan `onSubmit`) tidak lagi membawa `session` — sesi bukan bagian dari apa yang dikirim klien (server yang menentukan, per §1.3 `BACKEND_TASK.md`). `PresensiGuruManager.tsx` yang sekarang menaruh `presensi.session` ke record baru harus disesuaikan: baris optimistik baru di tabel menampilkan sesi hasil deteksi hook §1.2, bukan dari payload submit.
- `AddPresensiModal.tsx` (siswa): hapus `<select defaultValue="pagi">` di dalam form; tampilkan sesi terdeteksi di header (sama pola dengan §1.2), dan `subjects` (daftar siswa untuk presensi massal) tetap seperti sekarang.

### 1.4 `FilterBar` (kedua halaman) jadi filter history
Setelah datetime-local dihapus (§1.1), tambahkan filter tanggal yang sungguhan untuk **menyaring tabel riwayat** (`AttendanceTable`), bukan untuk membuat presensi baru — komponen `<input type="date">` yang sama dipakai di Revisi 2 (§2 di bawah). Pertimbangkan mengekstrak satu komponen `DateFilter` dipakai bertiga (Presensi Guru, Presensi Siswa, Setoran) alih-alih tiga implementasi terpisah.

---

## 2. Filter harian riwayat setoran

**Tujuan:** halaman Setoran bisa menyaring riwayat ke satu tanggal, default hari ini.

### 2.1 Tambah `FilterBar` baru di Setoran
`setoran/page.tsx` sekarang langsung merender `PageHeader` → `AddHafalanForm` → `SetoranTable` tanpa filter apa pun. Tambahkan `setoran/_components/FilterBar.tsx` baru (ikuti pola `data-siswa/_components/FilterBar.tsx`: card, `sm:flex-row`, label `sr-only`), diletakkan di antara `AddHafalanForm` dan `SetoranTable`:
```tsx
<PageHeader />
<AddHafalanForm />
<FilterBar date={date} onDateChange={setDate} />
<SetoranTable date={date} />
```
- Satu `<input type="date">`, default **hari ini** (`new Date().toISOString().slice(0, 10)`), state dikelola di `setoran/page.tsx` (perlu jadi client component — tambahkan `"use client"`) atau di komponen manager baru `SetoranManager.tsx` mengikuti pola `PresensiGuruManager.tsx`/`HalaqahManager.tsx` yang sudah ada untuk halaman lain.
- Sertakan juga tombol "Hari Ini" untuk kembali ke default cepat (opsional, kecil).

### 2.2 `SetoranTable.tsx`
- Terima prop `date` (atau `records` sudah tersaring dari manager) dan filter `RECORDS` mock berdasarkan tanggal — sekarang `RECORDS` tidak punya field `tanggal` sama sekali, tambahkan ke tipe `SetoranRecord`:
  ```ts
  export type SetoranRecord = {
    no: number;
    studentName: string;
    className: string;
    juz: number;
    amount: string;
    tanggal: string;   // ISO date — baru
  };
  ```
- Saat API client sudah ada, `date` inilah yang dikirim sebagai query `?tanggal=` ke `GET /setoran` (§2.1 `BACKEND_TASK.md`).

---

## 3. Halaqah = 1 kelas + 1 pengampu

**Tujuan:** "Atur Halaqah" menjadi *pilih kelas → tetapkan satu pengampu*, bukan lagi *pilih siswa satu-satu → pilih pengampu+halaqah bebas*. Ini perubahan UI paling besar dari tiga revisi.

### 3.1 `halaqah/_components/AssignHalaqahBar.tsx` — rombak total
- Hapus dropdown `HALAQAH_OPTIONS` (`"Kelas 7A-1"`, dst — konsep halaqah bebas tidak ada lagi).
- Dropdown pertama berubah dari "pilih pengampu lalu terapkan ke siswa yang dicentang" menjadi: **pilih kelas** (`CLASS_OPTIONS` yang sudah ada di `HalaqahFilterBar.tsx`, mis. `7A`, `7B`, …) + **pilih pengampu** (`PENGAMPU_OPTIONS`, boleh kosong = lepas pengampu). Tidak ada lagi pemilihan siswa individual.
- `onSave` sekarang membawa `{ kelasId, guruId }`, bukan `{ selectedIds, pengampu, halaqah }`.

### 3.2 `halaqah/_components/HalaqahManager.tsx`
- Hapus seluruh state `selectedIds: Set<number>` dan `toggleStudent` — tidak ada lagi mode "checkbox pilih siswa" karena penugasan sekarang per kelas, bukan per baris siswa.
- `isAssigning` tetap ada (kontrol tampil/sembunyi `AssignHalaqahBar`), tapi `HalaqahTable` tidak lagi menerima `isAssigning`/`selectedIds`/`onToggleStudent` — tabel selalu tampil dalam mode baca saja (kolom "Guru Pengampu", tidak pernah berubah jadi kolom "Pilih Siswa").

### 3.3 `halaqah/_components/HalaqahTable.tsx`
- Hapus prop `isAssigning`, `selectedIds`, `onToggleStudent`, dan kolom/`<input type="checkbox">` yang bergantung padanya (`columns = [...] isAssigning ? "Pilih Siswa" : "Guru Pengampu"` → tinggal `"Guru Pengampu"` selalu).
- `HalaqahStudent.pengampu` tetap ada di tipe, tapi nilainya sekarang **turunan dari kelas siswa**, bukan field independen per siswa (mengikuti `Santri.guru()` baru di §3.3 `BACKEND_TASK.md`) — tidak ada perubahan bentuk data yang terlihat frontend, hanya cara backend mengisinya.

### 3.4 `halaqah/_components/HalaqahFilterBar.tsx`
- Dropdown "Semua Pengampu" tetap relevan (filter tabel by pengampu), tapi sekarang secara implisit juga jadi filter by kelas (karena 1 kelas = 1 pengampu) — tidak perlu perubahan struktural, hanya pastikan sumber datanya (§3.7) konsisten dengan `kelas.guru`.

### 3.5 Presensi Siswa — "Halaqah Al-Fatih" → nama kelas
- `presensi-siswa/_components/FilterBar.tsx`: dropdown filter berisi `"Halaqah Al-Fatih"`, `"Halaqah An-Nur"` (nama bebas) → ganti jadi daftar **kelas** (`7A`, `7B`, …), konsisten dengan halaman lain.
- `STUDENTS: PresensiSubject[]` (mock, di file yang sama) punya `meta: "Halaqah Al-Fatih"` per siswa → ganti jadi nama kelas siswa tsb, turunan dari `kelas`, bukan field bebas.
- `_components/AddPresensiModal.tsx` menampilkan `subject.meta` apa adanya — tidak perlu diubah strukturnya, hanya isi datanya yang berubah sumber (nama kelas, bukan nama halaqah bebas).

### 3.6 Data Siswa & mock lain — satukan sumber "kelas + pengampu"
Beberapa file punya `PENGAMPU_OPTIONS`/`CLASS_OPTIONS` sendiri-sendiri sebagai array string lepas, sekarang jadi rawan tidak sinkron karena pengampu **melekat ke kelas**:
- `data-siswa/_components/FilterBar.tsx`
- `halaqah/_components/HalaqahFilterBar.tsx`
- `halaqah/_components/AssignHalaqahBar.tsx` (setelah §3.1)

Buat satu sumber mock sementara, mis. `_components/session.tsx`-sibling baru `_lib/mock-kelas.ts`:
```ts
export const KELAS_LIST = [
  { id: 1, nama: "7A", pengampu: { id: 3, nama: "Ustadz Zaid" } },
  { id: 2, nama: "7B", pengampu: null },
  // …
];
```
supaya "Pilih Kelas" dan "Pengampu" di berbagai filter berasal dari **satu daftar**, bukan dua array independen yang kebetulan sama isinya sekarang. Ini juga mempermudah penyambungan ke `GET /kelas` (yang setelah §3.4 `BACKEND_TASK.md` sudah menyertakan `guru`) nanti — satu tempat untuk diganti, bukan banyak file.

### 3.7 `session.tsx` — tidak berubah
`useCanManageMasterData()` dan aturan read-only `super_admin` di Data Siswa/Halaqah/Target (`api-spec.md` §2) **tidak terpengaruh** oleh revisi ini — pengampu pindah level (siswa → kelas), tapi siapa yang boleh mengubahnya (`admin`) tetap sama.
