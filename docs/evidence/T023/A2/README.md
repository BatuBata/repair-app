# T023 A2 — QA independen kandidat perbaikan

**PASS untuk kontrak software T023** pada `d047f25ea31d703660212e4205b937053c92adcb`, input T022 yang sama. F01 A1 tertutup pada kandidat ini; FAIL lama tetap dipertahankan di A1. Ini kesiapan software lokal, bukan penerimaan produksi atau perangkat fisik.

Ponytail **full** tetap dimuat dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; tester independen yang sama mengikuti Project Delivery, PRD v1.2, DESIGN dan mockup. Sol high diminta; model backend aktual tidak diekspos. Tidak ada implementasi, ledger, worker tambahan, push, merge main atau deployment oleh tester.

## Identitas sumber dan penggunaan ulang

[Input proof](input-proof.json) dan [freeze akhir](final-freeze.json) memverifikasi **230 sumber, 14 build, 9 font, 0 mismatch**. Salinan SQLite A1 milik tester dipakai; A1 asli tetap byte-identik. Source berubah hanya pada:

- Rule anchor nomor laporan di media <1024px: inline-block, min-width/min-height44px.
- Satu pengecualian pemindaian Tailwind `@source not '../../docs/evidence';`.

**228 sumber lainnya identik**, termasuk seluruh PHP/domain/otorisasi/schema/route/test, markup, shared layout, renderer dokumen, dependency dan font. [Bundle delta](bundle-delta.json) membuktikan12 file umum identik; hanya nama/hash CSS app dan manifest menunjuknya berubah. Perbandingan langsung build final A1→A2 menghapus **36 byte/satu aturan `[cursor:pointer]`**, dengan seluruh byte aturan yang dipertahankan identik. Tidak ada pemakai literal di app/resources/routes; pemeriksaan vendor Flux yang terpasang juga tidak menemukan konsumen. Perbandingan integrator *pre-exclusion A2*→final menghapus72 byte/dua aturan, termasuk `.lowercase` yang hanya muncul pada build sementara itu—bukan dua aturan hilang dari A1.

Bukti compiler/scanner Tailwind4.3.5 T022 A2 dibaca: sumber view/vendor eksplisit dipertahankan;602 berkas bukti dikeluarkan; sentinel milik task tidak mengubah kandidat/CSS, kontrol tanpa pengecualian mendeteksinya. Hash `app.css` sesuai sumber final. Tidak menjalankan ulang build atau menyalin bundle lain.

[Reuse proof](reuse-proof.json) memverifikasi **404 hash artefak A1**, producer independen `/root/t023_final`, evidence commit `c789e0a52e4195a41a9100e566561e0852c9fa11`, checked source `a95a3058c2160617d2e59ce7ac44685774d7528d`, per grup beserta source/hash/kondisi. Yang dipertahankan meliputi suite181PASS/1guardedMySQLSKIP/1913assert, Pint/PHPStan,111 layar,30 kasus angka besar/negatif,10PDF/5QR dan PDF panjang2/5/63halaman,6MySQL nyata serta baseline20tabel, auth remember-only, manifest, panduan/operasional. Dokumen PDF memakai CSS inline yang tidak berubah. Riwayat kegagalan/helper/isolation A1 tetap utuh. [Public/dependency source proof](supporting-source-proof.json) juga mem-pin manifest/ikon/index/lockfile yang tidak termasuk manifest230.

## Pemeriksaan baru yang benar-benar dijalankan

