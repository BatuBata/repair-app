# T003 A1 — kandidat belum lengkap

- verdict: FAIL / belum memenuhi kontrak pembuktian; bukan penerimaan UI.
- artifact_ref: branch worktree `muzib/repair-t003-layout`; revisi adalah commit yang memuat berkas ini.
- inputs: base `8601b54a1302f03bea98b51e7e033b097599dc7c`, hasil T001 `4107c49d9098ab47c465773857fd5ee2811eb1b4`.
- checked_revision: isi kandidat pada commit ini; pemeriksaan browser dilakukan pada build dari isi yang sama, kecuali penambahan atribut tab/filter dan tautan starter setelah screenshot. Screenshot tersebut membuktikan tampilan awal, bukan interaksi tab baru.
- Model: receipt meminta `gpt-6.1-terra` high; model backend efektif tidak teramati secara independen, tidak diklaim.
- Ponytail full — dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`.

## Diff

Shell Blade, menu responsif, style dari mockup, ikon/huruf lokal, komponen status/field/nominal/aksi, contoh lokal, manifest dan uji route. Aset mockup sumber tidak diubah; tidak ada perubahan lockfile atau migrasi.

## Pemeriksaan yang dijalankan

- `composer install --no-interaction --no-scripts --prefer-dist` menggunakan PHP 8.3 dan `npm ci --ignore-scripts`: selesai memakai lockfile; tidak menjalankan setup/migrasi.
- `npm run build`: berhasil; Vite memperingatkan referensi `/fonts/...` diselesaikan saat runtime dan fallback font opsional tidak terpasang. Browser memuat IBM Plex Sans dan Mono pada ketiga lebar.
- `APP_KEY=<kunci tes sintetis> php artisan test tests/Feature/Layout tests/Feature/Auth`: 19 tes, 44 assertion lulus, 2,114 detik; pemeriksaan awal tanpa kunci gagal dengan dua error kunci enkripsi, lalu diperbaiki dengan variabel tes. `tests/Feature/Settings`: 15 tes, 49 assertion lulus, 882 ms. Setelah perubahan tab dan layout, `tests/Feature/Layout`: 2 tes, 6 assertion lulus, 169 ms.
- `node --check resources/js/layout.js`, PHP lint route, dan `git diff --check`: lulus.
- Server lokal port 8083 memakai SQLite in-memory, session array dan kunci sintetis; `/layout-preview` HTTP 200. Feature test membuktikan route 404 pada environment production.
- Orca browser: lebar/scrollWidth 360/360, 768/768, 1280/1280; font Sans dan Mono terdeteksi. Klik desktop membuka menu, panah bawah/atas pindah fokus, Escape menutup dan mengembalikan fokus; klik luar menutup; scroll halaman menutup setelah event scroll selesai. Field nominal `1234567` tampil `1.234.567`, nilai tersembunyi `1234567`; field HP bertipe tel. Filter tab Selesai memilih tab dan menyisakan baris Selesai.
- `compare-360.png`, `compare-768.png`, `compare-1280.png`: kiri `mockup/tts.html`, kanan preview. Data dan jumlah baris berbeda; preview menambah form dan bilah aksi, avatar tidak muncul untuk preview tamu, tombol tunggal memakai kolom lebih lebar agar tidak menimpa teks. Sebelum perbaikan, tombol tunggal menimpa isi pada 360px; screenshot tersimpan setelah perbaikan.
- Inspector app (`inspect.mjs ... --app --widths 360,768,1280`) menyimpan `inspector/report.json` dan gambar; tidak mencatat overflow atau error konsol, satu WARN tepi teks navigasi 360px (label Beranda 13px dari tepi, sesuai navigasi mockup).
- Manifest JSON dan kedua PNG dibaca: nama Toko Anugerah, standalone, start `/`, tema `#f2f5f6`, ukuran 192×192 dan 512×512.

## Belum terbukti / risiko

- Orca `tap` pada viewport mobile mengirim touch ke elemen HTML, bukan tombol pada koordinat yang diharapkan; interaksi sentuh nyata belum terbukti, meskipun klik mobile membuka menu. Perlukan verifikasi browser/perangkat lain.
- Chrome DevTools Application/installability tidak diperiksa; instalasi Android fisik tetap T025. Menu teknisi dengan pengguna berperan, kontras dan seluruh target sentuh belum diaudit lengkap.
- Screenshot perbandingan baru mencakup komponen daftar dengan `mockup/tts.html`, belum form, dialog, toast dan empty state terhadap mockup masing-masing. QA visual gabungan independen masih diperlukan.
- Komponen dengan perilaku baru belum memiliki pengujian browser otomatis yang tersimpan; periksa kembali fokus dan penutupan menu pada revisi gabungan. Rute tujuan modul produk sebagian belum ada sampai task berikutnya.
- Waktu total tidak diukur; durasi uji di atas adalah angka yang diamati, bukan estimasi biaya model. Tidak ada push, deployment, MySQL, atau pemeriksaan perangkat Android.
