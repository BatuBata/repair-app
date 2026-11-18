# T063 — QA independen T062

Verdict backend/source: **PASS**, 18 November 2026. Tidak ditemukan cacat produk pada pemeriksaan berikut. Pemeriksaan browser dan visual PDF aktual diserahkan kepada coordinator dan tidak diklaim dilakukan oleh tester ini.

Target dan input: T062, base `e3cf94ea9aa195ff8397bee54ff5e42401e74536`, snapshot `sha256:cf11e12610ba88615f7d41013f64a6d1f14399c1875f6ab7e8cf43ae172dd460`, `docs/evidence/T062/source-manifest.json`. Verifikasi Python hashlib terhadap seluruh 20 berkas: cocok. PRD D-37 dan acceptance criteria T063 dibaca sebelum pemeriksaan. Runtime model diwariskan sesi; tidak ada receipt penggantian model dan tidak diklaim berbeda. Skill Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dan `references/qa-and-release.md` dibaca sesuai peran tester.

## Perintah dan hasil aktual

Semua tes memakai `/opt/homebrew/opt/php@8.3/bin/php`, konfigurasi `phpunit.xml`, SQLite `:memory:`. Tidak ada akses/tulis database operator, push atau deploy; tidak ada perubahan source produk/ledger oleh tester.

1. `php vendor/bin/phpunit tests/Feature/Invoice/ServiceRevisionsTest.php`: **5 PASS, 60 assertions**. Diskon invalid atomik, gratis dan pickup, form/koreksi, dashboard/laporan/nota/tracking/WA, pembayaran dibersihkan saat menjadi gratis, rekap gabungan dan WA unrepairable.
2. `php vendor/bin/phpunit /private/tmp/T063IndependentTest.php`: **4 PASS, 32 assertions**, log `/private/tmp/t063-independent-final.log`. Tes baru independen, hanya di `/private/tmp`.
3. `php vendor/bin/phpunit tests/Feature/Tenancy/ServiceIsolationTest.php tests/Feature/Tenancy/CommerceIsolationTest.php tests/Feature/Reports/ReportsTest.php tests/Feature/Tracking/TrackingTest.php`: **22 PASS, 379 assertions**, log `/private/tmp/t063-regression.log`.

Total final **31 tes PASS, 471 assertions**. Suite penuh tidak diulang karena suite developer pada pin sama sudah tersedia; subset independen memusatkan risiko uang, tanggal, tenant dan kompatibilitas.

## Kasus independen dan expected/actual

| Kasus | Expected dan hasil aktual |
|---|---|
| Migrasi forward atas transaksi sintetis lama | Kolom discount baru = 0; semua atribut lama dibandingkan persis sebelum/sesudah dan seluruh item tetap sama. PASS. Tes menghapus kolom hanya pada SQLite memory milik tes, lalu menjalankan migration up. |
| Nilai besar dan overflow | Qty 2147483647 × harga 4294967295, diskon subtotal−1 menghasilkan harga akhir integer 1; modal utuh, laba 1−modal. Null/kosong/false, notasi eksponen, pecahan dan nilai di atas PHP_INT_MAX ditolak; perkalian overflow ditolak. PASS. |
| Periode inklusif dan beda tanggal bayar | Nota 1 Nov dibayar 31 Des tetap masuk November; nota/sale 30 Nov masuk, nota 1 Des keluar. PASS. |
| Rekap gabungan dan legacy | Servis transfer75 bank arsip + sale QRIS20 + servis tanpa metode100: cash0/qris20/transfer75/unrecorded100/total tercatat95; omzet195, modal160. Pengeluaran999 tidak masuk pembayaran. Bank lama tetap tampil, tidak diubah ke bank aktif. PASS. |
| Tenant | Nota toko B dengan diskon90 tidak masuk laporan A; koreksi nota B oleh A ditolak dan discount tetap90; WA internal B memberi404. Tes regresi juga memeriksa jalur dokumen/aksi/daftar tenant. PASS. |
| Koreksi tanggal asli | Nota 1 Jan dikoreksi menjadi diskon penuh tanpa mengganti tanggal nota; payment_method/paid_at dikosongkan. PASS. |
| Gratis dan privasi | Total0 dapat diambil tanpa metode pembayaran valid, status menjadi Diambil; tracking menampilkan Rp0 (Gratis), tanpa nama/alamat/telepon customer atau modal. PASS. |
| WA unrepairable setelah pickup | Pesan mengatakan barang tidak dapat diperbaiki, tidak mengandung “sudah selesai”, status tetap Diambil. PASS. |

Percobaan awal tes independen menghasilkan 3 PASS/1 FAIL karena asumsi tester bahwa boolean PHP `true` harus ditolak oleh aturan `integer` Laravel; Laravel menerimanya sebagai nominal1. Ini bukan jalur input UI (property diskon bertipe string) atau cacat scope D-37. Asumsi tes tersebut dihapus; log awal `/private/tmp/t063-independent.log` dipertahankan. Produk tidak diubah untuk meluluskan tes.

## Review source dan batas

Review semua caller kalkulasi invoice, jalur report/dashboard, dokumen privat/publik, tracking dan WhatsApp: diskon dikurangkan dari omzet/laba, bukan modal. Koreksi dilakukan dalam transaksi dengan revision check sebelum penggantian item. Query summary mengikuti tanggal nota/jual, tenant scope dan status servis selesai/diambil; biaya operasional dikecualikan. Migrasi menambah satu kolom default0 tanpa backfill payment/date maupun reset data. UI diskon memakai komponen money existing dan pesan validasi; tidak menambah dependency/abstraksi. Source hanya menghasilkan tautan WhatsApp dan tidak mengirim pesan otomatis atau mengganti status internal menjadi Batal.

Tidak diuji oleh tester ini: browser interaktif/visual PDF (coordinator), MySQL/MariaDB nyata, printer fisik, pengiriman WhatsApp nyata, produksi. Validasi schema/data migration pada SQLite tidak menggantikan uji migration engine MySQL saat rilis diotorisasi. Tidak ada push/deploy; tidak membuka kembali gate perangkat/penerimaan produksi.

Update coordinator (diterima setelah tes backend): browser aktual lebar390/1280 tidak melampaui viewport, rekap bank utuh, edit diskon100000→Gratis/simpan/ambil tanpa pembayaran dan laporan sesudahnya PASS. PDF A5/thermal aktual dirender Poppler dan ditinjau coordinator PASS. Ini bukti terpisah coordinator, bukan observasi langsung tester ini; tidak memicu pengulangan suite pada source identik.
