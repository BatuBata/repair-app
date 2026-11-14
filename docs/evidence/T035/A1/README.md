# T035 A1 — QA independen placeholder

**Verdict: FAIL.** Snapshot lokal T034 cocok dengan pin. Input angka pada Pengaturan toko tidak mendapat placeholder ketika kosong.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; panduan Project Delivery dan `references/qa-and-release.md` juga dibaca. Peran sesi: tester independen. Hanya folder bukti T035/A1 ditulis, tanpa perbaikan source atau perubahan ledger.

## Snapshot yang diperiksa

- Repo: `/Users/muzib/Projects/web/repair-app`.
- HEAD: `89e1c5b739c9d7036b085ba8753ea1c0eae2d6d2`.
- SHA256 `git diff --binary HEAD -- resources/views tests/Feature/ScaffoldTest.php`: `86e6efb12b28f812b425b2b6024bf0b9e3802c1e5e5d4c6c035325b0b556cd50`.
- Workflow working tree dan HEAD byte-identik, SHA256 keduanya `51c6495785cde17fff7481c57ae73d08af91eaa5596165c12cc7d286c0a01739`.
- Instruksi yang diperiksa: PRD D-25/D-26, kontrak T034/T035, seluruh source diff, seluruh lokasi input/textarea/select serta pemakaian `x-ui.field` dan `x-ui.money` di Blade.

## Temuan yang harus diperbaiki

`resources/views/components/ui/field.blade.php:11` memakai `@if($placeholder)`. PHP menilai string `"0"` sebagai false. Cabang `type="number"` menetapkan `"0"`, tetapi kondisi tersebut menghilangkan atributnya.

Reproduksi aktual:

```sh
/opt/homebrew/opt/php@8.3/bin/php docs/evidence/T035/A1/render-probe.php
```

Expected: komponen angka kosong memiliki `placeholder="0"`. Actual: tidak ada atribut placeholder. Exit 1. Kasus eksplisit `placeholder="0"` pada field teks juga gagal dengan penyebab yang sama. Probe lainnya lulus; field terisi mempertahankan `value="Budi"` dan atribut placeholder terpisah.

Caller nyata yang terdampak adalah `resources/views/livewire/settings/store.blade.php:47`, `default_warranty_days`, label “Default hari garansi”. Nilai awal 30 menyembunyikan celah ini; ketika dikosongkan petunjuk tidak muncul. Input garansi penjualan bertipe `text` dan menerima petunjuk generik, sehingga bukan caller yang gagal ini. Tingkat dampak: P2, kriteria cakupan D-26 belum terpenuhi.

## Pemeriksaan aktual dan hasil

| Pemeriksaan/perintah | Expected | Actual |
| --- | --- | --- |
| `git rev-parse HEAD`; hash diff dengan Python `hashlib.sha256(subprocess.check_output(['git','diff','--binary','HEAD','--','resources/views','tests/Feature/ScaffoldTest.php']))` | Sama dengan pin | PASS, hash persis sama seperti di atas |
| Perbandingan bytes workflow menggunakan `Path('.github/workflows/tests.yml').read_bytes()` dan `git show HEAD:.github/workflows/tests.yml` | Identik | PASS |
| `git status --short`, `git diff --name-only`, `git reflog -1 --format='%H %gs'` | Candidate tetap lokal, HEAD sama | PASS untuk keadaan lokal; latest reflog hanya checkout ke `codex/form-placeholders` |
| `rg -n '<input\|<textarea\|<select\|<x-ui\.(field\|money)' resources/views`, dilengkapi enumerasi tag memakai Python dan review type dinamis penjualan | Seluruh supported controls tercakup; unsupported tidak dipaksa | Raw controls PASS; komponen angka FAIL ketika dirender |
| `/opt/homebrew/opt/php@8.3/bin/php artisan test --filter=ScaffoldTest --no-ansi` | Semua tes terarah lulus | PASS, 5 tes, 29 assertion, 344 ms |
| `render-probe.php` di atas | Semua placeholder default/override dirender | FAIL, default number dan override string 0 hilang; enam kasus lainnya PASS |
| `node docs/evidence/T035/A1/native-placeholder.mjs` | `:placeholder-shown` true pada kosong, false saat terisi, true setelah clear | NOT_RUN: sandbox EPERM; satu retry berizin melewati sandbox menghasilkan ECONNREFUSED pada Chrome lokal port 9235, sebelum tab dibuat |
| `rg -n -i 'placeholder\|setAttribute\([^\n]*value\|\.value\s*=' resources/js` dan review CSS placeholder | Placeholder memakai atribut native, tidak disalin sebagai nilai | PASS pada source; script nilai yang ada hanya pemformat nominal |
| Inspeksi visual tiga PNG T034 dan hash independen | Empat teks pengguna tepat, tidak terpotong pada 360/768/1280 | PASS |

Audit menginventarisasi 27 tag input raw (termasuk dua komponen), 4 textarea, 4 select, 21 pemakaian `x-ui.field`, dan 5 pemakaian `x-ui.money`; tag di loop dihitung sebagai lokasi source. Tidak ada raw supported input atau textarea yang kehilangan atribut placeholder. Date, select, file, radio, checkbox dan hidden yang digunakan tidak dipaksa memiliki placeholder. Komponen bersama memerlukan perbaikan kondisi string 0 agar cakupan runtime lengkap.

## Screenshot yang diperiksa

Ketiganya benar-benar dibuka dan dilihat tester. Nama `mis. Budi Santoso`, username `mis. budi`, password `Minimal 9 karakter`, serta konfirmasi `Ulangi password baru` terlihat lengkap. Label dan opsi peran tetap terlihat; layout tidak menunjukkan clipping pada capture.

| File T034/A1 | SHA256 |
| --- | --- |
| `users-create-360.png` | `146696b1c77c8206b5ebe228eaa2b7a8ec2d4e9ab89c45bc85a829b71801aa36` |
| `users-create-768.png` | `9b6eae204a66d88e9bcc2451e34bc36164fba3dceb4aa01070dfd4270940179e` |
| `users-create-1280.png` | `e716b3ac21c12b42973a838b712783d9ee800c4c22db96f03da05da7e24081cb` |

## Batas pemeriksaan

Tidak ada commit, push, merge, deploy, atau operasi GitHub/remote selama QA ini. Keadaan aktif workflow remote menggunakan bukti implementer T034, tidak dibaca ulang dalam QA; kesamaan file workflow lokal dibuktikan langsung. Pernyataan tidak melakukan remote mutation berlaku untuk aksi sesi tester ini, bukan audit seluruh aktor GitHub.

Full suite tidak diulang: implementer telah melaporkan full CI PASS pada snapshot sama; pemeriksaan independen terarah justru menemukan kasus yang tidak dicakup tes tersebut. Tidak menyentuh MySQL/demo, tidak melakukan transaksi simpan, dan tidak menguji perangkat fisik. Pengisian/clear langsung di browser NOT_RUN karena endpoint lokal tidak aktif; source menunjukkan atribut native terpisah dari value, tetapi hasil interaksi browser tidak diklaim PASS.
