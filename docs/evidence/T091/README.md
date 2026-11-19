# T091 — WA dan alasan pembatalan

D-54, main lokal; tanpa push/deploy. Empat berkas aplikasi diperbaiki, tanpa migration/dependency/status baru:

- TrackingController membaca status dan alasan pembatalan. TTS batal diprioritaskan atas flag WA hasil pengecekan lama, sehingga tidak meminta persetujuan lagi.
- WhatsApp::cancellationMessage memuat nomor, alasan bila tersedia, nama toko dan link tracking. Tidak mengarang alasan bagi data lama/kosong.
- Detail admin menampilkan WA pembatalan saat Batal. Modal menjelaskan bahwa alasan terlihat oleh pelanggan.
- Tracking publik menampilkan alasan tersimpan hanya ketika Batal, dengan Blade escaping serta wrapping existing. Tidak memuat hasil pengecekan internal/identitas kontak pelanggan.

## Verifikasi aktual

- PHP8.3 `artisan test --compact tests/Feature/Tracking/TrackingTest.php tests/Feature/Invoice/InspectionTest.php`: 10 PASS,133 assertions,896ms.
- QA independen membuat dan menjalankan `tests/Feature/Tracking/CancelledTrackingTest.php`: 3 PASS,78 assertions,361ms (rincian di independent-qa.md).
- PHP8.3 `vendor/bin/pint --dirty --test` PASS; `git diff --check` PASS; `npm run build` PASS406ms. Warning font fallback optional dan IBM Plex runtime existing tidak berubah.
- Browser CUA, fixture disposable `/private/tmp/repair-t091.sqlite` dari salinan fixture tugas sebelumnya; tidak menyentuh DB operator. Admin membatalkan TTS Pengecekan lewat modal asli, mengisi alasan, lalu melihat Batal dan link WA pembatalan. Setelah logout, link publik menampilkan alasan yang sama. Viewport1280/375 tidak overflow; screenshot admin-cancelled.jpg, customer-desktop.jpg, customer-mobile.jpg dan browser-checks.json.
- Manifest5berkas aplikasi+test pada source-manifest.json, sha256:8f96583ca3ee8459024f02d255131e3b8adfc0dbdc94d313125cee8a174e3a71. QA independen memverifikasi pin dan screenshot.

Serverfixture/tabagent ditutup, viewport dipulihkan. Tidak mengirim pesan ke WhatsApp sungguhan; hanya memeriksa redirect yang dihasilkan melalui featuretest. Suite penuh/MySQL/produksi tidak diulang untuk perbaikan terbatas ini; jalur penerimaan/nota/hasilcek dan guard ditangani tes target. Tidak mengubah alur pengambilan TTS batal atau menambah biaya pengecekan.
