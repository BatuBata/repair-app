# T006 — discovery dokumen dompdf dan QR

Hasil **PASS untuk discovery sintetis**, 12 November 2026. Empat PDF dan HTML pendamping berhasil dibuat; QR dari render setiap PDF terdecode persis ke URL contoh. Ini bukan penerimaan dokumen produksi, cetak fisik, atau gate T017/T023/T025.

## Artefak yang diperiksa

Semua identitas, tanggal, nomor HP, harga, dan token adalah contoh; dokumen berlabel `CONTOH SINTETIS T006`. TTS berarti tanda terima servis. Fixture memiliki keluhan panjang dan tiga baris nota, termasuk deskripsi dengan pemisah baris dan qty 2.

| Dokumen | PDF | HTML | Ukuran setiap halaman | Halaman | PNG yang dilihat |
|---|---|---|---|---:|---|
| TTS A5 | [PDF](../evidence/T006/tts-a5.pdf) | [HTML](../evidence/T006/tts-a5.html) | 148 × 210 mm | 1 | [1](../evidence/T006/tts-a5-1.png) |
| Nota A5 panjang | [PDF](../evidence/T006/nota-a5.pdf) | [HTML](../evidence/T006/nota-a5.html) | 148 × 210 mm | 2 | [1](../evidence/T006/nota-a5-1.png), [2](../evidence/T006/nota-a5-2.png) |
| TTS thermal | [PDF](../evidence/T006/tts-58mm.pdf) | [HTML](../evidence/T006/tts-58mm.html) | 58 × 280 mm | 1 | [1](../evidence/T006/tts-58mm-1.png) |
| Nota thermal panjang | [PDF](../evidence/T006/nota-58mm.pdf) | [HTML](../evidence/T006/nota-58mm.html) | 58 × 400 mm | 1 | [1](../evidence/T006/nota-58mm-1.png) |

[Fixture publik](../evidence/T006/public-fixture.json), [manifest](../evidence/T006/manifest.json), [hasil pemeriksaan](../evidence/T006/self-check.json), dan [decode QR + batas teks](../evidence/T006/render-check.json) disimpan bersama artefak. PNG adalah render PDF, bukan screenshot Android/browser. Seluruh lima PNG final dilihat dengan `view_image` setelah perubahan QR terakhir: teks, tabel, total, garansi, dan QR tidak tertutup, terpotong, atau bertumpuk. Pemeriksaan koordinat huruf juga memastikan semua teks di dalam halaman, dengan toleransi 2 pt dari tepi.

Nota A5 halaman 1 memuat seluruh rincian dan total; halaman 2 memuat garansi dan QR. Tidak ada halaman kosong. Halaman lanjutan tidak mengulang identitas/nomor halaman, dan ruang kosongnya besar: **batas kerapian yang belum selesai untuk T017**, bukan layout produksi yang disetujui. TTS thermal juga menyisakan ruang di bawah karena panjang contoh tetap.

## QR yang dipilih dan pembuktiannya

