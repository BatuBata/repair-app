# T062 — revisi laporan, diskon servis, dan WhatsApp

Permintaan user 18 November 2026: tiga revisi transkrip, tanggal transaksi seperti laporan existing, tidak overengineering, jangan push/deploy. PRD D-37. Implementasi A1 pada main lokal; sumber terpin pada source-manifest.json, base e3cf94e.

Perubahan minimal: satu kolom service_orders.discount default 0, tanpa backfill; rekap metode/bank memakai tanggal nota servis dan tanggal jual; diskon nominal 0..subtotal mengurangi omzet/laba tanpa mengurangi modal. Output form/detail, dashboard/laporan, tracking/WA, struk/A5 memakai total akhir. Servis gratis tidak perlu metode pembayaran saat diambil. WA unrepairable menjelaskan tidak dapat diperbaiki tanpa mengubah status internal menjadi Batal. Tidak ada persentase, cicilan, refund, dependency baru, atau perubahan data produksi.

Pemeriksaan developer dengan PHP 8.3:

- Suite terdampak awal: 51 tes / 524 assertions PASS.
- Tes revisi baru: 5 tes / 60 assertions PASS (fixture awal diperbaiki: reload revision setelah create, hanya kolom item yang diizinkan, nama Bank Mandiri sesuai daftar, tenant context setelah HTTP, periode UI eksplisit).
- Suite penuh awal: 252 PASS, 2 gagal pada fixture BanksTest yang menandai selesai tanpa item. Fixture dilengkapi satu jasa berbayar agar tetap menguji bank nonaktif/snapshot, bukan nota gratis. Tidak mengubah guard pembayaran.
- Suite penuh final: 254 PASS, 1 guarded MySQL SKIP, 2699 assertions. Log /private/tmp/repair-t062-suite-final.log.
- Setelah label Gratis pada daftar/detail laporan: Reports + ServiceRevisions 13 tes / 167 assertions PASS. Tidak mengulang suite lain yang tidak terpengaruh.
- Pint dirty PASS. PHPStan level7 PASS (debug serial karena sandbox menolak socket worker paralel); /private/tmp/repair-revisions-phpstan.log.
- SQLite sintetis terpisah /private/tmp/repair-t062.sqlite; migrasi forward PASS. Database operator dan produksi tidak disentuh.
- PDF A5/thermal aktual dibuat lewat Document, dirender Poppler dan ditinjau: Subtotal Rp100.000, Diskon −Rp100.000, Total Rp0 (Gratis), tanpa modal, satu halaman dan teks utuh. File uji /private/tmp/repair-t062-{a5,thermal}.pdf/png.

Browser CUA pada database sintetis: laporan 390×844 dan 1280×900, lebar dokumen tepat 390/1280 (tanpa overflow horizontal). Ringkasan metode dan rincian BCA Rp75.000 + Bank Mandiri Rp100.000 utuh. Form koreksi pada 390px: isian diskon Rp100.000 langsung menghasilkan Rp0 (Gratis), modal Rp20.000 dan laba Rp−20.000; simpan berhasil, label pembayaran Gratis, tanpa tombol/metode pembayaran. Tombol Tandai Diambil berhasil tanpa pembayaran; laporan kemudian menjadi omzet Rp100.000, modal Rp60.000, laba Rp40.000, transfer Bank Mandiri Rp100.000; dua baris gratis Rp0. Semua memakai UI dan komponen existing. Viewport dikembalikan, tab uji ditutup.

QA independen: lihat independent-qa.md. Snapshot kode 20 file tetap cocok dengan manifest sesudah pemeriksaan browser; browser hanya mengubah data fixture /private/tmp. Sesi QA diwariskan melalui native Codex, tidak mengklaim model berbeda tanpa receipt efektif.

Belum push/deploy, MySQL/MariaDB nyata dan printer fisik belum diuji untuk revisi ini. Tidak ada tes pengiriman WhatsApp; hanya tautan dan isi pesan diperiksa. Tidak membuat modul refund: koreksi nota menjadi gratis membersihkan metadata pembayaran nota, bukan transaksi pengembalian dana.
