# T093 — QA independen rilis

## Review preflight

PASS untuk perubahan tunggal `TrackingController`: `$order->status` menjadi `$order->getAttribute('status')` pada pembandingan ketat terhadap `ServiceOrderStatus::Batal`. Model meng-cast status ke enum, accessor yang sama telah digunakan action `ManageServiceOrder`. Query, guard tenant/admin, pesan dan urutan pembatalan sebelum flag inspection tidak berubah. Tidak ada perubahan perilaku baru yang memerlukan suite duplikat.

Bukti coordinator setelah perbaikan: PHPStan 0 error; CancelledTrackingTest 3 PASS/78 assertions/387 ms; Pint PASS. Pemeriksaan tersebut digunakan kembali, bukan diklaim sebagai eksekusi reviewer. Source lainnya merupakan kandidat yang sudah diperiksa pada task sebelumnya.

Ponytail **full** aktif, skill 5.3.3 dan panduan QA project-delivery telah dibaca. Peran reviewer hanya membaca source/target dan menulis bukti ini; tanpa push, perubahan source, kredensial, data bisnis atau migrasi oleh reviewer.

## Pascadeploy — PASS

Target nyata: `muzib@202.10.40.165:/var/www/demo-repair-app/current`, HTTPS `demo-repair-app.scannara.asia`, source `3b5426158073ce2d90bcad256f45da374026d002`. Pemeriksaan dimulai setelah coordinator melaporkan CI dan deployment [run 44555954136](https://github.com/BatuBata/repair-app/actions/runs/44555954136) sukses. Hasil aktual disimpan dalam `postdeploy-independent.json`.

Pemeriksaan reviewer yang benar-benar dijalankan:

- SSH `BatchMode` ke target, skrip PHP 8.3 hanya-baca memverifikasi `.release-revision` dan `last-success.json`: keduanya tepat SHA target, status pass, receipt `2026-11-19T15:35:12Z`; marker maintenance aplikasi/deployment tidak ada.
- Bootstrap aplikasi dan `SELECT 1` berhasil. Query tabel migrasi membuktikan keempat migrasi baru Ran. Sebelas metadata kolom diperiksa lewat `information_schema`: kedua diskon unsigned bigint default0, enum status memuat pengecekan, empat kolom pengecekan nullable, kategori pengeluaran varchar50 dan master tenant/category/active sesuai. Tidak menjalankan migrate/seed atau transaksi bisnis.
- Hash berkas server dibandingkan dengan bytes `git show` commit target: **211 berkas source yang masuk paket identik**, mencakup app, view, CSS/JS, migrasi, konfigurasi, routes, Composer lock dan ikon. Probe awal juga menyertakan package-lock.json: berkas itu memang tidak dipaketkan menurut `deploy/ci-package.sh`, sehingga diklasifikasikan pengecualian packaging yang diharapkan, bukan source runtime berbeda. Vendor/aset build tidak di-hash ulang.
- Source view server memuat logo bersama, subtitle netral, alasan pembatalan dan tidak memuat teks tetap Servis Elektronik. Satu compiled tracking view memuat perubahan. Controller server mendahulukan pembatalan sebelum flag inspection.
- `/usr/bin/curl` macOS dengan verifikasi TLS aktif mengembalikan `/up`, `/login`, logo PNG dan `/platform/login` **200**; `.env`/`.git/config` **403**, vendor/autoload **404**. Seluruh `ssl_verify_result=0`; isi berkas privat tidak diambil/ditampilkan.
- Hanya jumlah baris diamati: 3 toko,9 pengguna,28 TTS,6 penjualan,0 pengeluaran,0 kategori pengeluaran tambahan. Ini observasi sesaat pada aplikasi aktif, bukan klaim checksum atau uji perubahan data pelanggan.

Riwayat hambatan pemeriksaan: urllib Python lokal mula-mula gagal menemukan issuer CA; fallback curl memakai trust store sistem dan memverifikasi TLS dengan sukses, tanpa `-k`/mematikan verifikasi. Daftar Git awal mengutip nama Unicode; diperbaiki memakai pemisah NUL sebelum parity remote. Tidak ada perubahan produk atau data untuk menangani kedua hal tersebut.

CI keseluruhan (bukti coordinator) 292 PASS/3351 assertions/34,48s dan20 pemeriksaan keselamatan deployment; reviewer tidak mengulang suite pada target produksi.

Tidak login akun pelanggan/operator, membaca environment/kredensial/PII/token, mengirim WA, membuat transaksi, menguji printer/HP fisik atau menjalankan backup/restore. Backup tetap ditunda sesuai D-22; penerimaan fisik T025 tidak dibuka oleh rilis ini. Tidak ada cacat pascadeploy yang ditemukan dalam scope read-only.
