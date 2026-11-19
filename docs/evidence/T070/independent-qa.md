# T071 — QA independen detail TTS

**PASS, 19 November 2026.** Tester terpisah dari implementer; source dan ledger tidak diubah. Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, bersama project-delivery dan `references/qa-and-release.md`. Runtime diwarisi; tidak ada klaim pergantian model.

Input T070: base `85afa1ca9203c84cd3fbe846f0635554e63843c9`, checked revision `sha256:7ed96549e127344168f7e206b30354b5487a3f95572cd77771d1cef8dc7be5e3`. Keenam hash file di `source-manifest.json` cocok sebelum dan sesudah pemeriksaan. Kontrak PRD D-41 dan T070/T071 dibaca langsung.

## Pemeriksaan aktual

PHP `/opt/homebrew/opt/php@8.3/bin/php`, PHPUnit konfigurasi proyek, SQLite `:memory:` dengan fixture sintetis dan RefreshDatabase. Tidak mengakses database operator.

1. `vendor/bin/phpunit --configuration phpunit.xml /private/tmp/T071WorkflowTest.php --log-junit /private/tmp/t071-junit.xml` → **7 PASS, 279 assertions**, 783 ms. Log `/private/tmp/t071-qa.log`; script SHA256 `3154cd7d761b6247af8a0de16cf871f42823445e5d59b3233c584019bd96f8a8`.
2. `vendor/bin/phpunit --configuration phpunit.xml tests/Feature/Invoice/InspectionTest.php --filter 'test_inspection_save_send_and_start_work_with_correct_dates|test_direct_work_needs_no_check_fields_and_reassignment_preserves_stage|test_admin_and_tenant_guards_cover_all_new_actions_and_whatsapp'` → **3 PASS, 43 assertions**, 683 ms. Log `/private/tmp/t071-existing-targeted.log`.

Total **10 tes / 322 assertions PASS**. Tes sementara dan hasil render ada di `/private/tmp/t071-render/`; source tes proyek tetap utuh.

| Kasus | Expected dan actual |
|---|---|
| Diterima, pengecekan, dikerjakan, selesai belum bayar/lunas/gratis, diambil, batal | Render 200; summary sebelum pekerjaan sebelum dukungan; aksi tahap benar. Masing-masing satu primary pada cabang desktop/mobile (batal nol), form target dan dialog target valid, semua ID/aria-controls unik. |
| Dukungan | Detail, riwayat, ganti teknisi, cetak native details tertutup pada halaman detail. Success menampilkan aksi Lihat TTS dan detail terbuka sesuai desain. WA menyebut tanda terima/hasil pengecekan/nota; mulai kerja sekunder memakai konteks persetujuan eksternal. |
| Pembayaran/pengambilan | Belum bayar menyediakan dialog Catat pembayaran dan Bayar & Diambil. Lunas/gratis menampilkan tanggal dan primary Tandai Diambil. Bayar dulu kemudian ambil, bayar sekaligus ambil, dan gratis benar-benar mengubah tahap sesuai aturan; gratis tidak membuat paid_at. Transfer tanpa bank ditolak dan data tidak berubah. Prefix bank/error collect dan payment unik saat keduanya dirender. |
| Catatan | 8101 karakter ditolak: draft dan catatan tersimpan tetap, tahap pengecekan, textarea aria-invalid dan pesan error terhubung. Koreksi draft lalu mulai berhasil redirect, menyimpan teks multiline dengan trim, tahap dikerjakan. Alur simpan/buka WA serta jalur langsung dikerjakan/reassignment juga PASS. |
| Stale/otorisasi/privasi | Pembatalan dari luar sebelum draft dikirim menyebabkan status error tanpa menimpa catatan. Admin yang diturunkan perannya ditolak 403 tanpa mutasi. Tenant lain 404. Teknisi tidak mendapat kontrol admin, rincian/modal/laba atau nilai nota di render; public tracking tetap tidak membocorkan catatan/nama. HTML pelanggan di-escape. |

Responsive diperiksa lewat struktur render kedua cabang dan aturan CSS existing: actionbar tersembunyi desktop, desktop-only tersembunyi di bawah1024. Bukti browser checking1280/375, satu primary/docwidth375, disclosure dan mulai dari draft tetap merujuk pemeriksaan coordinator di `README.md`; tidak diklaim sebagai browser independen. Penyesuaian markup tanggal setelah browser dicakup render unpaid/paid/free final ini.

Percobaan awal harness menghasilkan empat FAIL asumsi QA (nama ID date-picker, form milik shell, error bag Livewire lama setelah redirect). Harness dibetulkan tanpa mengubah produk; redirect serta data akhir menjadi bukti keberhasilan retry. Log awal dipertahankan `/private/tmp/t071-qa-initial.log`. Tidak ditemukan cacat produk dari kasus terarah tersebut.

Tidak mengulang full suite/build/Pint/PHPStan pada source yang sama; bukti pengembang tetap di README. Tidak menguji race MySQL nyata, HP/printer nyata, pengiriman WA eksternal, atau produksi; tidak push/deploy/migrasi. StartWork memakai dua action transaksi existing secara berurutan, sehingga tidak mengklaim atomicity dua action terhadap kegagalan server di antaranya; ini tidak menghapus catatan valid yang sudah berhasil disimpan.
