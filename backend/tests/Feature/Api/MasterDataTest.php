<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;

class MasterDataTest extends ApiTestCase
{
    public function test_access_matrix_for_santri_kelas_halaqah(): void
    {
        $sa = $this->makeUser('super_admin');
        $admin = $this->makeUser('admin');
        $guru = $this->makeUser('guru_halaqah');
        $kelas = $this->makeKelas();
        $santri = $this->makeSantri(['kelas_id' => $kelas->id]);
        $halaqah = $this->makeHalaqah();

        foreach (['/api/santri', '/api/kelas', '/api/halaqah', '/api/halaqah/siswa', "/api/santri/{$santri->id}/rekap"] as $url) {
            $this->as($guru)->getJson($url)->assertStatus(403);
            $this->as($sa)->getJson($url)->assertOk();
            $this->as($admin)->getJson($url)->assertOk();
        }

        // super_admin read-only: semua tulis ditolak 403.
        $body = ['nama' => 'X', 'kelas_id' => $kelas->id, 'nisn' => '1234567890', 'jumlah_hafalan' => 1];
        $this->as($sa)->postJson('/api/santri', $body)->assertStatus(403);
        $this->as($sa)->putJson("/api/santri/{$santri->id}", $body)->assertStatus(403);
        $this->as($sa)->deleteJson("/api/santri/{$santri->id}")->assertStatus(403);
        $this->as($sa)->postJson('/api/santri/import')->assertStatus(403);
        $this->as($sa)->postJson('/api/kelas', ['kelas' => '9A', 'kategori' => 'ikh'])->assertStatus(403);
        $this->as($sa)->putJson("/api/kelas/{$kelas->id}", ['target_hafalan' => 5])->assertStatus(403);
        $this->as($sa)->deleteJson("/api/kelas/{$kelas->id}")->assertStatus(403);
        $this->as($sa)->postJson('/api/halaqah', ['nama' => 'Baru'])->assertStatus(403);
        $this->as($sa)->putJson('/api/halaqah/assign', [])->assertStatus(403);
        $this->as($sa)->deleteJson("/api/halaqah/{$halaqah->id}")->assertStatus(403);

        $this->assertDatabaseCount('santris', 1);
    }

    public function test_admin_santri_crud_derives_kategori_from_kelas(): void
    {
        $admin = $this->makeUser('admin');
        $kelas = $this->makeKelas('7A', 'akh');

        $id = $this->as($admin)->postJson('/api/santri', [
            'nama' => 'Ahmad Rasyid', 'kelas_id' => $kelas->id, 'nisn' => '0987654321', 'jumlah_hafalan' => 15,
        ])->assertStatus(201)
            ->assertJsonPath('data.nisn', '0987654321')      // nol di depan tidak hilang
            ->assertJsonPath('data.kategori', 'akh')
            ->assertJsonPath('data.kelas.nama', '7A')
            ->json('data.id');

        $this->as($admin)->putJson("/api/santri/$id", [
            'nama' => 'Ahmad R.', 'kelas_id' => $kelas->id, 'nisn' => '0987654321', 'jumlah_hafalan' => 16,
        ])->assertOk()->assertJsonPath('data.jumlah_hafalan', 16);

        $this->as($admin)->deleteJson("/api/santri/$id")->assertNoContent();
        $this->assertSoftDeleted('santris', ['id' => $id]);
        $this->as($admin)->getJson("/api/santri/$id")->assertStatus(404);
    }