Pilih **`bacon/bacon-qr-code v3.1.1`, BSD-2-Clause**, sudah terpasang dari `composer.lock` melalui Fortify; tidak ada dependency atau lockfile baru. Commit sumber terkunci `4da2233e72eeecd9be3b62e0dc2cc9ed8e2e31c2`. Metadata versi terpasang dan lisensi ada di [versions.json](../evidence/T006/versions.json). Implementasi resmi menyediakan backend SVG melalui XMLWriter. [Sumber resmi BaconQrCode](https://github.com/Bacon/BaconQrCode).

Gunakan `SvgImageBackEnd` → SVG → `<img src="data:image/svg+xml;base64,...">`. Empat dokumen memakai instance Dompdf masing-masing, akses jaringan dan eksekusi PHP dimatikan. SVG final 256 × 256, margin putih **4 modul**, hitam di atas putih, tanpa logo; kotak QR 22 mm pada A5 dan 26 mm pada thermal. Writer bawaan versi ini memakai koreksi kesalahan L. QR besar, token lebih panjang, atau pilihan koreksi lain perlu diuji ulang di T017; satu fixture tidak membuktikan semua token.

URL yang diharapkan:

```text
https://repair.example/t/SYNTHETIC_T006_0123456789abcdef0123456789abcdef
```

`repair.example` adalah domain contoh dan tidak dibuka. Ini menguji muatan URL `/t/{token}`, bukan implementasi/otorisasi rute tracking atau generator token produksi T004/T016.

PDF diraster pada 150 dpi menggunakan PDFKit macOS. Decoder lokal Core Image membaca ulang **file PNG hasil PDF**, bukan SVG asal. Hasil: TTS A5 h1, TTS 58 mm h1, nota A5 h2, nota 58 mm h1 masing-masing menghasilkan satu URL identik di atas; nota A5 h1 memang tidak memiliki QR. Kesetaraan string diperiksa otomatis, termasuk token. Ukuran halaman diukur dari MediaBox PDF dan diperiksa dengan toleransi 0,01 mm; hasil aktual dekat 148,00016 × 210,00014 dan 57,99984 mm lebar.

## Isi dan kerahasiaan

Generator memproyeksikan daftar field publik secara eksplisit sebelum merender. Fixture internal sengaja mengandung modal sentinel 734567, 23456, 4567 serta catatan `INTERNAL_COST_SENTINEL`; semuanya tidak masuk HTML atau teks PDF. Yang diperiksa: identitas toko, nomor TTS, tanggal terima, customer/HP, barang, keluhan, catatan pengambilan; nota menambah tanggal nota, teknisi, seluruh deskripsi/qty/harga jual/subtotal, total **Rp1.130.000**, garansi 30 hari sampai **12 Desember 2026**, dan syarat garansi. TTS tidak memuat harga nota.

Ini membuktikan pemisahan data pada spike; bukan pengujian izin Admin/Teknisi, endpoint customer, koreksi nota, flag tidak dapat diperbaiki, penjualan, atau data produksi. T017 tetap harus menggunakan proyeksi publik dan T023 memeriksa kebocoran data pada aplikasi sebenarnya.

## Penyesuaian mockup dan batas renderer untuk T017

Acuan dibaca: `DESIGN.md`, `PRODUCT.md`, `mockup/struk.html`, `mockup/nota-a5.html`; PRD §7, §10 isi dokumen, §16, §19 Q-02, §20.2. Struktur putih/hitam, garis kepala, nomor monospace, harga rata kanan, metadata, garansi, dan posisi QR mengikuti pola dokumen acuan. Margin A5 12 mm; thermal 4 mm atas, 5 mm kiri/kanan, 6 mm bawah, sehingga area isi 48 mm di kertas 58 mm.

- Gunakan blok dan tabel sederhana. Mockup memakai flex/grid, variabel CSS, `clamp()`, dan `aspect-ratio`; jangan langsung menyalin CSS itu ke PDF. Dompdf menyatakan flex/grid dan SVG inline mentah tidak didukung; tabel tidak dapat memecah satu sel panjang lintas halaman. Data URI SVG merupakan workaround resmi. [Batas resmi dompdf](https://github.com/dompdf/dompdf#limitations-known-issues).
- Versi terkunci yang diuji: `barryvdh/laravel-dompdf v3.1.2` (MIT), `dompdf/dompdf v3.1.6` (LGPL-2.1). Spike memanggil engine langsung tanpa boot Laravel; integrasi facade/route masih T017.
- Spike memakai **DejaVu Sans / Sans Mono bawaan dompdf** (body 9 pt, label 8 pt), karena aset IBM Plex mockup hanya WOFF2 dan belum diuji sebagai font PDF. Ini deviasi font yang disengaja untuk discovery; T017 perlu font TTF/format yang didukung, lisensi, embedding, dan pemeriksaan ulang pemenggalan teks sesuai DESIGN. HTML browser masih memakai fallback sans-serif jika DejaVu tidak tersedia.
- Halaman A5 panjang boleh berlanjut tanpa mengecilkan huruf. T017 perlu identitas dan nomor halaman lanjutan serta pengelompokan total/garansi/footer. Uji satu baris sangat tinggi dan daftar banyak baris; fixture tiga baris ini belum membuktikan batas maksimum data.
- Panjang thermal **280/400 mm hanya untuk fixture ini**, bukan kebijakan panjang nota. T017 perlu mengukur/paginasi isi dan menetapkan batas yang sesuai driver. Mengunci tinggi dari contoh dapat memboroskan kertas atau memotong data lain.
- `@page size: 58mm auto` pada mockup tidak sesuai grammar ukuran CSS: gunakan dua panjang pasti atau `auto` sendiri; `auto` bukan tinggi gulungan tanpa batas. HTML contoh memakai ukuran pasti yang sama dengan PDF. Ukuran CSS tidak menjamin driver menerima media tersebut. [Spesifikasi ukuran halaman W3C](https://www.w3.org/TR/css-page-3/#page-size-prop).

## Rekomendasi cetak dari Android

Sediakan PDF A5 sebagai cadangan, dan pertahankan HTML serta PDF 58 mm. Untuk printer thermal Bluetooth yang modelnya belum diketahui, **uji PDF 58 mm melalui layanan/aplikasi printer yang kompatibel setelah Q-02 dijawab**; jangan menjanjikan cetak Bluetooth langsung dari browser.

| Jalur | Pilihan yang disarankan | Batas pasti / yang harus diverifikasi |
|---|---|---|
| Chrome → Bagikan → Cetak | Pilihan awal bila printer tersedia di dialog Android melalui layanan cetak yang cocok; pengguna memilih printer dan ukuran, atau Simpan sebagai PDF | Petunjuk Chrome menyebut printer yang menerima data lewat Wi-Fi/jaringan seluler. Daftar printer, media 58 mm, margin, dan skala tergantung layanan/driver; CSS tidak memasangkan Bluetooth. |
| PDF 58 mm → layanan/aplikasi thermal | Kandidat untuk Bluetooth: aplikasi vendor printer atau **RawBT**, setelah jenis koneksi dan perintah grafis kompatibel dikonfirmasi | RawBT menyatakan dukungan layanan cetak, Bluetooth, USB bila perangkat mendukung, serta jaringan port 9100; model/protokol dan hasil nyata tetap perlu diuji. Versi tanpa lisensi menambahkan pemberitahuan pada hasil cetak menurut pengembang. |

Sumber: [petunjuk resmi Chrome Android](https://support.google.com/chrome/answer/1069693?hl=en&co=GENIE.Platform%3DAndroid), [alur cetak resmi Android](https://developer.android.com/training/printing/custom-docs), [listing dan penjelasan pengembang RawBT di Google Play](https://play.google.com/store/apps/details?id=ru.a402d.rawbtprinter&hl=en_US), diperiksa 12 November 2026. Rekomendasi kompatibilitas adalah kesimpulan bersyarat dari dokumentasi; tidak ada aplikasi yang diinstal atau dibeli.

Dialog browser dan aplikasi thermal dapat bekerja bersama: RawBT juga layanan cetak Android. Pengembang menjelaskan PDF diperlakukan sebagai gambar oleh drivernya; format baris tetap tanggung jawab aplikasi asal. Jadi PDF A5 yang dikecilkan ke 58 mm berisiko terlalu kecil; gunakan PDF 58 mm tersendiri. Jangan menganggap lebar kertas 58 mm sama dengan lebar area cetak atau jumlah dot kepala printer.

**Printer fisik klien / HP Android / Bluetooth / cetak dua salinan: NOT_RUN.** Q-02 belum menyediakan merek/model, antarmuka, driver/aplikasi, lebar cetak efektif, dan DPI. T025 harus memeriksa kertas aktual, skala 100% atau setelan yang disepakati, margin, teks terpanjang, total, garansi, QR hasil kertas yang dipindai HP, pemotongan/feed, serta dua salinan untuk barang dan customer. QR yang terdecode pada PNG komputer tidak membuktikan keterbacaan pada kertas thermal.

## Reproduksi dan hasil aktual

Jalankan dari root worktree. PHP global tetap 7.4.33; proyek memakai PHP 8.3.30. Tidak ada MySQL, `.env`, migrasi, npm, atau browser interaktif yang digunakan.

```sh
# Dependency terkunci, sudah dijalankan; hook setup/npm/migrasi tidak berjalan.
/opt/homebrew/opt/php@8.3/bin/php $(command -v composer) install --no-interaction --prefer-dist --no-scripts --no-plugins

/opt/homebrew/opt/php@8.3/bin/php -l spikes/documents/generate.php
/opt/homebrew/opt/php@8.3/bin/php -d zend.assertions=1 -d assert.exception=1 spikes/documents/generate.php
/usr/bin/swift spikes/documents/render.swift docs/evidence/T006
python3 spikes/documents/check.py

git diff --exit-code cd41c80c2b37be84b737470c92ab2f684a87db9f -- composer.json composer.lock
```

| Pemeriksaan aktual | Expected | Actual |
|---|---|---|
| Composer install | Hanya versi lock, tanpa hook/plugin | Exit 0; 147 install, 0 update/removal; manifest/lock tidak berubah |
| PHP lint | Sintaks valid | Exit 0 |
| Generator dengan assertion aktif | 4 PDF; halaman 1/1/2/1 sesuai fixture | Exit 0; [generate.log](../evidence/T006/generate.log), 0,433 detik di dalam generator |
| Render, ukuran, batas huruf, decode | 5 halaman tidak kosong, ukuran tepat, 1 URL/berkas identik | Exit 0; [render.log](../evidence/T006/render.log), 0,303 detik di dalam script (tidak termasuk startup/kompilasi Swift) |
| Self-check pypdf | Isi lengkap, jumlah/harga benar, tanpa modal, decode cocok | Exit 0; [self-check.log](../evidence/T006/self-check.log), 1,966 detik |
| View image | Tidak terpotong atau overlap | Kelima PNG final di tabel artefak dilihat; batas kerapian halaman kedua dan ruang kosong dicatat |
| Diff Composer | Tidak berubah | Exit 0 |

Poppler tidak terpasang (environment); dipulihkan dengan PDFKit/Core Image yang sudah ada di macOS 26.5, tanpa memasang alat global atau dependency proyek baru. `pypdf` sudah tersedia. PDF skill marker dijalankan sukses sekali sebelum pembuatan empat PDF. Instalasi Composer dan startup Swift tidak diberi angka total karena tidak ditiming sebagai satu fase.

## Riwayat kegagalan dan pin kerja

Bukti gagal tetap disimpan:

1. Eksperimen lokal pertama: assertion `tts-58mm must fit one page` gagal (PHP exit 255). Diagnostik dengan assertion dimatikan hanya untuk mengamati halaman: TTS 58×210 mm dan nota 58×270 mm menjadi dua halaman; nota A5 juga dua halaman. [Log gagal](../evidence/T006/A1-failure.log), [source eksperimen](../evidence/T006/A1-generate.php.txt), [diagnostik](../evidence/T006/A1-diagnostic.log), PDF/PNG prefiks `A1-` adalah bukti lama, **bukan hasil final PASS**. Shell gabungan pertama mengembalikan 0 dari `cat`; itu tidak mengubah kegagalan PHP menjadi lulus.
2. Eksperimen lokal kedua: thermal diperpanjang dan metadata A5 dirapikan, tetapi assertion satu halaman nota A5 masih gagal (exit 255): [log](../evidence/T006/A1-experiment2-failure.log). Ini kegagalan asumsi evidence satu halaman, bukan hilangnya isi dokumen. Persyaratan discovery tidak mewajibkan satu halaman A5.
3. Coordinator lewat pesan `msg_df8ee60a8a40` mengonfirmasi kedua eksperimen tetap dalam logical A1, mengizinkan paginasi dan pemeriksaan seluruh halaman. Assertion final memakai jumlah halaman fixture yang benar. Margin QR dikoreksi dari 24 ke 4 modul setelah inspeksi agar bidang QR tidak terlalu kecil; seluruh render/decode final diulang.
4. Beberapa URL riset tidak dapat diambil: tag GitHub via web ditolak, rawbt.ru memberi 502. Ini evidence retrieval, bukan kegagalan produk; versi dibuktikan oleh lock + source vendor terpasang, dan klaim RawBT memakai listing resmi pengembang. Tidak ada kegagalan jaringan dependency atau pergantian versi tersembunyi.

Base `cd41c80c2b37be84b737470c92ab2f684a87db9f`; input **T001 `4107c49d9098ab47c465773857fd5ee2811eb1b4`**. Kontrak dibaca dari `/Users/muzib/Projects/web/repair-app/TODO.md`, task `T006`, dispatch `ctx_9250f1c11cfd`; canonical ledger tidak diubah. Requested model `gpt-6.1-sol`, effort high tercatat pada receipt coordinator; backend model efektif tidak terlihat dari sesi ini, sehingga tidak diklaim terverifikasi. Commit hasil lokal dan `checked_revision` dilaporkan dalam worker_done; [source-hashes.json](../evidence/T006/source-hashes.json) mem-pin kode yang diperiksa. Tidak ada merge, remote, push, atau deploy.

Ponytail **full** benar-benar dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery, PDF, dan panduan Orca worker juga dibaca. Self-check ini bukti implementer; QA independen dan integrasi tetap milik coordinator.

Yang belum diuji: browser HTML aktual, endpoint Laravel, data tak terbatas, font Plex PDF, HP/WA, printer fisik dan QR di kertas. Risiko utama: paginasi A5 lanjutan, panjang thermal tetap, kompatibilitas driver, dan keterbacaan cetak nyata; semuanya tetap pekerjaan T017/T023/T025.
