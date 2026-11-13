# T009 — A1, form dan alur TTS

Developer self-check **PASS** pada source final yang dipin oleh `source-manifest.json`. Branch `codex/t009-tts`; base fondasi M1 diterima `1d831380cd89b601c67321298eff07f686d8f010`, input T008 `3610253be1dedb1d9abad958059f52e33377f320`. Commit coordinator selama assignment hanya metadata/evidence dan tidak disertakan commit ini. Bukan QA independen atau penerimaan F002.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; Project Delivery dan design-taste-frontend diterapkan pada DESIGN serta mockup existing, tanpa redraw. Sol high diminta pada assignment; effective backend tidak diekspos receipt. Tidak ada worker tambahan atau perubahan TODO/AGENTS oleh implementer.

## Hasil dan keamanan

- Rute auth `/tts`, `/tts/baru`, `/tts/{id}`, `/tts/{id}/edit`, `/tts/{id}/sukses`. Form berisi empat wajib dan dua opsional, pesan per field, batas PRD100/500/100/255, HP tel, isian tetap saat error dan preview desktop. Nomor/HP/pembuat memakai layanan CreateServiceOrder/DocumentNumber/PhoneNumber existing. Sukses menampilkan nomor, data nyata, Buat TTS lagi dan Lihat TTS. Slot Kirim WA/Cetak hanya dirender bila rute `tts.whatsapp`/`tts.print` tersedia; T011/T017 dapat memasang hook itu. Tidak ada tombol palsu atau teks implementasi di UI.
- Daftar mencakup semua TTS bagi admin/teknisi, pencarian nomor/nama/HP (termasuk input0/+62 berformat), status filter, pagination30, menu shared untuk dua aksi dan satu tombol Lihat untuk satu aksi, selalu kolom kiri. Detail menampilkan data customer/barang, status/tahap/riwayat nyata dan teknisi; tidak mengarang histori perubahan/status.
- Teknisi hanya bisa membuka/edit miliknya saat Diterima. Admin dapat mengubah enam data dasar tanpa mengubah status, sesuai akses penuh admin dan pengecualian teknisi PRD§10.1. ID edit/detail dan mode sukses Locked; boot/render membaca akun terbaru. Save memulai transaksi, mengunci akun/order/kategori lalu memeriksa pemilik/peran/status dan keaktifan kategori sebelum menulis. Edit boleh mempertahankan kategori historis nonaktif yang sama; pilihan kategori baru hanya aktif. Nama kategori/pembuat lama tetap terlihat walau nonaktif.
- Model/order dan items tidak disimpan pada properti publik Livewire. Query daftar/detail memilih field yang diperlukan, tidak mengambil baris biaya; HTML, snapshot dan respons JSON teknisi diuji memakai sentinel harga/modal. Form mengirim hanya enam field tervalidasi. Tidak membuat migrasi/dependency, mengubah domain/layout/CSS/JS shared, atau menyentuh MySQL existing.

## Pemeriksaan aktual

Semua PHP memakai `/opt/homebrew/opt/php@8.3/bin/php`.

| Perintah/kasus | Hasil dan bukti |
| --- | --- |
| `vendor/bin/phpunit --colors=never tests/Feature/Tts tests/Feature/Auth/RoleLauncherTest.php tests/Feature/Auth/AuthenticationTest.php` | **32 PASS,262 assertion**, `targeted-final.log`. Login/launcher sekarang mengharapkan form200. Validasi, nomor berurutan, phone normalize, semua status/owner/admin, stale role/status/owner, transaksi recheck, nonaktif, forged Locked IDs, inactive history, search/filter/pagination dan privacy. |
| Setelah polish sukses/navigation, dua kasus create+privacy terkait | **2 PASS,46 assertion**, `detail-polish-check.log`. |
| Setelah polish nomor daftar satu baris, dua kasus search/privacy+pagination | **2 PASS,37 assertion**, `list-polish-check.log`. CSS target sentuh final hanya menambah area44px; logika sama. Tidak menjumlahkan run subset sebagai tes unik tambahan. |
| `vendor/bin/pint --test app/Livewire/Tts routes/modules/tts.php tests/Feature/Tts tests/Feature/Auth/RoleLauncherTest.php tests/Feature/Auth/AuthenticationTest.php docs/evidence/T009/A1/fixture.php` | PASS, `pint-final.log`. |
| `vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` | PASS0error, `phpstan-final.log`. Initial6 type findings diperbaiki memakai getAttribute enum cast dan query first typed, tanpa suppression/model luar scope. |
| `node browser.mjs`, dilanjutkan hanya kasus yang belum selesai | **29 observasi tercatat PASS +15 respons JSON privacy PASS**, `browser-results.json`, `browser-fourth.log`. Login nyata, error/input preserved, simpan dengan disabled selama request, success, create another, cari/filter, menu keyboard/Escape, edit sendiri, peer403 dan edit admin. Touch CDP360/768, mouse1280; sumber final privacy dari test dan halaman terkait juga diperiksa ulang. |
| `node success-final.mjs` sesudah polish sukses | **4 kasus PASS**: screenshot final360/768/1280, navigasi Buat TTS dan urutan data, serta Buat TTS lagi360 form kosong; `success-final.json/log`. |
| `node list-final.mjs` sesudah polish nomor/area sentuh | **3 ukuran PASS**: tanpa overflow, nomor satu baris dan seluruh kontrol daftar yang terlihat di HP/tablet≥44×44; `list-final.json`, `list-touch-final.log`. |
| Design lint | Tool asli tidak mengenali `.blade.php` (`design-lint.log`), lalu source tiga view yang sama disalin ke HTML privat dan dipindai: **0FAIL,1WARN** (`design-lint-final.log`). Warning enam em dash gabungan berasal placeholder kosong `—` yang diwajibkan DESIGN; tidak diubah menjadi angka palsu. |

