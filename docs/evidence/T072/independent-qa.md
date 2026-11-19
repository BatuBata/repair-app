# T073 — QA independen detail TTS terbuka

**PASS teknis, 19 November 2026.** Tester terpisah dari implementer; produk, ledger, Git dan database operator tidak diubah. Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, bersama project-delivery dan referensi QA. Runtime diwarisi; tidak mengklaim model_hint sebagai model efektif. Acuan: kontrak T072/T073, PRD D-42 dan DESIGN.md.

Base/HEAD `b5a85ed1ca81615ad32c8fab1380aedeff3c1f99`. Input/checked revision final `sha256:8f84cb36cfeab91fda504a62728b32a3f01546aedddfe4fdfcf1d0a91b82dbe8`; hash view `0d9e050980429d6b5ad747354ea80c7af3fcdc9980ee247ee1cc74dcc060d676` cocok dengan `source-manifest.json` sebelum dan sesudah tes final. Pin dispatch awal `sha256:1468d305a88981cb97ddf37c63a280ce5759e22ff2504f02db4df414881352c4` tidak diterima sebagai pin final: coordinator membersihkan whitespace dan heading nota ganda, lalu membekukan sumber dan memperbarui manifest. Pemeriksaan final memakai pin baru.

## Pemeriksaan aktual

PHP `/opt/homebrew/opt/php@8.3/bin/php`, PHPUnit konfigurasi proyek, SQLite `:memory:` dan fixture sintetis melalui RefreshDatabase. Tidak memakai MySQL atau database operator.

1. `vendor/bin/phpunit --configuration phpunit.xml /private/tmp/T073WorkflowTest.php --log-junit /private/tmp/t073-junit.xml` → **8 PASS / 540 assertions**, 862 ms. Log `/private/tmp/t073-qa.log`; SHA256 script `ba2730a6eed10f7b19ca4a3613151246c3dedd4254cdef696c29a523c566a55f`.
2. `vendor/bin/phpunit --configuration phpunit.xml tests/Feature/Invoice/InspectionTest.php --filter 'test_inspection_save_send_and_start_work_with_correct_dates|test_direct_work_needs_no_check_fields_and_reassignment_preserves_stage|test_admin_and_tenant_guards_cover_all_new_actions_and_whatsapp'` → **3 PASS / 43 assertions**, 369 ms. Log `/private/tmp/t073-existing-targeted.log`.
3. Analisis Python atas HTML hasil render → **16 kombinasi peran/tahap PASS**: jumlah tanggal progres sesuai data (Diterima 1, Pengecekan 2, Dikerjakan 2, selesai unpaid/paid/free 3, Diambil 4, Batal 1), format tanggal dan ketiadaan disclosure pada progres benar.
4. Review diff/caller `Detail`, routes `tts.show`/`tts.success`, partial aksi/teknisi/nota dan `ManageServiceOrder`; `git diff --check` PASS. Tidak ada perubahan app/routes/database/dependency dibanding base; produk hanya view detail.

Total tes: **11 PASS / 583 assertions**. Render disimpan di `/private/tmp/t073-render/`.

| Kasus | Expected dan actual |
|---|---|
| Admin: Diterima, Pengecekan, Dikerjakan, selesai unpaid/paid/free, Diambil, Batal | HTTP 200. Barang/model, keluhan multiline, pelanggan/alamat, progres, penanganan dan dokumen tampil di struktur halaman terbuka; tidak ada `details`. Overview/progres mendahului area kerja. Catatan tampil langsung sesuai tahap; nota tampil langsung bagi admin bila ada. |
| Sasaran kontrol | Semua ID unik, setiap `form`, `data-open` dan `aria-controls` menunjuk tepat ke sasaran yang ada. Tidak ada form bersarang. Satu textarea pengecekan, tombol Simpan catatan di luar form menunjuk `inspection-form`; primary desktop/mobile sesuai tahap; batal tanpa primary. Pengambilan unpaid/lunas/gratis selalu terbuka. |
| Sukses dan teknisi | Halaman sukses memiliki satu primary Lihat TTS, tanpa form/dialog detail. Delapan tahap teknisi tetap menampilkan informasi servis terbuka tanpa form/dialog/aksi admin, rincian/modal/laba atau nilai nota, maupun tautan PDF A5. Judul tahap dapat terlihat; kontrol admin tidak tersedia. |
| Catatan/WA/penetapan | Draft 8101 karakter ditolak tanpa mengubah catatan tersimpan/tahap; textarea invalid dan pesan terkait tampil. Koreksi multiline lalu mulai pengerjaan menyimpan hasil trim. Simpan sebelum membuka WA, pesan hasil cek dan konfirmasi, penetapan langsung/pemeriksaan dan ganti teknisi menjaga tanggal/tahap; semuanya PASS. Tracking publik tidak membocorkan catatan atau nama pelanggan. |
| Pembayaran/pengambilan | Transfer tanpa bank ditolak tanpa mutasi. Bayar dulu lalu ambil, bayar sekaligus ambil, dan gratis benar-benar mengubah tahap sesuai aturan; gratis tidak menciptakan paid_at. ID/error bidang bank collect/payment unik. |
| Otorisasi/data | Tenant lain 404; teknisi ditolak mengubah/membuka WA admin; admin yang diturunkan perannya 403 tanpa mutasi. Status yang dibatalkan sebelum draft dikirim menolak perubahan catatan. HTML pelanggan di-escape. |

Harness sempat FAIL karena menganggap teks judul “Tetapkan teknisi” tidak boleh terlihat bagi teknisi Diterima. Itu judul tahap existing, bukan kontrol; asumsi dibetulkan menjadi pemeriksaan form/dialog/aksi admin. Log awal tetap di `/private/tmp/t073-qa-initial.log`; produk tidak diubah oleh tester. Preflight pin awal juga berhenti sebelum tes karena coordinator sedang memperbarui sumber; hasil tersebut tidak dihitung sebagai cacat produk.

Struktur responsive ditinjau: grid isi+penanganan desktop, satu kolom di bawah1024, overview satu kolom di bawah640, cabang primary desktop dan bilah aksi HP existing. Bukti browser1440/375 tetap milik coordinator di README, bukan browser independen tester. Tidak ditemukan cacat produk pada pemeriksaan terarah ini; **penerimaan rasa desain oleh user belum diasumsikan**.

Tidak mengulang full suite/build/Pint/PHPStan; tidak menguji browser secara independen, HP/printer nyata, race MySQL, pengiriman WA eksternal atau produksi. Tidak push/deploy. Risiko tersisa: penerimaan visual owner dan perangkat nyata belum dibuktikan oleh QA ini.
