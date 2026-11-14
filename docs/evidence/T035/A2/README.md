# T035 A2 — QA selektif perbaikan placeholder angka

**Verdict: PASS untuk snapshot lokal A2.** Temuan A1 terselesaikan tanpa regresi yang teramati pada cakupan yang diperiksa. Laporan FAIL A1 dipertahankan.

Ponytail **full** tetap memakai sumber yang telah dibaca: `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; panduan Project Delivery/QA yang dibaca pada A1 tetap berlaku. Tester hanya menulis bukti di T035/A2 dan tidak mengubah source, PRD, TODO, atau GitHub.

## Pin dan perubahan A1 ke A2

- HEAD: `89e1c5b739c9d7036b085ba8753ea1c0eae2d6d2`.
- SHA256 `git diff --binary HEAD -- resources/views tests/Feature/ScaffoldTest.php`: `2d31e5b2b6091fa2d301907bbef59b3301cf3bdc347a15333a473f51a998aa07`.
- Source A1: `86e6efb12b28f812b425b2b6024bf0b9e3802c1e5e5d4c6c035325b0b556cd50`.
- Review diff: kondisi komponen berubah dari `@if($placeholder)` menjadi `@if($placeholder !== null)`; tes menambah satu field `quantity` bertipe number dan satu assertion `placeholder="0"`.
- Verifikasi independen cakupan perubahan: membalik tiga perubahan tersebut pada diff A2, termasuk metadata blob/hunk Git yang sesuai, menghasilkan hash A1 persis. Tidak ada perubahan source lain di antara kedua snapshot.

## Pemeriksaan aktual

| Perintah/langkah | Expected | Actual |
| --- | --- | --- |
| `git rev-parse HEAD` dan Python SHA256 atas `git diff --binary HEAD -- resources/views tests/Feature/ScaffoldTest.php` | Sama dengan pin A2 | PASS, sama dengan pin di atas |
| `/opt/homebrew/opt/php@8.3/bin/php docs/evidence/T035/A1/render-probe.php` | Semua 8 kasus lulus | PASS, exit 0; number dan override string 0 kini memiliki placeholder `0`; text, tel, password, filled, date dan file juga lulus |
| `/opt/homebrew/opt/php@8.3/bin/php artisan test --filter=ScaffoldTest --no-ansi` | Semua tes terarah lulus | PASS, 5 tes, 30 assertion, 337 ms |
| Render caller asli `default_warranty_days` yang diekstrak dari `resources/views/livewire/settings/store.blade.php` melalui PHP CLI/`Blade::render`, dengan `ViewErrorBag` kosong | `type="number"`, `placeholder="0"` | PASS, output `{"caller":"default_warranty_days","type":"number","placeholder":"0","pass":true}` |
| Review explicit null dan nilai terisi | `0` tidak dibuang, nilai null untuk date/file tidak dirender; value terisi tetap terpisah | PASS; output probe mempertahankan `value="Budi"` dengan `placeholder="Masukkan nama"` |
| Python perbandingan bytes `.github/workflows/tests.yml` dengan `git show HEAD:.github/workflows/tests.yml` | Identik | PASS |
| `git status --short`, `git reflog -1 --format='%H %gs'` | Candidate tetap lokal dan HEAD tidak bergeser | PASS, tidak ada commit baru; latest reflog tetap checkout cabang |

Kondisi baru memeriksa null secara eksplisit dan tetap mengeluarkan teks melalui escaping Blade. Branch default date/datetime-local/file tetap null; tidak ada perubahan nilai input, validasi, binding Livewire, label, atau layout. Tidak ditemukan celah lain pada perubahan A2 yang terbatas ini.

## PASS A1 yang dipakai kembali

Producer: tester independen `/root/t035_placeholder_qa`, laporan `docs/evidence/T035/A1/README.md`, assessment `docs/evidence/T035/A1/assessment.json`, pin A1 di atas. Pemakaian ulang terbatas pada kasus PASS berikut, bukan pada verdict FAIL A1 secara keseluruhan.

| Case | Bukti asal | Alasan tetap berlaku pada A2 |
| --- | --- | --- |
| `raw-control-coverage` | Audit A1: 27 input raw, 4 textarea, 4 select, 21 pemakaian field, 5 money; tidak ada raw supported control tanpa placeholder | Seluruh raw control/caller sama, dibuktikan hash rekonstruksi A1. Komponen angka yang gagal telah diuji ulang terpisah |
| `unsupported-controls` | Audit A1 date/select/file/radio/checkbox/hidden tidak dipaksa | Caller tidak berubah; date/file juga diuji ulang oleh probe pada A2 |
| `users-placeholder-text` | Empat teks pengguna diperiksa A1 dan screenshot | Teks/name/username/password/confirmation tidak berubah; ScaffoldTest pada A2 mengujinya lagi |
| `users-visual-360-768-1280` | Inspeksi tester atas tiga PNG T034/A1 | Layout/caller dan placeholder nonzero tidak berubah; SHA256 tiga PNG diverifikasi ulang sama |
| `workflow-bytes` | Workflow byte-identik pada A1 | File tidak berubah; perbandingan bytes diulang dan lulus pada A2 |

Hash screenshot yang dikonfirmasi ulang:

- `docs/evidence/T034/A1/users-create-360.png`: `146696b1c77c8206b5ebe228eaa2b7a8ec2d4e9ab89c45bc85a829b71801aa36`.
- `docs/evidence/T034/A1/users-create-768.png`: `9b6eae204a66d88e9bcc2451e34bc36164fba3dceb4aa01070dfd4270940179e`.
- `docs/evidence/T034/A1/users-create-1280.png`: `e716b3ac21c12b42973a838b712783d9ee800c4c22db96f03da05da7e24081cb`.

## Batas

Full suite tidak diulang; implementer melaporkan full CI PASS pada pin A2, sementara tester mengulang kasus gagal dan subset regresi yang relevan. Browser fill/clear yang NOT_RUN pada A1 tidak diubah menjadi PASS dan tidak dicoba ulang; kesimpulan hanya-saat-kosong bertumpu pada atribut HTML native yang terpisah dari value, review source, serta pemeriksaan render. Tidak ada transaksi simpan, MySQL/demo, perangkat fisik, commit/push/merge/deploy, atau akses/mutasi GitHub dalam sesi QA ini. Status aktif workflow remote tetap memakai bukti implementer dan tidak diinspeksi ulang.
