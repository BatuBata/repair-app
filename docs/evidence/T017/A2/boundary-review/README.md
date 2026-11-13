# T017 A2 — review batas coordinator

Sumber dokumen T017 `66359012ac0e4febec1f21c4154ba23085212333` cocok byte dengan runtime pada branch laporan; lihat `review.json`. Seluruh data sintetis, SQLite/storage/cache baru di `/private/tmp/repair-coordinator-pdf-boundary-092tntqg`. Tidak ada query/reset MySQL atau source aplikasi diubah.

- FAIL F03: nama toko 255 karakter tanpa spasi masih legal, tetapi tabel header A5 memperlebar kolom hingga nama, judul dan nomor dokumen keluar kertas. Visual `name-a5-first.png` dan 223 glyph di luar setiap halaman membuktikannya.
- PASS parsial: thermal nama panjang tetap utuh; A5 alamat dan syarat masing-masing 65.535 karakter menghasilkan 61 halaman lengkap, kedua penanda akhir hadir, tidak ada glyph di luar kertas.
- Belum terbukti: thermal alamat/syarat panjang melampaui timeout harness 60 detik; proses anak dihentikan tanpa PDF. Ini bukan pengukuran FPM/produksi.

PHP 8.3 batas memori default128M, cache font privat awal kosong (hanya subdirektori data). Marker PDF dijalankan sekali sebelum empat perintah authoring yang diharapkan. Probe privat tercatat dalam `probe.php.txt`; migrasi biasa hanya pada file SQLite baru, tanpa migrate:fresh. Python PATH tidak memiliki pdfplumber; analisis kemudian berhasil dengan runtime bundled Python, pypdf/pdfplumber. pdftoppm bundled merender halaman sampel dan PNG diperiksa.

A1/A2 telah terpakai. Tidak ada percobaan kode tambahan dibuka; perlu keputusan user untuk A3 sesuai project-delivery. QA independen T023, printer/Android dan produksi belum dilakukan.
