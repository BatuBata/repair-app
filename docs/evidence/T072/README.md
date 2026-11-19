# T072 — detail TTS satu halaman terbuka

D-42, main lokal base b5a85ed. User menolak hasil D-41: disclosure menyembunyikan konteks. Riwayat T070/T071 tetap dipertahankan; PASS teknis sebelumnya bukan penerimaan UX oleh user.

Satu view existing: barang/keluhan dan pelanggan di atas, progres dengan tanggal terlihat, isi pekerjaan/catatan/nota di kiri, penanganan/teknisi dan dokumen di kanan. Di HP urutan menjadi satu kolom. Seluruh informasi detail terbuka, menu aksi/dialog input tetap existing. Ganti teknisi langsung terlihat, catatan satu isian tetap. Simpan catatan dan simpan+bukaWA berdampingan desktop; primary sticky HP tetap. Tidak ada perubahan domain, status, schema, dependency atau aset.

PHP8.3 targeted Invoice/Tts/Tenancy-ServiceIsolation: 63 tes PASS, 556 assertions, 2941ms (/private/tmp/repair-t072-final-tests.log). Ini menguji alur penetapan/hasil cek/nota/pembayaran/pengambilan dan privasi. Tidak mengulang build/full suite domain karena hanya markup dan CSS satu view berubah.

Browser coordinator CUA fixture SQLite terpisah8193: checking desktop1440x1000 semua overview/progres/catatan/teknisi/dokumen terlihat bersamaan;375x812 stacked, document.scrollWidth375, details0. Bukan database operator. Nota selesai fixture2 juga diperiksa di375: item, diskon, total, garansi dan catatan terbuka; scrollWidth375. Screenshot checking desktop: detail-desktop.jpg. QA independen T073: independent-qa.md. Belum uji penerimaan pengguna, HP/printer nyata atau kirimWA. Tanpa push/deploy.

Port8006 existing login HTTP200 (koneksi diverifikasi di luar pembatasan network sandbox); server fixture8193 dihentikan setelah pengujian. Tidak memulai ulang layanan8006 atau mengubah env/database operator. Source final hanya beda whitespace kosong dan penghapusan judul nota luar yang menggandakan judul include dari targeted run; QA render memakai pin final.

T073 PASS teknis:11tes/583assert dan16kombinasi tanggal progres; pin final tetap cocok. Penerimaan visual user belum diasumsikan. git diff --check bersih.
