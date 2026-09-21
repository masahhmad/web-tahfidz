<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SantriResource;
use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Data Siswa — super_admin & admin membaca, admin menulis (diatur di routes/api.php).
 */
class SantriController extends Controller
{
    private const CSV_HEADER = ['nama_siswa', 'kelas', 'nisn', 'jumlah_hafalan'];

    public function index(Request $request): JsonResponse
    {
        $santri = Santri::query()
            ->with(['kelas', 'pengampu', 'halaqah'])
            ->when($request->query('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->query('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->when($request->query('search'), fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('nama', 'like', $this->like($term))
                  ->orWhere('nisn', 'like', $this->like($term));
            }))
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return $this->paginated($santri, SantriResource::class);
    }

    public function show(Santri $santri): JsonResponse
    {
        return $this->item(new SantriResource($santri->load(['kelas', 'pengampu', 'halaqah'])));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $santri = Santri::create([
            'nama'           => $validated['nama'],
            'nisn'           => $validated['nisn'],
            'jumlah_hafalan' => $validated['jumlah_hafalan'],
            'kelas_id'       => $validated['kelas_id'],
            // Form tidak meminta kategori: mengikuti kategori kelasnya.
            'kategori'       => Kelas::findOrFail($validated['kelas_id'])->kategori,
        ]);

        return $this->item(new SantriResource($santri->load(['kelas', 'pengampu', 'halaqah'])), 201);
    }

    public function update(Request $request, Santri $santri): JsonResponse
    {
        $validated = $request->validate($this->rules($santri));

        $santri->update([
            'nama'           => $validated['nama'],
            'nisn'           => $validated['nisn'],
            'jumlah_hafalan' => $validated['jumlah_hafalan'],
            'kelas_id'       => $validated['kelas_id'],
            'kategori'       => Kelas::findOrFail($validated['kelas_id'])->kategori,
        ]);

        return $this->item(new SantriResource($santri->load(['kelas', 'pengampu', 'halaqah'])));
    }

    public function destroy(Santri $santri): JsonResponse|Response
    {
        $santri->delete();

        return response()->noContent();
    }

    /**
     * Tambah banyak siswa lewat CSV. Semua-atau-tidak: satu baris gagal membatalkan semuanya.
     * Nomor `baris` di laporan error = nomor baris pada file (header = baris 1).
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'file.required' => 'File CSV wajib diunggah.',
            'file.mimes'    => 'File harus berformat .csv.',
            'file.max'      => 'Ukuran file maksimal 2 MB.',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return $this->importFailed([['baris' => 1, 'field' => 'file', 'pesan' => 'File kosong.']]);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]); // BOM dari Excel
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        if ($header !== self::CSV_HEADER) {
            fclose($handle);

            return $this->importFailed([[
                'baris' => 1,
                'field' => 'header',
                'pesan' => 'Header harus persis: '.implode(',', self::CSV_HEADER).'.',
            ]]);
        }

        // Kelas dicocokkan lewat nama; nama yang dipakai lebih dari satu kategori dianggap ambigu.
        $kelasByName = Kelas::all()->groupBy('kelas');
        $existingNisn = Santri::withTrashed()->pluck('nisn')->flip();
        $seenNisn = [];
        $errors = [];
        $rows = [];
        $line = 1;

        while (($cols = fgetcsv($handle)) !== false) {
            $line++;

            if ($cols === [null] || count(array_filter($cols, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // baris kosong
            }

            [$nama, $namaKelas, $nisn, $hafalan] = array_map(fn ($c) => trim((string) $c), array_pad($cols, 4, ''));
            $rowErrors = count($errors);

            if ($nama === '' || mb_strlen($nama) > 255) {
                $errors[] = ['baris' => $line, 'field' => 'nama_siswa', 'pesan' => 'Nama siswa wajib diisi (maks 255 karakter).'];
            }

            $kelas = null;
            if (! isset($kelasByName[$namaKelas])) {
                $errors[] = ['baris' => $line, 'field' => 'kelas', 'pesan' => "Kelas {$namaKelas} tidak ditemukan."];
            } elseif ($kelasByName[$namaKelas]->count() > 1) {
                $errors[] = ['baris' => $line, 'field' => 'kelas', 'pesan' => "Kelas {$namaKelas} ambigu (ada di lebih dari satu kategori)."];
            } else {
                $kelas = $kelasByName[$namaKelas]->first();
            }

            if (! preg_match('/^\d{10}$/', $nisn)) {
                $errors[] = ['baris' => $line, 'field' => 'nisn', 'pesan' => 'NISN harus 10 digit angka.'];
            } elseif (isset($existingNisn[$nisn]) || isset($seenNisn[$nisn])) {
                $errors[] = ['baris' => $line, 'field' => 'nisn', 'pesan' => 'NISN sudah terdaftar.'];
            }

            // "15" dan "15 Juz" sama-sama diterima.
            if (! preg_match('/^(\d{1,2})(\s*juz)?$/i', $hafalan, $m) || (int) $m[1] > 30) {
                $errors[] = ['baris' => $line, 'field' => 'jumlah_hafalan', 'pesan' => 'Jumlah hafalan harus angka 0-30 (mis. 15 atau 15 Juz).'];
            }

            if (count($errors) === $rowErrors) {
                $seenNisn[$nisn] = true;
                $rows[] = [
                    'nama'           => $nama,
                    'nisn'           => $nisn,
                    'kelas_id'       => $kelas->id,
                    'kategori'       => $kelas->kategori,
                    'jumlah_hafalan' => (int) $m[1],
                ];
            }
        }
        fclose($handle);

        if ($errors) {
            return $this->importFailed($errors);
        }

        if (! $rows) {
            return $this->importFailed([['baris' => 2, 'field' => 'file', 'pesan' => 'File tidak berisi data siswa.']]);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                Santri::create($row);
            }
        });

        return response()->json(['data' => ['dibuat' => count($rows)]], 201);
    }

    public function rekap(Santri $santri): JsonResponse
    {
        $presensi = $santri->presensi()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json(['data' => [
            'siswa' => [
                'id'    => $santri->id,
                'nama'  => $santri->nama,
                'kelas' => $santri->kelas?->kelas,
            ],
            'presensi' => [
                'hadir' => (int) ($presensi['hadir'] ?? 0),
                'izin'  => (int) ($presensi['izin'] ?? 0),
                'sakit' => (int) ($presensi['sakit'] ?? 0),
                'alpa'  => (int) ($presensi['alpa'] ?? 0),
            ],
            'setoran' => [
                'total_baris' => (int) $santri->setoran()->sum('baris'),
                'terakhir'    => $santri->setoran()->max('tanggal'),
            ],
            'kenaikan_juz' => $santri->kenaikanJuz()->orderBy('juz')->get()->map(fn ($k) => [
                'juz'           => $k->juz,
                'nilai_hafalan' => $k->nilai_setoran,
                'nilai_soal'    => $k->nilai_soal,
            ])->all(),
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Santri $santri = null): array
    {
        return [
            'nama'           => 'required|string|max:255',
            'kelas_id'       => 'required|integer|exists:kelas,id',
            'nisn'           => ['required', 'digits:10', Rule::unique('santris', 'nisn')->ignore($santri?->id)],
            'jumlah_hafalan' => 'required|integer|between:0,30',
        ];
    }

    private function importFailed(array $errors): JsonResponse
    {
        return response()->json(['message' => 'Import dibatalkan.', 'errors' => $errors], 422);
    }
}
