# T087 — backend diskon penjualan

Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dan kontrak worker juga dibaca. Peran implementer, tidak mengubah ledger/view/Git. Backend, migrasi, dan tests dibekukan sesuai `source-manifest.json`; view dikerjakan coordinator secara terpisah.

Satu diskon nominal rupiah transaksi disimpan di `sales.discount`: default 0, integer 0–PHP_INT_MAX, maksimum subtotal hasil hitung server. Diskon melebihi subtotal ditolak di dalam transaksi database sehingga stok yang sempat diperbarui ikut dibatalkan. Harga/modal per unit, kuantitas, serta alur bayar tetap. Diskon penuh valid termasuk total0 dan pembayaran cash existing. Form mengirim diskon dan menyediakan `previewSubtotal`/`previewTotal`; input invalid menghasilkan total null, tanpa clamp. Dokumen memakai template diskon existing. Laporan menambah satu baris Diskon bermodal0; dashboard menguranginya satu kali per penjualan setelah loop item dengan scope tanggal/tenant existing.

## Pemeriksaan aktual

- `/opt/homebrew/opt/php@8.3/bin/php artisan test tests/Feature/Sales tests/Feature/Reports tests/Feature/Dashboard tests/Feature/Documents --compact` — PASS, 53 tests / 708 assertions, 2.980 detik terlapor (`targeted-tests.log`). Delapan kasus baru mencakup nilai negatif/pecahan/kosong/array/lebih subtotal/overflow, rollback stok/customer/counter, default0, diskon penuh, integer PHP maksimum persis, preview reaktif jumlah/harga/diskon dan invalid null, harga/modal tetap, hapus mengembalikan stok, total daftar/detail/HTML/dokumen PDF, biaya/laba, bank/metode/tanggal/dashboard, tenant/auth, validasi model dan guard rollback migrasi.
- Pint `--test` pada delapan source file manifest — PASS. `git diff --check` — PASS. PHP syntax evidence script — PASS.
- `REPAIR_MYSQL_ENV_FILE=/Users/muzib/Projects/web/repair-app/.env /opt/homebrew/opt/php@8.3/bin/php docs/evidence/T087/A1/mysql-proof.php` — PASS MySQL8.0.31, 4 pemeriksaan (`mysql-proof.json`). Migrasi asli dijalankan atas satu tabel fixture prefiks acak milik task: unsigned BIGINT default0; field baris lama dua tenant tetap; nilai PHP_INT_MAX persis; rollback dengan diskon ditolak tanpa perubahan; rollback aman dan up ulang melestarikan field lama. Seluruh20 tabel database uji existing identik pada jumlahbaris/hashbaris/hashschema. Tabel fixture task sudah dihapus, resource db:mysql-test dilepas. Tidak memigrasikan database operator/produksi.

## Riwayat yang dipertahankan

Run awal8 kasus menghasilkan7 PASS/1 FAIL karena pengujian memakai ulang komponen Livewire yang masih menyimpan errorbag invalid setelah redirect valid (`first-test-run.json`). Test diperbaiki menjadi komponen baru pada skenario simpan sukses sesuai lifecycle halaman; source aplikasi tidak perlu diubah. Run gabungan final di atas PASS.

Bootstrap pertama skrip bukti menyalin pola T086 yang mereferensikan alias Kernel sebelum import; gagal sebelum koneksi database. Bukti `mysql-bootstrap-first-failure.txt` dipertahankan; bootstrap memakai nama Kernel lengkap. Percobaan berikutnya terhalang sandbox TCP lokal sebelum mutasi; eksekusi dengan eskalasi sandbox yang diotorisasi berhasil.

Belum dijalankan worker: build/suite penuh, browser/QA independen, ekstraksi atau inspeksi visual PDF, HP/printer fisik, migrasi database operator, push/deploy. PDF diuji status200, Content-Type, awalan%PDF dan data yang dipasok ke template HTML bersama; inspeksi visual/teks PDF menjadi bagian QA coordinator. Rollback sengaja ditolak bila diskon masih ada agar nilai finansial tidak hilang diam-diam.
