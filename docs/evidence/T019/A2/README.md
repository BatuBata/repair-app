# T019 A2 — area sentuh nomor transaksi

**PASS developer check; menunggu QA independen T023 A2.** Perbaikan temuan T023 A1 F01, bukan dependency ke T023. Input keras T016 `c66d038ea448910e3cc8a8529f170f72ac064306`; sumber laporan A1 `7b7a438fcde75185c2160b642a02fbb2c6ed3ff1`. Assignment mulai dari `3683d27`; coordinator menambahkan metadata `5f2a5a27106ef1da4f30c09d4835f76ade0de365` saat worker berjalan. Hash sumber final di `source-proof.json` mengikat pemeriksaan ke kandidat commit yang menyertakan bukti ini; hanya `styles.blade.php` berubah terhadap A1.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery (kontrak worker) dan design-taste-frontend (app-screen) juga dibaca. Model diminta Sol high; identitas backend efektif tidak terlihat pada receipt worker sehingga tidak diklaim terverifikasi. Root kerja canonical `/Users/muzib/Projects/web/repair-app`, branch `codex/t019-reports-a2`. Worker tidak menulis TODO atau membuat worker lain.

## Perubahan terkecil

Satu aturan CSS di media `<1024px`: `.report-number a` memakai `inline-block`, `min-width:44px`, `min-height:44px`. Tinggi nyata anchor bertambah dari 20px menjadi 44px (72px untuk nomor yang membungkus pada baris nominal panjang 360px). Baseline nomor dan jumlah dipertahankan. Tidak ada pseudo-element yang memperluas area secara semu. URL/label, fungsi PHP, uang, fixture aplikasi, dan desktop tidak diubah. Pemanggil diperiksa: satu view laporan meng-include styles; service membentuk URL `tts.show` dan `sales.show`; tombol mata mengubah detail Livewire sehingga berbeda dari navigasi nomor.

## Pemeriksaan aktual

- `node /private/tmp/repair-t019-a2-20261113/check.mjs before`: **25 assertion PASS**, baseline 360/768/1280.
- `node /private/tmp/repair-t019-a2-20261113/check.mjs after`: **62 assertion PASS**, tiga lebar, data sampel dan nominal panjang/negatif, tanpa exception JavaScript. Script yang dijalankan disimpan sebagai `check.mjs`.
- Pada 360 dan 768px, event sentuhan CDP `Input.dispatchTouchEvent` di area anchor **di bawah teks** benar-benar menghasilkan `isTrusted:true`, `pointerType:touch` dan navigasi ke `/tts/1` serta `/penjualan/1`. `elementFromPoint` tepat mengenai anchor. Ukuran sampel TTS 108,42×44 dan penjualan 96,14×44; semua anchor pada data panjang juga ≥44×44.
- Tombol mata tetap paling kiri, tap nyata membuka dialog tanpa pindah `/laporan`; menutup dialog mengembalikan fokus ke tombol mata. Diperiksa pada ketiga lebar; desktop memakai pointer mouse terpercaya.
- **14 assertion pembandingan PASS** (`comparison.json`): uang sebelum/sesudah sama pada kedua dataset di tiga lebar, semua anchor HP/tablet ≥44×44, seluruh metrik desktop sama, dan screenshot sampel maupun nominal panjang **1280px byte-identik** sebelum/sesudah.
- Sampel PRD: omzet Rp2.600.000, modal Rp1.800.000, laba kotor Rp800.000, pengeluaran Rp65.000, net Rp735.000. Default bulan mencakup data besar A1: omzet Rp16.000.000.000.002.600.000 dan net −Rp1.179.869.179.999.265.000. Lebar dokumen selalu sama dengan viewport360/768/1280; IBM Plex Sans teramati. Peluang pindah baris nominal panjang tetap pada pemisah ribuan.
- `git diff --check`: PASS.

Semua enam screenshot final sampel/panjang dan referensi360/1280 dibuka dan dibandingkan. Gaya lembar, statement, grafik 5+7 desktop, tabel desktop/daftar HP, angka rata kanan dan aksi kiri mengikuti `mockup/laporan.html` serta DESIGN.md. HP memerlukan ruang baris tambahan24px pada nomor tertaut untuk target nyata44px; deskripsi berada di baris sesudah target, tidak ditutupi, nominal tetap di kolom kanan. Desktop tidak berubah. Nilai fixture berbeda dari ilustrasi mockup. Tidak menggambar arah baru atau mengubah desain halaman. Percobaan CSS pertama memakai `inline-flex;align-items:center`, lulus62assert tetapi menurunkan baseline nomor12px; disederhanakan menjadi inline-block, lalu pemeriksaan terarah diulang karena sumber berubah. Hasil antara dan gambar360 dipertahankan di `intermediate-centered/`, bukan kandidat final.

## Kegagalan harness dan isolasi

Dua run awal berhenti pada oracle yang salah: pertama memakai net data satu hari padahal layar menampilkan seluruh bulan, kedua salah satu kelompok ribuan pada string omzet. Keduanya murni kesalahan harapan harness sebelum CSS diedit. Log, stack dan screenshot dipertahankan di `failed-initial-oracle/` dan `failed-second-oracle/`; hasil tidak disamarkan. Baseline lengkap setelah koreksi tersimpan di `before/`; final di `after/`.

SQLite A1 dibuka read-only lalu disalin dengan SQLite backup API ke `/private/tmp/repair-t019-a2-20261113/database.sqlite`. Hanya password admin sintetis di salinan diganti untuk login; tidak dicetak/disimpan di Git. Config/cache/session/view/log/storage/TASTE_SKILL_TMP serta profile/CDP privat. Router memanggil `$app->useStoragePath(private)` sebelum bootstrap HTTP. Server PHP8.3 hanya loopback8191. `isolation-preflight.json`, router/setup tanpa rahasia, dan bukti proses browser tersedia. Tidak ada migrate/reset/drop, MySQL, GUI/CUA/Orca, WA atau deployment. Semua browser/profile dan serverPID17818 dihentikan; port8191 bebas, lihat `resource-release.json`. Runtime/fixture privat dipertahankan untuk audit.

Tidak mengulang feature suite/backend/static-analysis/build atau inspector generik penuh: PHP/view markup/routes/tests identik dengan hash A1; bukti PASS A1 direferensikan sebagai source-equal, bukan diklaim baru dijalankan. Uji browser terarah membuktikan perubahan CSS. Belum QA independen T023 A2, perangkat fisik/printer atau deployment; risiko volume data besar yang sudah dicatat A1 tetap sama.
