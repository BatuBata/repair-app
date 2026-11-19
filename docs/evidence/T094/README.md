# T094 — pengambilan TTS Batal

D-57, main lokal, tanpa push/deploy. Action collect existing mendapat cabang Batal setelah pemeriksaan admin/tenant dan lock baris: isi picked_up_on hari ini WITA bila masih null, lalu selesai sebelum perubahan status atau pembayaran. Request ulang tidak menulis lagi. Tidak menambah status, field, migrasi, dependency, form tanggal atau alur nota baru.

Admin TTS batal belum diambil mendapat tombol Tandai barang diambil (desktop dan actionbar HP). Setelah diambil tombol hilang; admin/teknisi readonly melihat Barang sudah diambil +tanggal. Link pelanggan menampilkan pengambilan dan tanggal bersama alasan pembatalan; badge tetap Batal dan timeline Diambil memakai tanggal existing.

## Bukti aktual

- PHP8.3 `artisan test --compact tests/Feature/Invoice tests/Feature/Tracking tests/Feature/Reports tests/Feature/Dashboard/DashboardTest.php`:66PASS716assert3138ms, sebelum test independen baru ditambahkan. Parametrized existing InvoiceTest disesuaikan dengan aturan baru Batal; aturan pickup biasa tetap teruji.
- QA independen CancelledPickupTest:4kasusPASS46assert455ms; setelah tambahan4assert akses teknisi sesudah pickup, hanya kasus berubah diulang1PASS33assert366ms. Totalcakupan4kasus final50assert; tiga kasus lain unchangedPASS. Meliputi tanggalhariWITA saatUTCbelumganti hari,clientdate/paymentdiabaikan, seluruhfieldlainidentik, stale/retry tidakubahdate/updated_at, role/tenant/inactive, tracking/privacy, laporan0/dashboardBatal1/Diambil0.
- PHP8.3 `vendor/bin/phpstan analyse --memory-limit=512M --no-progress`:PASS0error. PHP8.3 `vendor/bin/pint --dirty --test` PASS; `git diff --check`PASS.
- CUA fixtureSQLite terpisah `/private/tmp/repair-t094.sqlite`, tanpa DBoperator. Sebelum: Batal/Barangbelumdiambil/button terlihat. Klikbutton pada viewport375 menyimpan date19Nov2026, status tetapBatal, button0; sesudah logout,tracking publik memuat Barang sudahdiambil/date/reason pada1280/375,nooverflow. BrowserJSON+screenshotbefore/after desktop/HP disimpan. Dua-tab viewport awal hanya mengubah tab aktif; diselesaikan dengan satu tab dan innerWidth375 diverifikasi sebelum klik. Serverfixture/tabagent ditutup,viewportdipulihkan.
- Manifest6file runtime/tests sha256:566c56e9c776ed73f1786f5aaa4c0b4f6f958e8bf54eb6d26693c1b12fb93e7d. QA independen memeriksa pin dan screenshot, detail independent-qa.md.

Build/suite penuh/MySQL tidak diulang untuk cabang satu-update tanpa perubahan skema/aset/utility; pengujian perilaku dan regresi target dilakukan. Tidak mengubah data produksi, mengirim WA atau menguji perangkat fisik. Rilis produksi sebelumnya tetap3b54261; perubahan ini lokal sesuai instruksi user.
