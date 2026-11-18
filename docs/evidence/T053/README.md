# T053–T054 — Data pelanggan, 18 November 2026

Base: `14abe389330dcbb73748176123b88cefa95ad677` + perubahan lokal fitur pelanggan.
Scope: buku alamat tenant (nama, HP, alamat opsional), tambah/ubah/cari admin, pilihan pelanggan pada TTS baru. Tidak ada backfill, FK ke TTS, hapus pelanggan, atau perubahan histori. Snapshot identitas TTS existing tetap dipakai. Migrasi hanya membuat tabel `customers` dengan unique tenant/phone.

Developer: Ponytail full. PHPUnit penuh: 234 PASS, 1 MySQL test existing SKIP, 2504 assertion. Targeted Customers + TTS: 22 PASS / 200 assertion. PHPStan --debug: 0 error; Pint --dirty dan npm run build PASS. Build memberi warning fontaine opsional/font path runtime existing.

QA independen `/root/customer_qa`: Ponytail full, tidak mengubah source. Customers + TTS + Tenancy Migration 25 PASS / 223 assertion. Harness `/private/tmp/CustomerIndependentQaTest.php`: 3 PASS / 8 assertion, membuktikan rollback TTS/counter jika Customer gagal tersimpan, foreign customer ID ditolak ulang saat save, dan admin yang didemosi tidak bisa save form terbuka. Percobaan harness pertama salah mengasumsikan mutasi instance masuk snapshot Livewire; direct save menguji bypass hook dengan benar. Tidak ada bug backend penghambat.

Browser coordinator memakai SQLite sintetis terisolasi `/private/tmp/repair-customers-XXXXXX.sqlite`, bukan DB operator/produksi: login admin sintetis, search-select pelanggan dengan keyboard, isi otomatis termasuk alamat, pilih kembali Pelanggan baru mengaktifkan isian kosong, simpan TTS berhasil menyalin identitas, tambah pelanggan lewat UI berhasil dan muncul di daftar. TTS tanpa overflow horizontal pada 390/768/1440; daftar pelanggan tanpa overflow pada 390/1440. Screenshot desktop/HP diperiksa; tidak mengklaim uji HP fisik. Server QA port8302 dihentikan setelah selesai.

Belum diuji: migrasi MariaDB nyata, printer/HP fisik. Tidak menjalankan migrasi pada DB existing, push, atau deployment. Migrasi aditif perlu dijalankan oleh workflow deployment saat nanti diizinkan.