    public function test_deleting_santri_keeps_history_but_hides_and_blocks_the_student(): void
    {
        $admin = $this->makeUser('admin');
        $guru = $this->makeUser('guru_halaqah');
        $h = $this->makeHalaqah();
        $santri = $this->makeSantri(['nama' => 'Ahmad', 'nisn' => '0987654321', 'guru_id' => $guru->id, 'halaqah_id' => $h->id]);
        \App\Models\Setoran::create(['santri_id' => $santri->id, 'juz' => 1, 'baris' => 30, 'tanggal' => '2026-08-10']);
        \App\Models\PresensiSiswa::create(['santri_id' => $santri->id, 'tanggal' => '2026-08-10', 'sesi' => 'pagi', 'status' => 'hadir']);
        \App\Models\KenaikanJuz::create(['santri_id' => $santri->id, 'juz' => 1]);

        $this->as($admin)->deleteJson("/api/santri/{$santri->id}")->assertNoContent();

        // Hilang dari daftar siswa, dropdown, dan dashboard.
        $this->as($admin)->getJson('/api/santri')->assertJsonPath('meta.total', 0);
        $this->as($guru)->getJson('/api/lookup/santri')->assertJsonCount(0, 'data');
        $this->as($guru)->getJson('/api/dashboard/summary')->assertJsonPath('data', ['hafalan_tercapai' => 0, 'hafalan_belum_tercapai' => 0]);

        // Riwayat tetap ada, nama siswa tetap tampil.
        $this->as($admin)->getJson('/api/setoran')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.santri.nama', 'Ahmad');
        $this->as($admin)->getJson('/api/presensi-siswa')->assertJsonPath('data.0.nama_siswa', 'Ahmad');
        $this->as($admin)->getJson('/api/kenaikan-juz')->assertJsonPath('data.0.santri.nama', 'Ahmad');
        $this->as($guru)->getJson('/api/setoran')->assertJsonPath('meta.total', 1);

        // Tidak bisa dipilih lagi untuk data baru.
        $this->as($admin)->postJson('/api/setoran', ['santri_id' => $santri->id, 'juz' => 1, 'baris' => 5])->assertStatus(422)->assertJsonValidationErrors('santri_id');
        $this->as($admin)->postJson('/api/kenaikan-juz', ['santri_id' => $santri->id, 'juz' => 2])->assertStatus(422);
        $this->as($admin)->postJson('/api/presensi-siswa/bulk', [
            'tanggal' => '2026-08-11', 'sesi' => 'pagi', 'halaqah_id' => $h->id, 'items' => [['santri_id' => $santri->id, 'status' => 'hadir']],
        ])->assertStatus(422);
        $kelas = $this->makeKelas('9Z');
        $this->as($admin)->putJson('/api/halaqah/assign', ['santri_ids' => [$santri->id], 'guru_id' => $guru->id, 'halaqah_id' => $h->id])->assertStatus(422);

        // NISN tetap terpakai (unique index DB mencakup baris terhapus) — validasi & import memberi 422, bukan 500.
        $this->as($admin)->postJson('/api/santri', ['nama' => 'Baru', 'kelas_id' => $kelas->id, 'nisn' => '0987654321', 'jumlah_hafalan' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('nisn');
        $csv = \Illuminate\Http\UploadedFile::fake()->createWithContent('s.csv', "nama_siswa,kelas,nisn,jumlah_hafalan\nBaru,9Z,0987654321,1\n");
        $this->as($admin)->postJson('/api/santri/import', ['file' => $csv])->assertStatus(422)->assertJsonFragment(['field' => 'nisn']);
    }

    public function test_santri_validation(): void
    {
        $admin = $this->makeUser('admin');
        $kelas = $this->makeKelas();
        $this->makeSantri(['nisn' => '1111111111']);
        $ok = ['nama' => 'A', 'kelas_id' => $kelas->id, 'nisn' => '2222222222', 'jumlah_hafalan' => 1];

        $this->as($admin)->postJson('/api/santri', array_merge($ok, ['nisn' => '123']))->assertStatus(422)->assertJsonValidationErrors('nisn');
        $this->as($admin)->postJson('/api/santri', array_merge($ok, ['nisn' => '1111111111']))->assertStatus(422)->assertJsonValidationErrors('nisn');
        $this->as($admin)->postJson('/api/santri', array_merge($ok, ['jumlah_hafalan' => 31]))->assertStatus(422)->assertJsonValidationErrors('jumlah_hafalan');
        $this->as($admin)->postJson('/api/santri', array_merge($ok, ['kelas_id' => 999]))->assertStatus(422)->assertJsonValidationErrors('kelas_id');
    }

    public function test_santri_index_filters_and_pagination(): void
    {
        $sa = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');
        $kelas = $this->makeKelas('7A');
        $this->makeSantri(['nama' => 'Ahmad', 'nisn' => '0000000001', 'kelas_id' => $kelas->id, 'guru_id' => $guru->id]);
        $this->makeSantri(['nama' => 'Budi', 'nisn' => '0000000002', 'kelas_id' => $kelas->id]);
        $this->makeSantri(['nama' => 'Citra', 'nisn' => '0000000003']);

        $this->as($sa)->getJson('/api/santri')->assertOk()->assertJsonPath('meta.total', 3);
        $this->as($sa)->getJson("/api/santri?kelas_id={$kelas->id}")->assertJsonPath('meta.total', 2);
        $this->as($sa)->getJson("/api/santri?guru_id={$guru->id}")->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.pengampu.nama', $guru->username);
        $this->as($sa)->getJson('/api/santri?search=0000000003')->assertJsonPath('data.0.nama', 'Citra');
        $this->as($sa)->getJson('/api/santri?search=%')->assertJsonPath('meta.total', 0);
        $this->as($sa)->getJson('/api/santri?per_page=2&page=2')->assertJsonPath('meta.current_page', 2)->assertJsonCount(1, 'data');
    }

    public function test_import_csv_all_or_nothing(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeKelas('7A');
        $this->makeSantri(['nisn' => '1111111111']);

        $csv = fn (string $body) => UploadedFile::fake()->createWithContent('siswa.csv', $body);
        $header = "nama_siswa,kelas,nisn,jumlah_hafalan\n";

        $this->as($admin)->postJson('/api/santri/import', ['file' => $csv($header."Ahmad,7A,0987654321,15 Juz\nBudi,7A,0987654322,3\n")], ['Accept' => 'application/json'])
            ->assertStatus(201)->assertJsonPath('data.dibuat', 2);
        $this->assertDatabaseHas('santris', ['nisn' => '0987654321', 'jumlah_hafalan' => 15, 'kategori' => 'ikh']);

        // Baris gagal -> tidak ada yang tersimpan, error memuat nomor baris (header = baris 1).
        $before = \App\Models\Santri::count();
        $this->as($admin)->postJson('/api/santri/import', ['file' => $csv($header."Citra,7A,5555555555,1\nDedi,7A,1111111111,1\nEko,9Z,6666666666,2\n")])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Import dibatalkan.')
            ->assertJsonFragment(['baris' => 3, 'field' => 'nisn', 'pesan' => 'NISN sudah terdaftar.'])
            ->assertJsonFragment(['baris' => 4, 'field' => 'kelas', 'pesan' => 'Kelas 9Z tidak ditemukan.']);
        $this->assertSame($before, \App\Models\Santri::count());

        $this->as($admin)->postJson('/api/santri/import', ['file' => $csv("nama,kelas\nA,7A\n")])
            ->assertStatus(422)->assertJsonPath('errors.0.field', 'header');
        $this->as($admin)->postJson('/api/santri/import', ['file' => $csv($header."A,7A,7777777777,1\nB,7A,7777777777,1\n")])
            ->assertStatus(422)->assertJsonFragment(['baris' => 3, 'field' => 'nisn', 'pesan' => 'NISN sudah terdaftar.']);
    }

    public function test_rekap(): void
    {
        $sa = $this->makeUser('super_admin');
        $santri = $this->makeSantri();
        foreach (['hadir', 'hadir', 'sakit'] as $i => $status) {
            \App\Models\PresensiSiswa::create(['santri_id' => $santri->id, 'tanggal' => "2026-08-1$i", 'sesi' => 'pagi', 'status' => $status]);
        }
        \App\Models\Setoran::create(['santri_id' => $santri->id, 'juz' => 1, 'baris' => 30, 'tanggal' => '2026-08-10']);
        \App\Models\Setoran::create(['santri_id' => $santri->id, 'juz' => 1, 'baris' => 10, 'tanggal' => '2026-08-12']);
        \App\Models\KenaikanJuz::create(['santri_id' => $santri->id, 'juz' => 1, 'nilai_setoran' => 85, 'nilai_soal' => 80]);

        $this->as($sa)->getJson("/api/santri/{$santri->id}/rekap")->assertOk()
            ->assertJsonPath('data.presensi', ['hadir' => 2, 'izin' => 0, 'sakit' => 1, 'alpa' => 0])
            ->assertJsonPath('data.setoran.total_baris', 40)
            ->assertJsonPath('data.setoran.terakhir', '2026-08-12')
            ->assertJsonPath('data.kenaikan_juz.0.nilai_hafalan', 85);
    }

    public function test_kelas_target_crud_and_guards(): void
    {
        $admin = $this->makeUser('admin');

        $id = $this->as($admin)->postJson('/api/kelas', ['kelas' => '7A', 'kategori' => 'ikh'])
            ->assertStatus(201)->assertJsonPath('data.target_hafalan', null)->assertJsonPath('data.jumlah_siswa', 0)->json('data.id');

        $this->as($admin)->postJson('/api/kelas', ['kelas' => '7A', 'kategori' => 'ikh'])->assertStatus(422);
        $this->as($admin)->postJson('/api/kelas', ['kelas' => '7A', 'kategori' => 'akh'])->assertStatus(201);

        // Popup pensil: hanya target_hafalan.
        $this->as($admin)->putJson("/api/kelas/$id", ['target_hafalan' => 15])->assertOk()
            ->assertJsonPath('data.target_hafalan', 15)->assertJsonPath('data.kelas', '7A');
        $this->as($admin)->putJson("/api/kelas/$id", ['target_hafalan' => 31])->assertStatus(422);
        $this->as($admin)->putJson("/api/kelas/$id", ['target_hafalan' => null])->assertOk()->assertJsonPath('data.target_hafalan', null);

        $santri = $this->makeSantri(['kelas_id' => $id]);
        $this->as($admin)->deleteJson("/api/kelas/$id")->assertStatus(422);

        // Ganti kategori kelas ikut mengubah kategori siswanya.
        $this->as($admin)->putJson("/api/kelas/$id", ['kategori' => 'akh', 'kelas' => '7B'])->assertOk();
        $this->assertSame('akh', $santri->fresh()->kategori);

        $santri->delete();
        $this->as($admin)->deleteJson("/api/kelas/$id")->assertNoContent();
    }

    public function test_halaqah_crud_and_assign(): void
    {
        $admin = $this->makeUser('admin');
        $guru = $this->makeUser('guru_halaqah');
        $nonaktif = $this->makeUser('guru_halaqah');
        $this->setActive($nonaktif, false);
        $bukanGuru = $this->makeUser('admin');
        $s1 = $this->makeSantri();
        $s2 = $this->makeSantri();

        $hid = $this->as($admin)->postJson('/api/halaqah', ['nama' => 'Al-Fatih'])->assertStatus(201)->json('data.id');
        $this->as($admin)->postJson('/api/halaqah', ['nama' => 'Al-Fatih'])->assertStatus(422);
        $this->as($admin)->putJson("/api/halaqah/$hid", ['nama' => 'Al-Fatih 2'])->assertOk();

        $this->as($admin)->putJson('/api/halaqah/assign', ['santri_ids' => [$s1->id, $s2->id], 'guru_id' => $nonaktif->id, 'halaqah_id' => $hid])
            ->assertStatus(422)->assertJsonValidationErrors('guru_id');
        $this->as($admin)->putJson('/api/halaqah/assign', ['santri_ids' => [$s1->id], 'guru_id' => $bukanGuru->id, 'halaqah_id' => $hid])
            ->assertStatus(422)->assertJsonValidationErrors('guru_id');
        $this->as($admin)->putJson('/api/halaqah/assign', ['santri_ids' => [], 'guru_id' => $guru->id, 'halaqah_id' => $hid])
            ->assertStatus(422)->assertJsonValidationErrors('santri_ids');
        $this->as($admin)->putJson('/api/halaqah/assign', ['santri_ids' => [$s1->id, 9999], 'guru_id' => $guru->id, 'halaqah_id' => $hid])
            ->assertStatus(422);
        $this->assertNull($s1->fresh()->guru_id); // atomik: yang valid pun tidak berubah

        $this->as($admin)->putJson('/api/halaqah/assign', ['santri_ids' => [$s1->id, $s2->id], 'guru_id' => $guru->id, 'halaqah_id' => $hid])
            ->assertOk()->assertJsonPath('data.diperbarui', 2);
        $this->assertSame($guru->id, $s1->fresh()->guru_id);

        $this->as($admin)->getJson('/api/halaqah/siswa')->assertOk()
            ->assertJsonPath('data.0.pengampu.nama', $guru->username)
            ->assertJsonPath('data.0.halaqah.nama', 'Al-Fatih 2');
        $this->as($admin)->getJson('/api/halaqah')->assertOk()->assertJsonPath('data.0.jumlah_siswa', 2);

        $this->as($admin)->deleteJson("/api/halaqah/$hid")->assertStatus(422);
    }

    public function test_lookup_is_open_to_guru_but_scoped(): void
    {
        $guru = $this->makeUser('guru_halaqah');
        $admin = $this->makeUser('admin');
        $this->makeKelas('7A');
        $h = $this->makeHalaqah();
        $mine = $this->makeSantri(['guru_id' => $guru->id, 'halaqah_id' => $h->id]);
        $this->makeSantri();

        $this->as($guru)->getJson('/api/lookup/kelas')->assertOk()->assertJsonPath('data.0.nama', '7A');
        $this->as($guru)->getJson('/api/lookup/halaqah')->assertOk()->assertJsonCount(1, 'data');
        $this->as($guru)->getJson('/api/lookup/pengampu')->assertOk()->assertJsonCount(1, 'data');
        $this->as($guru)->getJson('/api/lookup/santri')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
        $this->as($admin)->getJson('/api/lookup/santri')->assertOk()->assertJsonCount(2, 'data');
        $this->as($admin)->getJson("/api/lookup/santri?halaqah_id={$h->id}")->assertJsonCount(1, 'data');
    }

    public function test_dashboard_summary(): void
    {
        $guru = $this->makeUser('guru_halaqah');
        $admin = $this->makeUser('admin');
        $target15 = $this->makeKelas('7A', 'ikh', 15);
        $tanpaTarget = $this->makeKelas('7B');
        $this->makeSantri(['kelas_id' => $target15->id, 'jumlah_hafalan' => 15, 'guru_id' => $guru->id]); // tercapai
        $this->makeSantri(['kelas_id' => $target15->id, 'jumlah_hafalan' => 20]);                          // tercapai
        $this->makeSantri(['kelas_id' => $target15->id, 'jumlah_hafalan' => 3, 'guru_id' => $guru->id]);   // belum
        $this->makeSantri(['kelas_id' => $tanpaTarget->id, 'jumlah_hafalan' => 30]);                       // belum (tanpa target)

        $this->as($admin)->getJson('/api/dashboard/summary')->assertOk()
            ->assertJsonPath('data', ['hafalan_tercapai' => 2, 'hafalan_belum_tercapai' => 2]);
        $this->as($guru)->getJson('/api/dashboard/summary')->assertOk()
            ->assertJsonPath('data', ['hafalan_tercapai' => 1, 'hafalan_belum_tercapai' => 1]);
    }
}