Initial PHPUnit gagal pada dua cara setup tes: mencoba call sesudah set sudah403 (snapshot invalid), dan mutasi query-listener sesudah SELECT selesai. Tes diperbaiki agar input diset sebelum pencabutan izin dan mutasi memakai beforeExecuting sebelum SELECT; log `targeted-initial.log` tetap disimpan. Tes transaksi ini memeriksa recheck terarah pada SQLite, bukan bukti balapan InnoDB nyata.

Runner browser sempat gagal tiga kali karena membaca body/input sebelum redirect selesai dan selector Buat TTS lagi mengenai link sidebar tersembunyi. `browser-failure-initial/second/third.json` dan log masing-masing dipertahankan. Selanjutnya hanya kasus pending dijalankan; hasil360 yang sudah lulus dipakai ulang,768/1280 diselesaikan. Tidak mengubah aplikasi untuk mengatasi error runner. Hasil final untuk sukses/daftar mengganti screenshot setelah polish source terkait, bukan mengklaim run lama pada source baru.

## Visual dan lingkungan

Screenshot form, daftar, detail, sukses dan edit admin pada360/768/1280 tersimpan. Referensi exact `tts-baru.html`, `tts.html`, `tts-detail.html`, `tts-sukses.html` ditangkap pada ukuran yang sama. Perbandingan360/1280 dibuka sebagai `comparison-*.png`; screenshot desktop/tablet terkait juga ditinjau. Form mengikuti komposisi/ukuran acuan; nomor daftar dibuat nowrap sesudah perbandingan; sukses memakai data customer dahulu dan nav Buat TTS sesuai acuan. Perbedaan data/status/tanggal berasal fixture nyata, kategori mengikuti urutan DB, tombol WA/print dan QR belum ditampilkan karena masih task berikutnya; tidak ada prototipe/fake action. Preview nomor sebelum simpan tidak mengarang nomor yang belum terbit.

Semua layar shell font IBM Plex loaded, viewport dan document width sama360/768/1280; body16px mobile/tablet,14px desktop. Sticky save pada360 berada top763,bottom836 dari tinggi900, di atas nav64, tanpa scroll untuk menekan. `wire:loading.attr=disabled` diamati selama request nyata dengan latency300ms. HP memiliki type/inputmode tel. Sentinel919191/818181 dan PART PRIVAT UJI tidak ditemukan di HTML/snapshot maupun15JSON yang diamati. Ini bukti emulasi, bukan keyboard fisik Android.

Browser memakai Chrome157/Node22 dan helper CDP existing dengan TASTE_SKILL_TMP baru milik task. Server8189 memakai cache config privat yang menyimpan seluruh path view/disk/session/log privat; root storage tidak dipakai fixture browser. Fixture baru SQLite dibuat dari migrasi/seed existing lalu ditambah aktor dan TTS sintetis; credential/key berada di file0600/direktori0700 di luar repo. Tiga akun, sembilan kategori, lima order awal; penambahan/edit browser hanya fixture ini. `.env` root dan14aset build tetap cocok hash fondasi M1. Tidak npm build, DB reset/drop, push atau merge main. Chrome/server ditutup; port8189 terbukti tidak mendengarkan (`resource-release.json`).

## Pemeriksaan tambahan terbatas: kategori panjang

Snapshot source pertama `223063e2054f95439cd156cca247a2a3290eada3` dipertahankan. Sebelum handoff final, label kategori ditemukan masih mewarisi tinggi tetap60/72px walau master menerima nama255karakter. Satu koreksi CSS scoped mengubahnya menjadi tinggi mengikuti isi dengan min-height60/72 yang sama; source PHP aplikasi/JS/build tidak berubah. Coordinator memberikan ulang resource browser8189 untuk subset ini. `fixture.php long-category` hanya menambah kategori255karakter pada SQLite privat existing task (bukan schema lain).

`node docs/evidence/T009/A1/long-category.mjs`: **3 ukuran PASS**360/768/1280, `long-category.json/log` dan screenshot fullpage terkait. Label tidak overflow (`scrollHeight≤clientHeight+1`), semua label≥44×44, bawah label berada di atas isian keluhan, lebar dokumen sama viewport dan save tetap terlihat di atas nav. Screenshot360 benar-benar dibuka. Ukuran kategori biasa tetap60/72; screenshot normal sebelumnya tetap berlaku. Initial runner keliru mengharapkan innerText255 persis padahal browser memang memangkas spasi akhir menjadi254; bukti raw DB255 ada di `long-category-fixture.log`, initial evidence failure dipertahankan dan hanya pembanding innerText dinormalisasi. Bukan bug data atau perubahan aplikasi tambahan. Pint helper tetap PASS; tidak mengulang suite/build untuk dua deklarasi CSS. Chrome/server final ditutup dan port8189 tidak mendengarkan.

## Batas dan handoff

Belum diuji di Android fisik/keyboard nyata atau MySQL/InnoDB race aktual; AC25 manual perangkat tetap perlu QA. Full suite lintas task tidak diulang; subset auth/TTS dan analisis statis sesuai perubahan. QA independen/integrasi F002 tetap diperlukan. WA/print/QR, penetapan teknisi, transisi, rincian biaya, tracking dan dokumen tetap task terkait, bukan fitur palsu yang diklaim T009. Semua bukti gagal historis dipertahankan.
