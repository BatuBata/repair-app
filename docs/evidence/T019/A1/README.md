# T019 A1 — laporan rentang tanggal

Implementasi mandiri, belum penerimaan fitur atau QA independen T023. Branch `codex/t019-reports`; sumber awal assignment `66359012ac0e4febec1f21c4154ba23085212333`, HEAD persiapan workspace `c2ecfb1`. Input keras T016 `c66d038ea448910e3cc8a8529f170f72ac064306` telah diterima pada main `6e6cb0967923e89ec61900fda069336b7c5bf5e4`. Model diminta Sol high; receipt identitas backend efektif tidak tersedia untuk worker ini.

Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery dan aturan worker/runtime juga dibaca. Tampilan mengikuti DESIGN.md dan `mockup/laporan.html` dengan gaya framed-sheet yang sudah disetujui; tidak menggambar arah baru atau mengubah shell. Pertanyaan layar: berapa laba bersih toko pada periode yang dipilih? Laba rugi menjadi angka utama, lalu omzet per hari dan bukti transaksi.

## Hasil dan pemeriksaan aktual

- Rute `/laporan`, nama `reports.index`, admin aktif diperiksa ulang sebelum query/aksi. Tanggal `from`/`to` WITA default awal bulan–hari ini, rentang inklusif dan format ISO ketat dalam batas 1000–9999.
- Sampel PRD 11 November: omzet 2.600.000, modal 1.800.000, laba kotor 800.000, pengeluaran 65.000, laba bersih 735.000. Nota batal/belum terbit dikecualikan; koreksi mengambil nilai baru pada tanggal nota asli; penjualan terhapus hilang. Restok hanya informasi.
- Seluruh perkalian/subtotal/agregat uang memakai Brick Math BigInteger existing. Dua transaksi legal masing-masing omzet 8.000.000.000.000.000.000 menghasilkan omzet exact 16.000.000.000.000.000.000, modal 17.179.869.180.000.000.000, laba bersih −1.179.869.180.000.000.000. Restok agregat sebesar 16.000.000.000.000.000.000 juga terbukti exact. Tidak memakai SQL SUM atau float untuk uang. Koordinat grafis adalah rasio piksel terbatas; sumbu besar memakai tick integer dalam triliun, dengan satuan jelas.
- `php artisan test tests/Feature/Reports --compact`: **7 tes / 99 assertions PASS**, mencakup contoh, rentang/default/invalid/batas, koreksi/hapus, akses dicabut, nilai besar, dan bucket grafik. Setelah perubahan markup pemisah ribuan dan keterbacaan HP, hanya dua tes tampilan yang relevan diulang: `--filter='prd_sample|exact_large'`: **2 tes / 32 assertions PASS**. PHP yang digunakan selalu `/opt/homebrew/opt/php@8.3/bin/php`.
- `php vendor/bin/phpstan analyse app/Livewire/Reports app/Services/Reports routes/modules/reports.php --no-progress --memory-limit=512M`: **PASS, 0 errors**.
- `php vendor/bin/pint app/Livewire/Reports app/Services/Reports routes/modules/reports.php tests/Feature/Reports --test`: **PASS**.
- Browser Chrome headless privat: **32 assertions PASS**, tidak ada exception JavaScript. Kontrol Hari ini/7 hari, tanggal bebas, filter jenis, rincian, tutup/fokus kembali, tooltip keyboard/Escape/ketuk sentuhan, dan navigasi jendela grafik benar-benar dijalankan. Lihat `browser-results.json` dan `browser-check.log`.

## Grafik dan pembandingan visual

Total, rata-rata, tertinggi, serta rincian memakai seluruh rentang laporan. Tampilan grafik dibagi jendela 31 hari agar rentang legal 1000–9999 tidak membentuk jutaan elemen. Jendela aktif tertulis, tombol sebelumnya/berikutnya nyata dan dapat difokuskan. Di HP, hari bergeser horizontal dengan target sentuh 44px, dimulai dari hari terbaru; tanggal tanpa omzet tidak diberi batang. Rata-rata uang dibulatkan ke bawah hanya untuk label per hari dan diberi catatan bila ada sisa pembagian; total tetap exact.

