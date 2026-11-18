# T060–T061 — Bank per toko

Base `b3c46881b4f12f1f414ba548cef1ce5495542287` + perubahan lokal18Nov2026. Request: default readonly/hanya aktif-nonaktif per tenant, custom add/edit/delete milik tenant sendiri.

Satu tabel banks tenant/name/is_active dengan unique tenant/name. Sembilan default Payment::BANKS tetap virtual aktif tanpa row; toggle membuat override status untuk tenant itu saja. Tidak seed/backfill tenant/transaksi. Custom name tidak boleh sama dengan default (termasuk beda kapital) atau sentinel semua. Settings admin-only memakai locked editing/ID dan scoped lookup; default edit/delete ditolak server, bukan hanya UI. Model guard default memakai original name dan current+original tenant saat delete agar dirty model tidak melewati proteksi.

Payment::bankOptions() menggabungkan default/custom aktif. Payment::validate memakai daftar aktif tenant saat save sehingga request lama bank yang dinonaktifkan ditolak untuk penjualan/servis. Laporan memakai opsi all: default termasuk nonaktif, custom, dan distinct snapshot payment_bank dari transaksi tenant; Rule::in array mendukung nama mengandung koma. Snapshot transaksi lama tidak diubah setelah rename/delete/toggle; tidak ada FK baru ke pembayaran.

Developer: Banks5tes61assert PASS; Invoice/Sales/Reports50tes430assert PASS; full248PASS1existingMySQLSKIP2637assert. PHPStan awal1styleerror nullsafe+coalesce; firstWhere()->is_active??true benar dan final0error. Pint/build PASS. Guard dirty-default hasil review independen diperkuat, Banks6tes63assert PASS, Pint dan PHPStan final0error. Fullsuite/build tidak diulang karena hanya guard delete+test yang berubah.

QA independen `/root/tenant_banks_qa`: Ponytail full dan project-delivery QA dibaca. Banks/Invoice/Sales/Reports55tes491assert PASS. Edge `/private/tmp/TenantBanksIndependentQaTest.php`2tes21assert PASS: semua default nonaktif menolak transfer tetapi Tunai/QRIS valid, reaktifkan bank berhasil, BCA tenantB tetap aktif, customB ditolak diA, invalid toggle tidak menambah row. Defense-only dirty-name model dicatat sebelum perbaikan; retest targeted final dicatat ledger.

Browser coordinator memakai SQLite sintetis `/private/tmp/repair-customers-XXXXXX.sqlite`, server8302: migration PASS; menu BCA hanya Nonaktifkan, toggle menampilkan Nonaktif; custom BankJagoQA tambah dan rename lewat menu Edit berhasil, custom menu punya Edit/Nonaktifkan/Hapus; bank settings390px scrollWidth=innerWidth390/screenshot dibaca. Pembayaran transfer pada penjualan: pencarianBCA hasil0, custom rename BankJagoQABaru tampil sebagai opsi. Server dihentikan. Tidak uji klik hapus end-to-end browser; action delete dan snapshot diuji backend.

Belum: MariaDB nyata, perangkat fisik, push/deploy; DB operator/produksi tidak dimigrasikan. Tidak menambah nomor rekening, rekening pelanggan, atau fitur pembayaran lain.
