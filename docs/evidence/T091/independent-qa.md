# T091 — QA independen pembatalan TTS

**PASS** pada snapshot `sha256:8f96583ca3ee8459024f02d255131e3b8adfc0dbdc94d313125cee8a174e3a71`; seluruh lima hash `source-manifest.json` diverifikasi cocok. Main lokal, tanpa push/deploy. Reviewer terpisah, Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; prosedur QA project-delivery tetap berlaku.

Perintah aktual:

- `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint tests/Feature/Tracking/CancelledTrackingTest.php`: PASS setelah perapian urutan imports.
- `/opt/homebrew/opt/php@8.3/bin/php artisan test --compact tests/Feature/Tracking/CancelledTrackingTest.php`: **3 PASS, 78 assertions, 361 ms**, SQLite in-memory `RefreshDatabase`.

Kasus yang terbukti:

1. TTS benar-benar ditetapkan ke pengecekan, menyimpan catatan pengecekan/rencana lama, kemudian dibatalkan lewat `Detail.cancel`. Status dan alasan tersimpan. Form menjelaskan alasan akan dibagikan. Halaman detail menampilkan **WA pembatalan**, tanpa tombol WA tanda terima/hasil pengecekan yang salah.
2. Endpoint WA normal dan URL lama `?inspection=1` menghasilkan pembatalan beserta nomor TTS, alasan dan tracking link; tidak meminta persetujuan, tidak mengirim catatan pengecekan lama, tanda terima atau pesan selesai. Nama/alasan UTF-8, ampersand, newline dan markup tetap utuh melalui URL encoding, dengan tepat satu parameter `text`.
3. Tracking anonim menampilkan alasan tersimpan dengan escaping HTML (payload script tidak menjadi tag), tanpa alamat/nomor HP pelanggan atau catatan pengecekan privat. Header no-store/private dan noindex tetap.
4. TTS batal lama dengan alasan null, kosong atau whitespace tetap menampilkan pembatalan generik dan mengirim WA pembatalan lewat link inspection lama; tidak menampilkan label alasan kosong atau meminta persetujuan.
5. Alasan historis pada status nonbatal tidak tampil. Tamu diarahkan login, teknisi ditolak, admin toko sendiri diizinkan, admin toko lain tidak dapat mengakses WA/detail; tracking token lokal tidak memuat alasan toko lain.

Review source: pembatalan diperiksa sebelum flag inspection, field status/alasan disertakan pada query endpoint yang memerlukannya; query tenant dan middleware admin dipertahankan. Blade alasan menggunakan escaping dan aturan existing `overflow-wrap:anywhere`/`white-space:pre-line`. Tidak ditemukan cacat tersisa dalam scope.

Bukti visual coordinator `admin-cancelled.jpg`, `customer-desktop.jpg`, `customer-mobile.jpg` dibuka dan diperiksa independen. Alasan terbaca dan terbungkus rapi pada dua kolom desktop/satu kolom HP. Interaksi browser aktual dan geometri 1280/375 dilakukan coordinator, bukan reviewer.

Tidak menguji pengiriman pesan melalui aplikasi WhatsApp nyata, DB operator/MySQL, perangkat fisik atau produksi. Regresi receipt/inspection/invoice menggunakan pemeriksaan coordinator Tracking+Inspection (10 PASS, 133 assertions); tidak mengulang suite yang sama pada source identik.