| Pemeriksaan | Hasil |
|---|---|
| F01 pada360/768 | **4 tap terpercaya PASS** pada area tambahan *di bawah teks*, untuk servis dan penjualan di kedua lebar. `event.isTrusted=true`, hit-test mengenai anchor, rute aktual tepat. Semua anchor laporan ≥44×44px; contoh96,14×44 dan108,42×44. [chrome-results.json](chrome-results.json), `tap-added-area-*.png`. |
| Dialog, fokus dan posisi aksi | Tombol mata tetap di kiri dan membuka dialog berbeda; tutup mengembalikan fokus ke trigger, Tab menuju link. PASS pada360/768/1280. Desktop anchor tetap inline/tinggi18px, tidak terkena rule mobile. |
| Laporan normal/besar/negatif | **9 layout PASS**, tiga kondisi × tiga lebar, tanpa scroll horizontal/teks terpotong/overlap. Nilai legal besar membungkus antar kelompok angka; nama panjang membungkus di barisnya. Sampel11Nov tepat2.6m/1.8m/800k/65k/735k. Screenshot penuh dan top/lower sheets ditinjau. |
| Perbandingan desain | `compare-approved-360/768/1280.jpg` dibandingkan dengan layar **laporan.html** yang sama dari mockup disetujui. Bingkai, font, urutan mobile, statement/chart dan tabel desktop sesuai pola. Perbedaan data/tanggal mengikuti fixture. A1 layar lain dipakai dengan provenance, bukan di-capture ulang. |
| Microsoft Edge nyata | **13 kasus PASS**, produk CDP `Edg/157.0.4341.57`, binary portable resmi Microsoft, profil/proses/TASTE terpisah. UA memang direduksi `Edg/157.0.0.0`; identitas lengkap dibuktikan product CDP. [edge-runtime.json](edge-runtime.json), [edge-results.json](edge-results.json). Tidak memasang global/mengubah browser bawaan. |
| Smoke Edge | Login/admin launcher, dashboard, tab grafik+tooltip keyboard, link laporan, input tanggal aktual dan sampel PRD, detail/filter/navigasi penjualan, form rincian nota tanpa simpan, validasi4wajib TTS tanpa create, logout, launcher teknisi,6HTTP403, HTML teknisi tanpa harga, tracking anonim/privasi. Screenshot ditinjau;0errorJavaScript. |
| PDF Edge | Dua request HTTP A5 admin/publik:200/application-pdf/no-store; isi/modal/privasi diperiksa dengan pypdf. [edge-pdf-smoke.json](edge-pdf-smoke.json). Ini smoke integrasi browser baru, bukan pengulangan suite PDF. |
| Aset final | **23 aset/font SHA256/HTTP200** dari Chrome dan23 dari Edge cocok persis. Edge benar-benar memuat `app-CDn1G0_W.css`; tidak ada kegagalan CSS/script/font/image. `chrome-assets.json`, `edge-assets.json`. |

[Pemetaan26 AC dan16 BR](coverage.json) seluruhnya **software PASS** dengan rujukan reuse dan bukti pengganti. F01 tidak dianggap hilang hanya karena developer mengubah CSS: ukuran, hit-test, trusted tap, navigasi, fokus dan visual di atas membuktikan penutupan independen. Tidak ada defect penghambat baru yang teramati dalam scope.

## Riwayat dan penutupan

Sesudah Chrome selesai, turn sempat macet dan diinterupsi coordinator. [Recovery state](recovery-state.json) membuktikan server yang tersisa adalah PID24014 milik A2 dengan router privat yang benar, tidak ada profil browser tertinggal, dan source/build/font masih beku. Kasus Chrome yang selesai tidak diulang. Probe PID sandbox awal mendapat EPERM; pemeriksaan PID spesifik yang diizinkan kemudian berhasil.

Edge pertama berhenti **sebelum login** karena oracle helper mengharapkan versi lengkap pada navigator UA yang direduksi. `edge-useragent-harness-failure.json/png` dipertahankan. Oracle dikoreksi menjadi product CDP lengkap+UA Edg major, kemudian smoke lulus. Tidak ada source produk diubah. Chrome A2 memakai kondisi13Nov; Edge dilakukan14Nov WITA: dashboard hari14 bernilai0 sementara data besar13Nov tetap dalam agregat bulanan. Sampel laporan dipatok eksplisit11Nov, sehingga pergantian tanggal tidak salah dianggap cacat.

Semua pekerjaan memakai SQLite/config/storage/cache/session/views/log privat dan `app->useStoragePath()` eksplisit sebelum bootstrap HTTP. Hanya empat baris fixture besar di **salinan privat** diganti positif untuk review; tidak membuat transaksi baru. MySQL, migrasi, seed/reset/restore, WA nyata dan perangkat fisik tidak dipakai. Server8193 dan profil/proses headless milik task ditutup; fixture serta binary Edge privat dipertahankan. [Resource release](resource-release.json). Commit tester hanya `docs/evidence/T023/A2/**`; coordinator menangani penerimaan, push, main dan CI.

Belum diuji/dilewati: ulang suite penuh/build/111layar/PDF/MySQL yang tidak terpengaruh; bukti sah digunakan ulang. Android fisik/keyboard/instalasi/4G/pilot≤60detik/printer/kamera QR tetap T025. Produksi VPS/HTTPS/FPM/cron/salinan luar VPS T024–T025 belum diizinkan. Risiko produksi/perangkat tetap memerlukan gerbang tersebut; PASS ini tidak mengklaimnya selesai.
