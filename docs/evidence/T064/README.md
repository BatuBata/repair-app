# T064 — pengecekan opsional

User19Nov2026, PRD D-38: main lokal, jangan push/deploy. A1 implementasi pada base1fc6fb1, source terpin source-manifest.json. Ponytail full/project-delivery; komponen dan desain existing, tanpa dependency/modul approval/estimasi harga baru.

- Penetapan awal oleh admin memilih Pengecekan dulu atau Langsung dikerjakan. Reassign hanya mengganti teknisi dan tidak memundurkan tahap. Caller assign lama tanpa argumen tahap tetap langsung bekerja.
- Dua teks hasil pengecekan/rencana tindakan, wajib saat disimpan (maks4000 masing-masing). Admin dapat Simpan atau Simpan & kirim WA; tombol kedua menyimpan teks terbaru sebelum membuka tautan. Teknisi dapat membaca, bukan mengubah tahap/catatan. Persetujuan pelanggan di luar sistem; mulai kerja tidak diwajibkan mengisi persetujuan/hasil cek digital.
- Pengecekan → Dikerjakan melalui Mulai pengerjaan; pembatalan existing tersedia. Nota tetap hanya dari Dikerjakan. Dua timestamp nullable membedakan waktu cek/kerja dari penetapan teknisi; legacy fallback assigned_at tanpa backfill.
- Daftar/dashboard/tracking/timeline/status pill mengenali tahap baru. Tracking publik tidak memilih/menampilkan catatan pemeriksaan privat. WA hasil cek dipisah dari tanda terima/nota existing, middleware admin/tenant tetap.
- Migrasi satu file memperluas enum status dan menambah dua teks/timestamp nullable. Down ditolak sebelum mengubah schema bila masih ada status Pengecekan. Tidak mengubah migrasi lama atau transaksi lama.

## Verifikasi developer aktual

PHP8.3 `/opt/homebrew/opt/php@8.3/bin/php`:

- Awal52tes: dua expected graph lama diperbarui, dua error fixture/validation diperbaiki dalam A1 (errorbag dibersihkan sebelum simpan/kirim; test forbidden memakai snapshot terpisah). Log awal /private/tmp/repair-t064-{initial-tests,targeted}.log dipertahankan.
- InspectionTest6tes70assert PASS: dua jalur, simpan+WA teks terbaru, urutan/timestamp/reassign, larangan nota, validasi/XSS/batal/stale, role/tenant, nullable legacy dan guard rollback.
- Fullsuite:263PASS1guardedMySQLconcurrencySKIP2805assert, /private/tmp/repair-t064-suite.log. Pint PASS, PHPStanlevel7 errors0, npm build PASS419ms. Perubahan setelah suite hanya tinggi textarea/jarak tombol pada view yang sudah diperiksa browser; backend tetap identik.
- MySQL8.0.31 nyata, database disposable unik dibuat/dihapus khusus tes: migrasi ENUM baru, atribut/items legacy byte-equivalent, kolom nullable, nilai Pengecekan diterima, rollback saat aktif ditolak tanpa perubahan, safe down/up mempertahankan data. /private/tmp/repair-t064-mysql.php dan .log. Docker tidak berjalan sehingga MariaDB10.11 belum dites di turn ini; tidak mengubah database operator untuk tes ini.
- Browser CUA SQLite fixture /private/tmp/repair-t064.sqlite, server8193:390×844 penetapan default Pengecekan, simpan hasil/rencana, Mulai pengerjaan dan tombol nota muncul PASS. Form dua textarea100px dan tombol berjeda utuh; timeline5tahap menjadi3+2diHP. Dokumen width390 tepatviewport.
- Desktop1280×900: pilih Langsung dikerjakan saat menetapkan teknisi, tidak ada isian hasil cek wajib, tombol nota langsung tersedia, timeline4tahap. Dashboard5kolom utuh, width1280 tepatviewport. Viewport dikembalikan dan tab fixture ditutup. Browser tidak membuka WhatsApp eksternal/mengirim pesan; isi URL diuji otomatis.

QA independen T065 PASS:50tes580assert pada pin17/17 cocok; independent-qa.md membedakan tes tester dari bukti browser/MySQL developer yang direview.

Migrasi lokal setelah QA: hanya `2026_11_19_000000_add_optional_inspection` diterapkan ke MySQL localhost `repair_app`,114.77ms DONE. Atribut/items existing dibandingkan sebelum/sesudah identik; database ini saat pemeriksaan berisi0service_orders, jadi bukti preservasi data berisi tetap dari fixture MySQL/SQLite di atas. Log /private/tmp/repair-t064-local-migrate.log. Server8006 dihidupkan kembali memakai konfigurasi proyek utama secara privat, tanpa mengubah .env.

Belum push/deploy. Belum uji MariaDB10.11, printer/HP fisik atau pengiriman WA nyata. Rollback produksi belum dijalankan; down menghapus kolom catatan/timestamp baru setelah guard status terpenuhi.
