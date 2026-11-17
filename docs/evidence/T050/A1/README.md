# T050 A1 — QA independen pembayaran dan filter laporan

Tanggal: 17 November 2026 (WITA)

## Target dan mode

- Target commit: `1d8c2a9c6b6d51382d1f79aaa7061c9f294c8f8b` (`main`, sebelum push).
- Hash gabungan 21 file implementasi/tes: `sha256:1e8464a094ec3d8a3b4db6f37e240cdd02229b7df1a009ccc19ccfcbe1fa0ccc` (cocok dengan T049).
- Input T049: `sha256:1e8464a094ec3d8a3b4db6f37e240cdd02229b7df1a009ccc19ccfcbe1fa0ccc`.
- Tester: `/root/t050_payment_qa`, terpisah dari implementer `/root`.
- Model diminta: `gpt-6.1-sol`; receipt model efektif tidak tersedia dari runtime worker.
- Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`.

## Hasil yang lulus

1. **Audit diff/pin**
   - Commit dan hash file cocok dengan handoff T049.
   - Tidak ada perubahan dependency, lockfile, route, atau config.
   - Migrasi hanya menambah `payment_method`, `payment_bank`, dan `paid_at` nullable pada `service_orders` dan `sales`; tidak ada backfill, drop, atau update data.

2. **Aturan bisnis dan laporan**
   - `php artisan test tests/Feature/Invoice/InvoiceTest.php tests/Feature/Sales/SalesTest.php tests/Feature/Reports/ReportsTest.php`
   - Hasil: **PASS — 50 tes, 430 assertion**.
   - Tunai/QRIS tanpa bank, transfer wajib bank, pembayaran servis sebelum/saat pengambilan, serta filter metode/bank terbukti.
   - Filter transfer menghasilkan satu baris penjualan dan total daftar `Rp75.000`, sedangkan ringkasan periode tetap `Laba bersih Rp225.000 dari omzet Rp425.000`.

3. **CI lokal penuh**
   - `composer ci:check` dengan PHP 8.3 dan izin loopback Pint.
   - Hasil: **PASS** — Pint, PHPStan 0 error, PHPUnit 230 pass + 1 existing skipped, 2.458 assertion.
   - Percobaan awal tanpa PATH PHP 8.3 berhenti pada guard versi PHP 7.4; percobaan sandbox berikutnya berhenti karena Pint parallel tidak dapat membuka port loopback. Keduanya kegagalan environment, bukan product; perintah yang benar kemudian lulus.

4. **View/build/diff**
   - `php artisan view:cache`: **PASS**.
   - `npm run build`: **PASS**; hanya peringatan optional `fontaine` dan font lokal existing.
   - `git diff --check`: **PASS**.

5. **Migrasi dan data lama pada database terisolasi**
   - Migrasi penuh pada SQLite sintetis: **PASS**.
   - Database kedua dimigrasikan hanya sampai schema lama, diisi satu servis dan satu penjualan sintetis, lalu migration pembayaran dijalankan.
   - Actual: kedua baris lama tetap memiliki `payment_method = null`, `payment_bank = null`, `paid_at = null`; tidak ada backfill.
   - Tidak menyentuh `repair_app`, `repair_app_test`, database demo, atau data pengguna.

6. **Browser sintetis**
   - Login, laporan, detail servis, dan form penjualan diuji melalui Chrome pada database SQLite terisolasi.
   - Laporan menampilkan `Belum dicatat`, `Transfer bank · BCA`, filter metode dan filter bank; filter benar-benar memperkecil 2 transaksi menjadi 1 tanpa mengubah ringkasan periode.
   - Detail servis belum dibayar menampilkan `Bayar & Diambil`; memilih Transfer menampilkan selectbox bank.
   - Form penjualan menampilkan Tunai/QRIS/Transfer dan selectbox bank hanya untuk Transfer.
   - Tidak ada overflow horizontal pada tiga state viewport yang diminta. Backend Chrome memakai zoom sistem 80%, sehingga input 390/768/1440 menghasilkan CSS viewport efektif 487/960/1800; seluruh state mobile/tablet/desktop tetap terjangkau, tetapi nilai CSS pixel exact bukan 390/768/1440.

7. **Gate CI MariaDB dan deployment**
   - GitHub Actions run `44087631993` pada commit target yang sama selesai **success**.
   - Job `ci` memakai service `mariadb:10.11`; semua CI checks, deployment safety, packaging, dan upload artifact lulus.
   - Job `deploy` memasang artifact `release-1d8c2a9...` dan melaporkan `DEPLOYED 1d8c2a9...; HTTPS health/login PASS`.
   - Pemeriksaan coordinator sesudah workflow: `/up` HTTP 200 dan `/login` HTTP 200.
   - Bukti exact CSS viewport 390/768/1440 dari T049 diterima untuk bagian yang tidak dapat diulang tester akibat zoom browser 80%; keterbatasan independensi dipertahankan di bawah.

## Verdict

**PASS.** Gate MariaDB 10.11 yang sebelumnya terblokir lokal telah dipenuhi oleh GitHub CI pada commit yang sama. Source, aturan bisnis, migrasi/data lama, regresi, build, UI, artifact deployment, dan HTTPS lulus.

## Dilewati dan risiko

- MariaDB 10.11 aktual tidak dijalankan di laptop karena Docker daemon tidak aktif; penggantinya adalah service MariaDB 10.11 pada GitHub CI yang lulus.
- Tester independen tidak mengubah server; deployment dan smoke test produksi dijalankan workflow terkontrol lalu diverifikasi coordinator.
- Backup tetap ditunda sesuai D-22; migrasi aditif nullable berisiko rendah, tetapi rollback aplikasi tidak menghapus kolom secara otomatis.
- Exact-width 390/768/1440 berasal dari pemeriksaan coordinator T049; tester independen mengulang state mobile/tablet/desktop pada CSS viewport efektif 487/960/1800 karena zoom browser 80%.
