<?php

namespace Tests\Feature\Api;

use App\Models\KenaikanJuz;
use App\Models\PresensiGuru;
use App\Models\PresensiSiswa;
use App\Models\Setoran;

class TransactionsTest extends ApiTestCase
{
    // ---------- Presensi Guru ----------

    public function test_presensi_guru_create_uses_token_user_and_enforces_rules(): void
    {
        $guru = $this->makeUser('guru_halaqah');
        $lain = $this->makeUser('guru_halaqah');
        $body = ['tanggal' => '2026-08-10', 'sesi' => 'pagi', 'status' => 'hadir', 'lokasi' => '-6.2,106.8'];

        // user_id di body diabaikan: selalu milik pemanggil.
        $this->as($guru)->postJson('/api/presensi-guru', $body + ['user_id' => $lain->id])
            ->assertStatus(201)->assertJsonPath('data.sesi', 'pagi')->assertJsonPath('data.guru.id', $guru->id);
        $this->assertDatabaseHas('presensi_gurus', ['user_id' => $guru->id, 'tanggal' => '2026-08-10']);

        $this->as($guru)->postJson('/api/presensi-guru', $body)->assertStatus(409);
        $this->as($guru)->postJson('/api/presensi-guru', array_merge($body, ['sesi' => 'siang']))->assertStatus(201);

        // keterangan wajib bila status != hadir.
        $this->as($guru)->postJson('/api/presensi-guru', array_merge($body, ['sesi' => 'sore', 'status' => 'sakit']))
            ->assertStatus(422)->assertJsonValidationErrors('keterangan');
        $this->as($guru)->postJson('/api/presensi-guru', array_merge($body, ['sesi' => 'sore', 'status' => 'alpa', 'keterangan' => 'Tanpa kabar']))
            ->assertStatus(201);
        $this->as($guru)->postJson('/api/presensi-guru', array_merge($body, ['sesi' => 'malam']))->assertStatus(422);
    }

    public function test_presensi_guru_visibility_and_permissions(): void
    {
        $sa = $this->makeUser('super_admin');
        $admin = $this->makeUser('admin');
        $g1 = $this->makeUser('guru_halaqah');
        $g2 = $this->makeUser('guru_halaqah');
        $mk = fn ($u, $sesi, $tgl = '2026-08-10') => PresensiGuru::create(['user_id' => $u->id, 'tanggal' => $tgl, 'sesi' => $sesi, 'status' => 'hadir']);
        $mine = $mk($g1, 'pagi');
        $mk($g2, 'pagi');
        $mk($g2, 'siang', '2026-08-11');

        $this->as($g1)->getJson('/api/presensi-guru')->assertOk()->assertJsonPath('meta.total', 1);
        $this->as($g1)->getJson("/api/presensi-guru?user_id={$g2->id}")->assertOk()->assertJsonPath('meta.total', 1); // filter user_id diabaikan untuk guru
        $this->as($admin)->getJson('/api/presensi-guru')->assertOk()->assertJsonPath('meta.total', 3);
        $this->as($sa)->getJson('/api/presensi-guru?tanggal=2026-08-11')->assertOk()->assertJsonPath('meta.total', 1);
        $this->as($sa)->getJson("/api/presensi-guru?user_id={$g1->id}&sesi=pagi")->assertOk()->assertJsonPath('meta.total', 1);

        // super_admin tidak membuat presensi; hanya koreksi/hapus.
        $this->as($sa)->postJson('/api/presensi-guru', ['tanggal' => '2026-08-12', 'sesi' => 'pagi', 'status' => 'hadir'])->assertStatus(403);
        $this->as($g1)->putJson("/api/presensi-guru/{$mine->id}", ['tanggal' => '2026-08-10', 'sesi' => 'pagi', 'status' => 'izin', 'keterangan' => 'x'])->assertStatus(403);
        $this->as($admin)->deleteJson("/api/presensi-guru/{$mine->id}")->assertStatus(403);

        $this->as($sa)->putJson("/api/presensi-guru/{$mine->id}", ['tanggal' => '2026-08-10', 'sesi' => 'pagi', 'status' => 'izin', 'keterangan' => 'Ada acara'])
            ->assertOk()->assertJsonPath('data.status', 'izin');
        $mk($g1, 'siang'); // koreksi ke slot yang sudah terisi milik guru yang sama -> konflik
        $this->as($sa)->putJson("/api/presensi-guru/{$mine->id}", ['tanggal' => '2026-08-10', 'sesi' => 'siang', 'status' => 'hadir'])->assertStatus(409);
        $this->as($sa)->deleteJson("/api/presensi-guru/{$mine->id}")->assertNoContent();
    }