Screenshot `populated`, `empty`, `negative-large`, dan `error` tersedia pada 360/768/1280px. Rincian, filter, tooltip keyboard/tap dan jendela berikutnya juga disimpan. Capture final menunggu font dan menggulir dokumen ke atas; bilah bawah tetap berada di batas viewport asli pada screenshot halaman panjang. Semua lebar dokumen sama dengan viewport, tidak ada luapan horizontal halaman. Font IBM Plex Sans teramati.

Pembandingan manual dilakukan terhadap `reference-laporan-360.png` dan `reference-laporan-1280.png`: shell dan grid 5+7/12 mengikuti acuan, statement tanpa kartu, kolom aksi paling kiri, satu aksi rincian sehingga tidak memerlukan menu `···`, daftar HP dan tabel desktop, footer menegaskan laba bersih. Nilai fixture berbeda dari data ilustrasi mockup dan sengaja berasal dari sampel PRD. Label tanggal terlihat; info restok dan jendela grafik ditambahkan sesuai kontrak. Nominal panjang memakai baris HP penuh, font minimal 16px, dan peluang pindah baris hanya pada pemisah ribuan.

Inspector app-screen pada snapshot privat dengan CSS/font bundle lokal tertanam: **tidak ada FAIL**. WARN `tiny-text` (metadata/sumbu 11–12px diwajibkan DESIGN), `no-data-hue` (detektor tidak mengenali batang HTML/CSS custom; teal nyata terlihat pada screenshot), `edge-text` (label Beranda pada shell bersama, bukan scope T019). Tidak ada aturan yang disembunyikan memakai `--allow`.

## Isolasi, kegagalan awal, dan batas

`isolation-preflight.json` mencatat SQLite, config cache, session, view, log, storage dan TASTE_SKILL_TMP privat; server hanya loopback8191. `browser-process.json` mencatat profile/proses browser milik assignment. Tidak memakai MySQL, database existing, GUI/CUA/Orca, kirim WA, atau deployment. Fixture dan cache privat dipertahankan pada `/private/tmp/repair-t019-a1-20261113`, tanpa kredensial aplikasi nyata dalam bukti Git. Browser dan server ditutup saat handoff; lihat `resource-release.json`.

Pemeriksaan awal A1 (sebelum kandidat final) gagal: lima assertion agregat dan dua kompilasi tampilan. Penyebab: batas tanggal akhir SQLite disimpan sebagai timestamp, sehingga perbandingan tanggal string mengecualikan hari terakhir; serta directive Blade tanpa pemisah sesudah teks `/hari`. Diperbaiki dengan whereDate inklusif dan pemisah directive. Analisis statis awal juga menemukan enum magic-property yang diinferensikan sebagai string; akses kategori kini melalui raw value yang memang string. Server privat awal memakai working directory yang salah dan gagal memuat index.php; dipulihkan dengan cwd public. Tidak ada data produksi terlibat.

Inspector snapshot pertama gagal karena stylesheet crossorigin ditolak pada origin file; bukti disimpan di `inspector-failed-static-cors*`. Pengulangan menanamkan CSS/font lokal yang sama ke snapshot privat, sehingga valid mengukur tampilan. `initial-1280.png` menyimpan tampilan awal sebelum perbaikan sumbu/readability, bukan bukti final.

Tidak menjalankan full suite, build gabungan T022, QA independen T023, MySQL, printer fisik, atau deployment. Banyak transaksi masih dimuat lengkap untuk rincian, mengikuti pola modul existing; volume data produksi besar belum diuji. Penerimaan fitur dan rilis tetap mengikuti gate coordinator.
