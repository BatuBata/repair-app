# T010 — alur admin servis, A1

Developer self-check **PASS**, branch `codex/t010-invoice`, base `af4c81aff6cd851cc7f7fc9713d546d7783338ae`, input T009 `12124a71bd24b30b7f343941bacd41315a4281f7`. Source final dipin `source-manifest.json`; coordinator mencatat commit saat handoff. Belum QA independen/F002 accepted atau merge main.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, DESIGN, skill design-taste-frontend dan mockup `tts-detail.html`/`tts-rincian.html` diterapkan tanpa redraw. Sol high diminta; model backend efektif tidak diekspos receipt. Tidak ada worker tambahan atau perubahan ledger oleh implementer.

## Hasil

- Admin menetapkan/mengganti teknisi aktif, status Dikerjakan dan assigned_at. Pengulangan penetapan teknisi yang sama mempertahankan waktu. Pilihan baru tidak mencantumkan teknisi nonaktif; nama historis tetap terlihat.
- `/tts/{id}/rincian` khusus admin: tambah/hapus Part/Jasa, qty/harga/modal integer, input Rupiah berformat dan total/subtotal/modal/laba langsung. Modal Jasa dipaksa0 pada server. Terbit hanya Dikerjakan dengan teknisi dan≥1baris; Rp0 diperbolehkan. Tanggal nota otomatis WITA, default garansi toko,0 Tanpa garansi, akhir garansi dari tanggal nota, flag Tidak dapat diperbaiki. Tanggal di luar rentang MySQL1000–9999 ditolak sebelum penulisan.
- Koreksi Selesai/Diambil mengganti baris secara atomik sambil mempertahankan tanggal nota, status, dan picked_up_on. Diambil hanya dari Selesai dengan tanggal valid (default hari ini); Batal hanya Diterima/Dikerjakan dengan alasan opsional. Enum existing digunakan untuk transisi.
- Semua write berada dalam transaksi retry5 dengan akun admin/order/teknisi yang relevan dikunci dan dibaca ulang. ID divalidasi sebelum query order. Sidik SHA256 order+items Locked menolak form nota kedaluwarsa dan duplicate issue/correction; status guards menolak duplicate collect/cancel. Produk dan penjumlahan uang diperiksa terhadap PHP_INT_MAX sebelum operasi. Tidak memakai float untuk total. `number_format` bawaan dipertahankan setelah batas integer terbukti tepat pada PHP8.3 ini.
- Detail admin menampilkan aksi/menu, status/data aktual dan nota/total. Field biaya tetap hanya variabel view admin; tidak ditempatkan di properti publik Detail. Boot invoice selalu memeriksa admin sebelum hidrasi/render; teknisi/list/detail/sukses dan respons403 setelah demosi tidak menerima biaya. Tanggal invoice/pengambilan ditampilkan sebagai tanggal, bukan waktu00.00. Tombol WA/print tetap hook kondisional T009.

## Pemeriksaan aktual

Semua PHP memakai `/opt/homebrew/opt/php@8.3/bin/php`.

| Pemeriksaan | Hasil/bukti |
| --- | --- |
| `vendor/bin/phpunit --colors=never tests/Feature/Invoice tests/Feature/Tts tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RoleLauncherTest.php` | Batch terakhir52PASS/377assertion dan1error import DB pada tes (`targeted-final.log`). Import tes diperbaiki; hanya kasus gagal diulang:1PASS/6assertion (`invalid-id-recheck.log`). **53 kasus/383assertion terbukti**, tidak mengklaim batch gagal sebagai PASS. |
| Tambahan batas angka + subset terkait uang/garansi | `vendor/bin/phpunit --colors=never tests/Feature/Invoice/InvoiceTest.php --filter 'test_large_totals|test_invalid_lines_dates_and_money_bounds|test_zero_total_unrepairable'`: **3PASS/26assertion**, `money-final.log`. Satu kasus baru membuktikan total tepat PHP_INT_MAX tetap int dan string9.223.372.036.854.775.807; tanda negatif/0 tepat. **54 kasus unik** keseluruhan; assertion subset tidak dijumlah ulang. |
| Pint source/routes/tests/helper fixture | PASS `pint-final.log`. |
| `vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` | PASS0error `phpstan-final.log`; tidak mengubah model/suppression untuk mengatasi laporan enum cast lama. |
| Chrome157/Node22, helper CDP existing | **29 observasi dan5privacy PASS**, `browser-results.json`, `browser-final.log`. Resource process/profile/config/DB/storage privat. |
| Source/environment | `.env`,14aset build, model/migrasi/shared CSS-JS/Composer tetap. Tidak build ulang; fresh production build gabungan tetap T015 karena Blade scan inputs berubah. |