    // ---------- Presensi Siswa ----------

    public function test_presensi_siswa_bulk_scoping_and_upsert(): void
    {
        $guru = $this->makeUser('guru_halaqah');
        $lain = $this->makeUser('guru_halaqah');
        $h = $this->makeHalaqah();
        $a = $this->makeSantri(['guru_id' => $guru->id, 'halaqah_id' => $h->id]);
        $b = $this->makeSantri(['guru_id' => $guru->id, 'halaqah_id' => $h->id]);
        $milikLain = $this->makeSantri(['guru_id' => $lain->id, 'halaqah_id' => $h->id]);
        $lepas = $this->makeSantri(['guru_id' => $guru->id]); // bukan anggota halaqah $h

        $payload = fn (array $items) => ['tanggal' => '2026-08-10', 'sesi' => 'pagi', 'halaqah_id' => $h->id, 'items' => $items];

        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([
            ['santri_id' => $a->id, 'status' => 'hadir'],
            ['santri_id' => $b->id, 'status' => 'sakit', 'keterangan' => 'Demam'],
        ]))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.1.keterangan', 'Demam');
        $this->assertDatabaseCount('presensi_siswas', 2);

        // Kirim ulang sesi yang sama = upsert, bukan duplikat/409.
        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([
            ['santri_id' => $a->id, 'status' => 'izin', 'keterangan' => 'Keluarga'],
            ['santri_id' => $b->id, 'status' => 'hadir'],
        ]))->assertOk();
        $this->assertDatabaseCount('presensi_siswas', 2);
        $this->assertSame('izin', PresensiSiswa::where('santri_id', $a->id)->first()->status);
        $this->assertNull(PresensiSiswa::where('santri_id', $b->id)->first()->keterangan);

        // Guru tidak boleh menyentuh siswa guru lain -> seluruh request ditolak.
        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([
            ['santri_id' => $a->id, 'status' => 'alpa'], ['santri_id' => $milikLain->id, 'status' => 'alpa'],
        ]))->assertStatus(403);
        $this->assertSame('izin', PresensiSiswa::where('santri_id', $a->id)->first()->status);

        // Siswa harus anggota halaqah yang dipilih.
        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([['santri_id' => $lepas->id, 'status' => 'hadir']]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.santri_id');

        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([['santri_id' => $a->id, 'status' => 'alpha']]))->assertStatus(422);
        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([['santri_id' => $a->id, 'status' => 'tidur']]))->assertStatus(422);
        $this->as($guru)->postJson('/api/presensi-siswa/bulk', $payload([]))->assertStatus(422);
    }

    public function test_presensi_siswa_read_scoping_and_write_permissions(): void
    {
        $sa = $this->makeUser('super_admin');
        $admin = $this->makeUser('admin');
        $guru = $this->makeUser('guru_halaqah');
        $lain = $this->makeUser('guru_halaqah');
        $h = $this->makeHalaqah('Al-Fatih');
        $mine = $this->makeSantri(['guru_id' => $guru->id, 'halaqah_id' => $h->id]);
        $theirs = $this->makeSantri(['guru_id' => $lain->id]);
        $p1 = PresensiSiswa::create(['santri_id' => $mine->id, 'tanggal' => '2026-08-10', 'sesi' => 'pagi', 'status' => 'hadir']);
        PresensiSiswa::create(['santri_id' => $theirs->id, 'tanggal' => '2026-08-10', 'sesi' => 'pagi', 'status' => 'hadir']);

        $this->as($guru)->getJson('/api/presensi-siswa')->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama_siswa', $mine->nama)->assertJsonPath('data.0.halaqah', 'Al-Fatih');
        $this->as($admin)->getJson('/api/presensi-siswa')->assertOk()->assertJsonPath('meta.total', 2);
        $this->as($sa)->getJson("/api/presensi-siswa?halaqah_id={$h->id}")->assertOk()->assertJsonPath('meta.total', 1);

        $this->as($guru)->putJson("/api/presensi-siswa/{$p1->id}", ['status' => 'sakit'])->assertStatus(403);
        $this->as($guru)->deleteJson("/api/presensi-siswa/{$p1->id}")->assertStatus(403);
        $this->as($sa)->postJson('/api/presensi-siswa/bulk', [])->assertStatus(403);

        $this->as($admin)->putJson("/api/presensi-siswa/{$p1->id}", ['status' => 'sakit', 'keterangan' => 'Demam'])->assertOk()->assertJsonPath('data.status', 'sakit');
        $this->as($sa)->deleteJson("/api/presensi-siswa/{$p1->id}")->assertNoContent();
    }

    // ---------- Setoran ----------

    public function test_setoran_permissions_and_scoping(): void
    {
        $sa = $this->makeUser('super_admin');
        $admin = $this->makeUser('admin');
        $guru = $this->makeUser('guru_halaqah');
        $lain = $this->makeUser('guru_halaqah');
        $mine = $this->makeSantri(['guru_id' => $guru->id]);
        $theirs = $this->makeSantri(['guru_id' => $lain->id]);
        $body = fn ($s) => ['santri_id' => $s->id, 'juz' => 1, 'baris' => 30, 'tanggal' => '2026-08-10'];

        $id = $this->as($guru)->postJson('/api/setoran', $body($mine))->assertStatus(201)
            ->assertJsonPath('data.juz', 1)->assertJsonPath('data.tanggal', '2026-08-10')->json('data.id');
        $this->assertSame($guru->id, Setoran::find($id)->guru_id); // pencatat dari token

        $this->as($guru)->postJson('/api/setoran', $body($theirs))->assertStatus(403);
        $this->as($admin)->postJson('/api/setoran', $body($theirs))->assertStatus(201);
        $this->as($sa)->postJson('/api/setoran', $body($mine))->assertStatus(403); // super_admin hanya baca

        $this->as($guru)->getJson('/api/setoran')->assertOk()->assertJsonPath('meta.total', 1);
        $this->as($sa)->getJson('/api/setoran')->assertOk()->assertJsonPath('meta.total', 2);

        $this->as($guru)->putJson("/api/setoran/$id", array_merge($body($mine), ['baris' => 12]))->assertOk()->assertJsonPath('data.baris', 12);
        $this->as($guru)->putJson("/api/setoran/$id", $body($theirs))->assertStatus(403); // tidak boleh memindah ke siswa orang lain
        $theirsId = Setoran::where('santri_id', $theirs->id)->value('id');
        $this->as($guru)->putJson("/api/setoran/$theirsId", $body($mine))->assertStatus(403);
        $this->as($guru)->deleteJson("/api/setoran/$theirsId")->assertStatus(403);
        $this->as($sa)->deleteJson("/api/setoran/$id")->assertStatus(403);
        $this->as($guru)->deleteJson("/api/setoran/$id")->assertNoContent();
    }

    public function test_setoran_validation_and_default_date(): void
    {
        $admin = $this->makeUser('admin');
        $s = $this->makeSantri();

        $this->as($admin)->postJson('/api/setoran', ['santri_id' => $s->id, 'juz' => 31, 'baris' => 1])->assertStatus(422)->assertJsonValidationErrors('juz');
        $this->as($admin)->postJson('/api/setoran', ['santri_id' => $s->id, 'juz' => 1, 'baris' => 0])->assertStatus(422)->assertJsonValidationErrors('baris');
        $this->as($admin)->postJson('/api/setoran', ['santri_id' => $s->id, 'juz' => 1, 'baris' => 5])
            ->assertStatus(201)->assertJsonPath('data.tanggal', now()->toDateString());
    }

    // ---------- Kenaikan Juz ----------

    public function test_kenaikan_juz_create_delete_and_conflict(): void
    {
        $sa = $this->makeUser('super_admin');
        $admin = $this->makeUser('admin');
        $guru = $this->makeUser('guru_halaqah');
        $penguji = $this->makeUser('guru_halaqah');
        $mine = $this->makeSantri(['guru_id' => $guru->id]);
        $theirs = $this->makeSantri();

        $id = $this->as($guru)->postJson('/api/kenaikan-juz', ['santri_id' => $mine->id, 'juz' => 1, 'penguji_id' => $penguji->id])
            ->assertStatus(201)
            ->assertJsonPath('data.nilai_hafalan', null)->assertJsonPath('data.nilai_soal', null)
            ->assertJsonPath('data.penguji.id', $penguji->id)
            ->assertJsonPath('data.can_input_hafalan', true)->assertJsonPath('data.can_input_soal', false)
            ->json('data.id');

        $this->as($guru)->postJson('/api/kenaikan-juz', ['santri_id' => $mine->id, 'juz' => 1])->assertStatus(409);
        $this->as($guru)->postJson('/api/kenaikan-juz', ['santri_id' => $theirs->id, 'juz' => 1])->assertStatus(403);
        $this->as($admin)->postJson('/api/kenaikan-juz', ['santri_id' => $theirs->id, 'juz' => 1])->assertStatus(201);
        $this->as($sa)->postJson('/api/kenaikan-juz', ['santri_id' => $mine->id, 'juz' => 2])->assertStatus(403);
        $this->as($admin)->postJson('/api/kenaikan-juz', ['santri_id' => $mine->id, 'juz' => 2, 'penguji_id' => $admin->id])->assertStatus(422); // penguji harus guru

        $this->as($guru)->deleteJson("/api/kenaikan-juz/$id")->assertStatus(403);
        $this->as($sa)->deleteJson("/api/kenaikan-juz/$id")->assertStatus(403);
        $this->as($admin)->deleteJson("/api/kenaikan-juz/$id")->assertNoContent();
    }

    public function test_kenaikan_juz_visibility(): void
    {
        $sa = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');
        $penguji = $this->makeUser('guru_halaqah');
        $asing = $this->makeUser('guru_halaqah');
        $mine = $this->makeSantri(['guru_id' => $guru->id]);
        $other = $this->makeSantri();
        KenaikanJuz::create(['santri_id' => $mine->id, 'juz' => 1, 'penguji_id' => $penguji->id]);
        KenaikanJuz::create(['santri_id' => $other->id, 'juz' => 1]);

        $this->as($guru)->getJson('/api/kenaikan-juz')->assertOk()->assertJsonPath('meta.total', 1);
        $this->as($penguji)->getJson('/api/kenaikan-juz')->assertOk()->assertJsonPath('meta.total', 1);
        $this->as($asing)->getJson('/api/kenaikan-juz')->assertOk()->assertJsonPath('meta.total', 0);
        $this->as($sa)->getJson('/api/kenaikan-juz')->assertOk()->assertJsonPath('meta.total', 2);
        $this->as($sa)->getJson('/api/kenaikan-juz?juz=2')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_kenaikan_juz_nilai_rules(): void
    {
        $admin = $this->makeUser('admin');
        $pengampu = $this->makeUser('guru_halaqah');
        $penguji = $this->makeUser('guru_halaqah');
        $asing = $this->makeUser('guru_halaqah');
        $santri = $this->makeSantri(['guru_id' => $pengampu->id]);
        $ujian = KenaikanJuz::create(['santri_id' => $santri->id, 'juz' => 1, 'penguji_id' => $penguji->id]);
        $url = "/api/kenaikan-juz/{$ujian->id}/nilai";

        // Pengampu: hanya nilai hafalan.
        $this->as($pengampu)->patchJson($url, ['nilai_hafalan' => 85])->assertOk()
            ->assertJsonPath('data.nilai_hafalan', 85)->assertJsonPath('data.nilai_soal', null);
        $this->as($pengampu)->patchJson($url, ['nilai_soal' => 90])->assertStatus(403);
        $this->as($pengampu)->patchJson($url, ['nilai_hafalan' => 90, 'nilai_soal' => 90])->assertStatus(403);
        $this->assertSame(85, $ujian->fresh()->nilai_setoran);

        // Penguji: hanya nilai soal.
        $this->as($penguji)->patchJson($url, ['nilai_soal' => 70])->assertOk()
            ->assertJsonPath('data.nilai_soal', 70)->assertJsonPath('data.nilai_hafalan', 85)
            ->assertJsonPath('data.can_input_hafalan', false)->assertJsonPath('data.can_input_soal', true);
        $this->as($penguji)->patchJson($url, ['nilai_hafalan' => 100])->assertStatus(403);

        // Pihak lain & admin tidak boleh menilai.
        $this->as($asing)->patchJson($url, ['nilai_hafalan' => 1])->assertStatus(403);
        $this->as($asing)->patchJson($url, ['nilai_soal' => 1])->assertStatus(403);
        $this->as($admin)->patchJson($url, ['nilai_hafalan' => 1])->assertStatus(403);

        // Validasi.
        $this->as($pengampu)->patchJson($url, [])->assertStatus(422);
        $this->as($pengampu)->patchJson($url, ['nilai_hafalan' => 101])->assertStatus(422);

        // Merangkap pengampu dan penguji: boleh keduanya sekaligus.
        $ujian2 = KenaikanJuz::create(['santri_id' => $santri->id, 'juz' => 2, 'penguji_id' => $pengampu->id]);
        $this->as($pengampu)->patchJson("/api/kenaikan-juz/{$ujian2->id}/nilai", ['nilai_hafalan' => 80, 'nilai_soal' => 75])->assertOk()
            ->assertJsonPath('data.nilai_hafalan', 80)->assertJsonPath('data.nilai_soal', 75);
    }
}
