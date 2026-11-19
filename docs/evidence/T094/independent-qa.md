# T094 — QA independen pengambilan TTS Batal

**PASS** pada snapshot `sha256:566c56e9c776ed73f1786f5aaa4c0b4f6f958e8bf54eb6d26693c1b12fb93e7d`; keenam hash `source-manifest.json` diverifikasi cocok. Main lokal, tanpa push/deploy atau akses DB operator. Reviewer terpisah, Ponytail **full** memakai skill 5.3.3 yang telah dibaca dan panduan QA project-delivery.

Pemeriksaan aktual (PHP 8.3, SQLite in-memory `RefreshDatabase`):

- `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint tests/Feature/Invoice/CancelledPickupTest.php`: PASS.
- `/opt/homebrew/opt/php@8.3/bin/php artisan test --compact tests/Feature/Invoice/CancelledPickupTest.php`: **4 PASS,46 assertions,455ms**.
- Setelah menambah empat assertion tampilan teknisi sesudah pengambilan, hanya kasus berubah diulang: perintah di atas dengan `--filter=test_cancelled_pickup_uses_server_wita_today_and_preserves_status_and_finances`: **1 PASS,33 assertions,366ms**. Tiga kasus lainnya tidak berubah; cakupan final empat kasus berjumlah50 assertions. Tidak mengklaim ada eksekusi suite4kasus/50assert tersendiri.

Kasus yang terbukti:

1. TTS dibatalkan lewat action existing. Admin melihat Barang belum diambil dan tombol Tandai barang diambil; tracking awal juga belum diambil. Klik melalui `Detail.collect` memakai tanggal server **20 Nov WITA ketika UTC masih19Nov**, walaupun request mengirim tanggal1990 dan pembayaran transfer/bank tidak valid. Status tetap Batal, alasan/tanggal batal dan seluruh field selain picked_up_on/updated_at identik. Tidak membuat nota, pembayaran atau item.
2. Sesudahnya admin/teknisi readonly serta tracking anonim menampilkan sudah diambil/tanggal; tombol hilang. Tracking tidak membocorkan alamat/HP pelanggan. Pembatalan dan alasan tetap ditampilkan.
3. Dua request stale/ulang dipisahkan satu hari: tanggal pengambilan pertama dan seluruh baris termasuk updated_at tetap identik. Date/payment dari request tidak digunakan untuk TTS Batal.
4. Teknisi ditolak saat memanggil collect, toko lain tidak dapat membuka target foreign, akun yang dinonaktifkan setelah komponen dibuka ditolak. Semua baris target tetap belum diambil pada jalur ditolak.
5. TTS Batal yang diambil tidak masuk revenue/net/rows laporan, tidak menambah lane/status Diambil, tetap dihitung Batal dan tidak masuk backlog selesai.

Review source: cabang Batal berjalan setelah guard admin aktif, scope tenant, validasi ID dan row lock dalam transaction; hanya menyimpan tanggal bila masihnull, lalu return sebelum aturan pembayaran/transisiDiambil. Jalur normal tetap mengikuti transisi dan validasi existing. Tidak ditemukan cacat tersisa.

Bukti coordinator digunakan untuk regresi yang sama: 66 tes Invoice/Tracking/Reports/Dashboard PASS,716 assertions,3138ms; PHPStan0/Pint dirty PASS. Tidak mengulang suite tersebut.

`admin-before.jpg`, `customer-before.jpg`, `admin-after.jpg`, `customer-after.jpg`, `customer-mobile-after.jpg` dibuka dan diperiksa independen. Status Batal tetap jelas, informasi pengambilan/tanggal rapi, tombol tersedia sebelum dan hilang sesudah pengambilan; alasan tetap terbaca desktop/HP. `browser-checks.json` menunjukkan klik nyata pada375px, tombol0 sesudahnya, dan publictracking1280/375 tanpa overflow. Interaksi browser dikumpulkan coordinator, bukan reviewer.

Tidak menguji kontensi MySQL nyata, produksi/operatorDB, pengirimanWA atau perangkat fisik; idempotensi stale request diuji dan penggunaan rowlock existing direview. Build aset tidak diulang karena tidak ada perubahan sumber aset/CSS atau utility baru. Tanpa dependency, migrasi, status baru, push atau deployment.