Tes mencakup legal/illegal transitions, teknisi aktif/inaktif/ID palsu, demosi/nonaktif, status stale, Locked correction, fingerprint stale, integer multiplication/sum overflow, garansi mustahil, tanggal pickup invalid, Rp0/unrepairable, koreksi Selesai dan Diambil, dan rollback saat insert kedua gagal setelah satu baris sempat ditulis. Contoh LCD1.000.000/modal700.000+Jasa100.000/modal forged5.000 menjadi total1.100.000/modal700.000/laba400.000. Garansi11Nov+30hari=11Des; koreksi15Nov tidak memindahkan tanggal nota11Nov atau pickup15Nov. Semua data SQLite privat/in-memory; ini bukan uji konkurensi MySQL nyata.

Initial setup tes memakai StoreSetting tanpa field wajib dan mengharapkan error validasi dari form yang statusnya sudah Batal (render memang403). Setup/ekspektasi diperbaiki, sumber FAIL dipertahankan. Laporan statis awal salah menafsir cast enum/string; akses attribute/array serialisasi model existing digunakan, tanpa suppressing tipe.

## Browser dan visual

Pada360/768/1280, aksi nyata melalui komponen/rute aplikasi membuktikan assign→invoice add/remove Part/Jasa→issue→collect→correction→cancel; screenshot detail Diterima/Dikerjakan/Selesai, rincian, koreksi dan teknisi tersimpan. DOM tidak overflow, IBM Plex loaded. Input kas menampilkan1.000.000 dan menyimpan1000000, total1.100.000, profit negatif merah/0 netral, warranty0 Tanpa garansi. Tombol submit terbukti disabled selama request; total+Terbitkan/Simpan menempel di atas nav pada360/768. Dialog cancellation ditargetkan dengan `#cancel-order` agar tidak tertukar dialog navigasi shared.

Dropdown desktop native macOS sempat tidak memilih opsi; dua failure runner disimpan. Desktop akhirnya memakai fokus/keyboard dengan fallback set opsi+input/change setara selectOption bila diperlukan; **submit tetap event CDP nyata** dan assignment persisted benar. Hasil360/768 yang sudah lulus tidak diulang. Runner kemudian menganggap rejection `$refresh`403 yang memang diharapkan sebagai error; final runner menelan rejection itu, memeriksa body respons403 tanpa angka biaya, lalu detail baru untuk admin yang didemosi/teknisi tetap tanpa biaya. Lima observasi privacy semuanya PASS. Satu run opsional input sangat besar berhenti karena CDP Runtime.evaluate timeout; tidak diklaim sebagai PASS visual ekstrem. Batas integer tetap diuji pada PHP aktual, tanpa menambah formatter/refactor dari asumsi rounding.

Review membuka screenshot serta `comparison-invoice-final-*` dan `comparison-detail-assigned-*` pada360/1280. Acuan exact ditangkap pada3ukuran. Preview desktop awal membungkus angka total dan isian kas terlalu sempit; scoped CSS kembali memakai kolom140px mockup dan jumlah tidak terpotong. Hanya layout yang terdampak diperiksa ulang (`invoice-final-360/768/1280.png`). Nomor/tanggal/nama fixture berbeda dari mockup; tombol WA/print belum ada karena hook task berikutnya. Tidak menambahkan fitur prototipe palsu.

Fixture baru berisi4aktor/6TTS awal;3TTS tambahan hanya untuk screenshot layout, semuanya pada SQLite privat yang sama. `fixture-inventory.json`:3Diambil dengan invoice/pickup asli serta garansi0,3Batal,3Dikerjakan fixture layout; seluruh Jasa modal0. Config cache privat menahan path view/disk/session/log agar tidak memakai storage shared browser. Credential/key file0600 dalam direktori0700 di luar repo. `resource-release.json`: Browser.close, server8189 exit130, listener tidak ada. Tidak push/deploy/merge main.

## Batas

Android fisik/keyboard nyata, printer, MySQL/InnoDB race aktual dan visual angka mendekati PHP_INT_MAX belum diuji. Full suite lintas task/build tidak diulang. QA independen dan produksi build gabungan T015/T016 tetap wajib sebelum menerima F002. Semua bukti gagal dipertahankan.
