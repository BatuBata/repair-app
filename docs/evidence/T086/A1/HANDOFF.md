# T086 — kandidat A1

Implementasi master kategori pengeluaran per toko mengikuti Bank. Empat bawaan tetap berasal dari enum existing; status aktif/nonaktif disimpan per tenant tanpa backfill transaksi. Master tambahan mendukung tambah, ubah, hapus, status, dan pencarian. Nama bawaan dilindungi tanpa membedakan kapital. Route admin: `/pengaturan/kategori-pengeluaran`, terhubung dari nav pengaturan.

`expenses.category` diperluas dari ENUM menjadi varchar(50), dan cast enum PHP pada Expense dihapus. Nilai transaksi merupakan snapshot teks; rename/hapus/nonaktif master tidak mengubah transaksi. Simpan dari komponen dan model memvalidasi kategori aktif toko. Edit hanya mendapat pengecualian bagi kategori asli dari baris yang dibaca ulang dari database, bukan dari nilai publik komponen. Isolasi tenant dan guard admin memakai pola existing.

## Pemeriksaan aktual

- PHP 8.3: `artisan test --compact tests/Feature/Settings/ExpenseCategoriesTest.php tests/Feature/Expenses/ExpensesTest.php tests/Feature/Reports/ReportsTest.php tests/Feature/Tenancy/CommerceIsolationTest.php` — PASS 25 tes / 376 assertions.
- PHP 8.3: `artisan test --compact tests/Feature/Expenses/ExpenseCategoriesMigrationTest.php tests/Feature/Tenancy/MigrationTest.php tests/Feature/Settings/BanksTest.php tests/Feature/Settings/SettingsTest.php tests/Feature/Domain/DomainTest.php tests/Feature/Invoice/ServiceRevisionsTest.php` — PASS 41 tes / 544 assertions.
- Pint terhadap 9 file PHP task — PASS setelah perapian urutan import; perbaikan akhir dokumentasi tipe helper juga PASS.
- PHPStan seluruh konfigurasi proyek: `vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` — PASS 0 error. Pemeriksaan awal menemukan 3 error dokumentasi tipe pada helper baru; semuanya diperbaiki sebelum freeze. Mode awal tanpa `--debug` terhalang socket worker sandbox, bukan temuan aplikasi.
- MySQL 8.0.31 fixture nyata — PASS. Reproducer `REPAIR_MYSQL_ENV_FILE=/Users/muzib/Projects/web/repair-app/.env /opt/homebrew/opt/php@8.3/bin/php docs/evidence/T086/A1/mysql-proof.php` hanya membaca environment privat, memakai database uji lokal dan tiga nama tabel prefiks acak. Empat baris existing fixture identik setelah up/down/up; varchar(50) menerima snapshot custom 50 karakter; rollback tidak aman ditolak sebelum perubahan. Dua puluh tabel baseline pre-existing identik pada jumlah baris, hash baris, dan hash schema; ketiga tabel milik fixture sudah dihapus. Rincian tanpa kredensial: `mysql-proof.json`. Resource `db:mysql-test` dilepas.
- Akses awal MySQL sandbox tertolak; akses berikutnya berhasil melalui izin eksekusi. Berkas env worktree tidak memuat username MySQL sehingga reproducer membaca env checkout utama secara privat. Query baseline semula mencakup schema lain; dibatasi eksplisit ke `repair_app_test` sebelum membuat fixture apa pun. Tidak ada migrasi/reset pada tabel operator.

Manifest SHA-256 source task tersedia di `source-manifest.json`; perubahan desain/ledger/form penjualan milik coordinator tidak termasuk. Semua tes perilaku lulus sebelum perbaikan dokumentasi tipe terakhir yang tidak mengubah perilaku; suite gabungan akhir ditangani coordinator.

Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dan kontrak worker dibaca. DESIGN.md dan pola halaman Bank menjadi acuan tampilan; tidak ada sistem desain baru.

Belum diuji worker: browser/layar nyata, suite gabungan akhir, QA independen, deploy. Tidak ada commit/push/deploy atau perubahan database operator/produksi. Rollback perlu mempertahankan varchar sampai seluruh snapshot custom ditangani; rollback otomatis menolak penyempitan yang dapat merusak nilai.
