# T087 — QA independen diskon penjualan

Verdict: **PASS**. Tidak ada cacat yang teramati terhadap kontrak T087/PRD D-50.

Checked revision: `sha256:413a891252ba49709041388c769f03edd98b96f065a7f7f946597746829a6266`, base `a971de1250989e3c5bedbf374428693a1694529b`. Seluruh **12 hash file** pada `source-manifest.json` dihitung ulang tester dan cocok, termasuk view serta tes final. `git diff --check` PASS, exit 0.

Ponytail **full** dan project-delivery/aturan QA telah dibaca dalam sesi ini, termasuk sumber Ponytail `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Acuan visual tetap DESIGN.md. Skill PDF dibaca untuk pemeriksaan isi PDF. Tester tidak mengubah source, ledger, Git atau database operator.

## Review source dan kontrak

- Menelusuri `CreateSale`, model Sale/SaleItem, form/list/detail, seluruh caller penjualan yang menampilkan angka, Dashboard, Report, layanan/template dokumen bersama, DeleteSale, komponen uang dan parser uang existing, migrasi, serta delapan kasus tes diskon. Riwayat pelanggan tidak menampilkan angka transaksi sehingga tidak membutuhkan perubahan hitungan.
- Diskon adalah satu nominal transaksi, default 0, integer nonnegatif sampai PHP_INT_MAX. Batas subtotal dihitung dari item terkunci pada server, bukan preview klien. Penolakan diskon berlebih berada dalam transaksi database: perubahan stok sementara ikut rollback, sementara customer/nomor/sale/item belum dibuat. Pemeriksaan overflow baris dan akumulasi existing tetap sebelum penyimpanan. Diskon penuh boleh total0; pembayaran tetap memakai alur existing.
- Harga dan modal snapshot item tidak dikurangi diskon; stok tetap berkurang sesuai jumlah barang. Hapus memakai jalur existing yang mengembalikan stok dan menghapus transaksi/item, tanpa penyesuaian jumlah stok berdasarkan diskon.
- List/detail menghitung subtotal dikurangi diskon; laba detail memakai total akhir dikurangi modal. Dokumen memasok `discount` ke template bersama existing yang mencetak subtotal/diskon/total akhir. Report menambah satu baris Diskon bermodal0 per transaksi sebelum agregasi uang/metode/bank/hari/laba. Dashboard mengurangi diskon setelah loop barang, sekali per sale, pada revenue/profit/sales/sales_profit. Scope tanggal dan tenant tetap ada pada query penjualan tersebut.
- Auth admin aktif, pemeriksaan tenant produk/customer, scope model dan transaksi existing tetap dipertahankan. Penambahan diskon tidak membuka route edit atau alur pembayaran baru.
- Migrasi hanya menambah `sales.discount` unsigned BIGINT default0. Guard rollback memeriksa seluruh penjualan sebelum drop kolom dan menolak bila ada diskon positif. `A1/mysql-proof.php` serta hasilnya dibaca: migrasi asli dijalankan pada satu tabel fixture prefiks milik task, dua baris lama dari dua tenant dipertahankan, integer maksimum persis, unsafe rollback ditolak, safe rollback/up lolos, 20 tabel baseline existing identik dan fixture dibersihkan. Tester tidak mengulang MySQL dan tidak memigrasikan database operator.
- Preview memisahkan subtotal dan total akhir, memperbarui diskon lewat binding uang existing, memakai null/“Periksa isian” untuk diskon invalid, serta error inline dengan `aria-invalid` dan fokus existing. Harga/jumlah tetap memakai binding lama.

## Tes tambahan yang benar-benar dijalankan tester

Satu kasus gabungan baru pada SQLite `:memory:` melalui PHPUnit/RefreshDatabase melengkapi kasus worker: penjualan QRIS subtotal80.000 diskon15.000 serta penjualan cash gratis diskon80.000 pada hari sama, lalu master barang diganti nama/harga/modalnya. Report tetap sales65.000/cost100.000/gross−35.000, pembayaran QRIS65.000 dan cash0; dashboard sales/profit sama. Setelah penjualan sebagian dihapus, penjualan gratis tersisa sales0/cost50.000/gross−50.000 dan stok kembali menjadi18. Snapshot item tidak mengikuti perubahan master.

Perintah: `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit -c phpunit.xml /private/tmp/T087IndependentTest.php --colors=never`.

Receipt aktual: `{"tool":"phpunit","result":"passed","tests":1,"passed":1,"assertions":19,"duration_ms":574}`; exit 0. Tes ini juga meminta kedua endpoint PDF nyata, status200 dan Content-Type PDF, lalu menyimpan respons fixture ke berkas sementara.

### Isi PDF nyata

Python `pypdf.PdfReader` membaca `/private/tmp/t087-independent-a5.pdf` dan `/private/tmp/t087-independent-thermal.pdf`; masing-masing satu halaman. Assertion ekstraksi kedua PDF memastikan nama snapshot `Independent synthetic cable`, hargaunit `Rp40.000`, subtotal `Rp80.000`, `Diskon −Rp15.000`, dan total `Rp65.000` tersedia; nama master baru tidak terbawa. Tidak memasang dependency baru.

- SHA-256 A5: `0ac3ba9cafe06920daf089cbf0436266604048359743f07255035f97056e2a5e`.
- SHA-256 thermal: `8847ec48d5ace48f3989b325a8408a4d19609507b50f728e0a42a8c988a74fe5`.

Ini membuktikan isi PDF hasil render aplikasi; tidak diklaim sebagai inspeksi visual layout PDF.

## Bukti browser dan integrasi yang direview

Tester melihat keempat screenshot coordinator: `discount-desktop.jpg`, `discount-error-mobile.jpg`, `discount-detail-mobile.jpg`, `discount-receipt.jpg`. Angka subtotal650.000/diskon50.000/net600.000 konsisten, hargaunit650.000/modal450.000 tetap, laba150.000 benar. Error diskon700.000 terlihat di bawah input/fokus pada375px; total invalid tidak menjadi negatif. Susunan HP tidak terlihat overflow. Full-page detail HP memuat overlay navigasi fixed pada posisi capture, bukan seluruh konten yang tampil serentak dalam satu viewport.

Interaksi CUA berasal dari **coordinator**, bukan sesi tester: `browser.md` mencatat diskon sebagian/penuh, perubahan jumlah, overdiscount yang ditolak dan fokus error, simpan QRIS, stock20→19, serta list/detail/strukHTML bernilai akhir. Fixture SQLite terpisah, tanpa transaksi operator.

Hasil worker 53 tes/708 assertion serta bukti MySQL direview melalui `A1/HANDOFF.md`. Tester juga membaca receipt coordinator `/private/tmp/repair-t087-full-suite.log`: **283 kasus, 282 PASS, 1 SKIP concurrency MySQL terjaga, 3100 assertion, 15422ms**; dan `/private/tmp/repair-t087-build.log`: **PASS446ms**. Tidak mengulang suite/build pada source sama. Riwayat kegagalan tes awal tetap berada di bukti A1.

Belum diperiksa: layout PDF secara visual, HP/printer fisik, pembaca layar, atau migrasi database operator/produksi. Risiko operasional: rollback sengaja ditolak selama diskon tersimpan agar nilai finansial tidak hilang. Tidak push/deploy atau melakukan mutasi database operator.
