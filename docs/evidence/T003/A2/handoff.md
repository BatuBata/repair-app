# T003 A2 — handoff implementer

- verdict: PASS untuk pemeriksaan implementer yang tercantum; penerimaan UI tetap menunggu QA gabungan independen T008.
- artifact_ref: branch `muzib/repair-t003-layout`, folder `docs/evidence/T003/A2/`.
- revision: `fb7fc2ad1bf5619c119a00f31522842d17205054` (kandidat kode dan bukti); commit setelahnya hanya menambah handoff ini.
- checked_revision: `fb7fc2ad1bf5619c119a00f31522842d17205054` (hasil browser/build/test pada isi kode yang sama; pemformatan test oleh Pint tidak mengubah kode produk).
- inputs: T001 `4107c49d9098ab47c465773857fd5ee2811eb1b4`; kandidat A1 `e8f0553ccf64dab23dab4866e23fd8f17697c691`.
- evidence: `browser-results.json`, `target-results.json`, `contrast.json`, `compare-{list,form,money,dialog,toast,empty}-{360,768,1280}.png`, `contact-{360,768,1280}.jpg`, `inspector/report.json` dan `technician-{360,1280}.png` pada folder ini.
- Model diminta `gpt-6.1-terra` high; identitas backend efektif belum teramati secara independen. Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`.

## Perubahan dan pengamatan visual

Tautan Repository/Documentation dihapus; navigasi aktif berdasarkan path dan subpath, membedakan `/tts/baru` dari detail `/tts/{id}`, serta menandai Menu untuk modul di dalamnya. Identitas Admin/Teknisi dan avatar memakai user aktif; fixture autentikasi sintetis hanya dibuat setelah gate environment `local`/`testing` di route preview. Field uang memakai awalan Rp dan rata kanan seperti rincian mockup; dialog memakai permukaan putih dalam mode gelap sistem, judul 600, toast memiliki ikon, tab berukuran minimal 44px, dan isi setelah bilah aksi dapat digulir terlihat.

Semua contact sheet diperiksa: kiri mockup yang bersesuaian, kanan preview. Perbedaan yang disengaja: data dan jumlah baris sintetis lebih sedikit, preview menggabungkan beberapa contoh dalam satu halaman, judul dan isi toast contoh berbeda, avatar Pak Arif memakai inisial `PA` dari User existing (mockup memakai `A`), dan tombol satu aksi memiliki kolom lebih lebar agar tidak menimpa teks. Field nominal memiliki awalan Rp dan angka rata kanan; form, dialog dan empty state tidak mereplikasi seluruh layar domain. Pada pemeriksaan awal A2, dialog tampil gelap dan empty state tertutup bilah aksi; gambar A2 menunjukkan keduanya setelah koreksi.

## Perintah / expected / actual

- `npm run build`: expected build selesai; actual berhasil dalam 347 ms. Peringatan font fallback `fontaine` opsional dan path `/fonts/` diselesaikan saat runtime; Chrome mengonfirmasi kedua keluarga IBM Plex dimuat.
- `APP_KEY=<kunci sintetis> /opt/homebrew/opt/php@8.3/bin/php artisan test tests/Feature/Layout`: expected gate production, field, role dan nav benar; actual 3 tes, 22 assertion lulus, 154 ms. Pemeriksaan awal A2 gagal karena assertion substring HTML rapuh; diganti pemeriksaan DOM dan lulus.
- Perintah yang sama untuk `tests/Feature/Auth tests/Feature/Settings`: expected alur starter tetap berjalan sesudah shell berubah; actual 32 tes, 87 assertion lulus, 2.313 detik. Ini memperbarui bukti A1 karena shell/settings berubah; bukti A1 tetap disimpan.
- `vendor/bin/pint --test routes/modules/layout-preview.php tests/Feature/Layout/LayoutPreviewTest.php`: awalnya gagal pemisah antar method pada test, diperbaiki Pint; pemeriksaan ulang lulus. `node --check` pada skrip JS dan PHP lint route serta `git diff --check` lulus.
- `node docs/evidence/T003/A2/browser-check.mjs`: expected interaksi dan tampilan final; actual 23/23 pemeriksaan lulus pada Chrome 157 headless memakai CDP, tanpa exception. CDP mengirim `Input.dispatchTouchEvent` pada koordinat hasil `getBoundingClientRect`; event `touchstart` `isTrusted=true` benar mengenai tombol dan menu terbuka. Panah atas/bawah, Escape dan pemulihan fokus, sentuh di luar, scroll, dialog Escape dan fokus, nominal kosong/nol/tanda minus/karakter campur, font dan overflow diuji. Awal pemeriksaan dialog gagal karena skrip CDP tidak mengirim virtual key code; setelah kode tombol benar, native Escape dan fokus lulus.
- `node docs/evidence/T003/A2/target-check.mjs`: klik desktop membuka menu dan klik luar menutup; semua target terukur pada halaman dan dialog 360px serta halaman 768px berukuran ≥44×44, body/input 16px di 360/768 dan 14px di 1280, toast hilang setelah 2,8 detik. Daftar target desktop di JSON memang mencatat kontrol <44px karena batas tersebut hanya berlaku <1024px.
- Chrome CDP `Page.getAppManifest` mengurai nama, standalone, warna, start URL dan dua ikon tanpa error; `Page.getInstallabilityErrors` kosong, dan kedua PNG berhasil didekode pada 192×192 dan 512×512. Ini hasil Chrome desktop pada localhost, bukan uji pemasangan Android.
- `inspect.mjs <preview> --app --widths 360,768,1280`: exit 0, overflow 0 di ketiga lebar, satu WARN label navigasi Beranda 13px dari tepi pada 360px, sesuai susunan navigasi mockup. Perhitungan pasangan token pada `contrast.json`: teks biasa terendah 5,14:1, pasangan status terendah 6,43:1, tepi input 3,17:1.
- Waktu kerja teramati sekitar 10:40–10:50 WITA; durasi individual di atas hanya yang dikeluarkan alat, tanpa perkiraan biaya model.

## Yang tersisa / risiko

Instalasi pada Android fisik NOT_RUN, dijadwalkan T025, dan QA hasil gabungan independen tetap T008. Pemeriksaan kontras di atas mencakup pasangan token yang digunakan, bukan audit setiap kombinasi browser/perangkat; formatter uang membuang tanda minus dan karakter nonangka, sehingga validasi nilai bisnis tetap tanggung jawab server pada task domain. Rute modul produk yang belum diimplementasikan masih menunggu task berikutnya; tidak ada push, deploy, perubahan MySQL atau lockfile.
