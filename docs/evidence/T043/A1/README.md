# T043/A1 — Tracking customer sebagai tanda terima digital

Verdict implementer: **PASS** (menunggu QA independen T044).

Halaman tracking diubah dari kartu kecil yang mengambang menjadi satu lembar tanda terima digital selebar maksimum 920px. Hierarki baru: identitas toko → status dan nomor TTS → progres empat tahap → rincian barang → langkah berikutnya atau rincian nota/garansi/PDF → kontak toko. Desktop memakai dua kolom pada rincian, sedangkan HP menjadi satu kolom. Data, controller, route, header privasi dan dependency tidak berubah.

Pemeriksaan:

- `php artisan view:cache`: PASS.
- `php artisan test tests/Feature/Tracking/TrackingTest.php`: PASS, 2 tes / 48 assertion.
- `npm run build`: PASS, 25 modul.
- CI penuh PHP 8.3: Pint PASS, PHPStan 0 error, PHPUnit 190 tes (189 PASS, 1 skipped) / 1.995 assertion.
- `git diff --check`: PASS.
- Audit diff `app`, `routes`, `.github`, dependency/lockfile: tidak ada perubahan.
- Browser SQLite sintetis: Diterima, Selesai/bernota dan Batal diperiksa; 360, 768, 1280 px tanpa overflow yang tampak. Tombol PDF 44px dan label status tetap berbentuk teks.
- HTML/test memastikan HP, alamat customer, modal dan token internal tidak ditampilkan; `noindex` dan `private, no-store` tetap diuji.

Screenshot CUA ditinjau saat eksekusi tetapi tidak disimpan sebagai berkas repo. Fixture browser hanya berada di `/tmp/repair-search-select.sqlite`; status sintetis yang diubah untuk observasi Batal sudah dikembalikan.

Tidak ada commit, push, merge, deploy atau perubahan workflow GitHub Actions.
