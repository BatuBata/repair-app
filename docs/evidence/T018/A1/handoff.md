# T018 A1 — Dashboard admin

**Developer self-check PASS; belum QA akhir T023.** Workspace `/Users/muzib/.codex/worktrees/v1-stock/repair-app`, branch `codex/t018-dashboard`, base `6e6cb0967923e89ec61900fda069336b7c5bf5e4`. Input T016 **`c66d038ea448910e3cc8a8529f170f72ac064306`**. Ini logical A1 yang sama sesudah pause/resume, tanpa reset attempt. Commit hasil mengikat hash di `source-manifest.json`; SHA dikembalikan dalam result worker.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, design-taste app-screens, DESIGN dan **mockup/dashboard.html** digunakan. Sol high diminta; backend efektif tidak diekspos receipt. Worker ini implementer, bukan tester T023.

Root `/` kini component nyata bernama route `home`; `/dashboard` tetap redirect admin. Placeholder scaffold tidak lagi menimpa root, dan view/tes quicklink lama disesuaikan. Dashboard menampilkan TTS masuk hari ini WITA, hitungan status, tag nomor/umur servis, Diambil bulan ini beserta total status, backlog Selesai (5terlama +tautan semua), omzet harian, serta pengingat servis/penjualan H-3 sampai hari akhir. Garansi0/contacted/expired dan servis bukan Selesai/Diambil tidak masuk. Nama kategori riwayat dan nama barang snapshot penjualan tetap dibaca.

Pengingat memakai helper WhatsApp existing. Menu `···` kiri dipindah ke body; callback `Livewire.find(id)` tetap bekerja. Mark memvalidasi kind/id, mengunci actor/target, lalu membaca ulang clock WITA/kelayakan sebelum menyimpan timestamp pada instant yang sama. Rollover sesudah locked read dibuktikan FAIL dahulu lalu diperbaiki; duplikat, status/tanggal berubah, role dicabut/nonaktif ditolak. Tidak membuka/mengirim WA.

Panel bulanan sesuai DESIGN: tab Omzet/Laba kotor/Nota servis, grafik garis220px tanpa isian, rentang WITA, keyboard tab dan tooltip hover/ketuk/keyboard. Uang dijumlahkan per baris menggunakan **BigInteger**, termasuk modal snapshot untuk laba kotor; tidak memakai SQL SUM/float uang. Total dan tooltip string exact. Skala sumbu memakai pembagian decimal exact dengan unit eksplisit (misalnya Juta Rupiah atau Rupiah×10^18); hanya rasio koordinat SVG yang menjadi float. Nol dan laba negatif jujur. Data keuangan hanya view admin, public Livewire prop hanya `series`; guard fresh berjalan sebelum query keuangan.

Hook laporan memakai `Route::has('reports.index')` dan route tanpa parameter, sesuai kontrak coordinator/T019. Tidak ada tombol palsu saat route belum tersedia; hook harus diuji lagi pada integrasi T022.

## Pemeriksaan aktual

Semua PHPUnit memakai `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' RUN_MYSQL_DOMAIN=0` serta `/opt/homebrew/opt/php@8.3/bin/php`.

| Run terarah | Actual |
|---|---|
| Dashboard/Auth/RoleLauncher/Tracking sebelum chart | 27PASS241assertions (`targeted-final.log`), dipertahankan sebagai regresi awal |
| Dashboard setelah dataset chart ditambah | 11PASS93assertions (`chart-existing-tests.log`): daily/status/backlog, exact aggregate, WITA8/11/12Des, role/status/kind/id/contacted |
| Chart baru +daily yang kini memakai sumber bulanan | 5PASS106assertions (`chart-final-tests.log`): tanggal/status, nota dihitung sekali, nol/satu hari, aggregate/loss besar, seri/role |
| Unit skala final/nol/negatif | 3PASS92assertions (`axis-tests.log`), label decimal exact dan negatif |
| Guard sebelum query financial chart | 1PASS7assertions (`chart-auth-final.log`) |
| Locked-read midnight +sukses/duplikat dua jenis | 2PASS24assertions pada backend final (`contact-final.log`) |
| `vendor/bin/pint --test` pada source/routes/tests/helperPHP milik task | PASS; `pint-final.log` |
| `vendor/bin/phpstan analyse --memory-limit=512M --no-progress --debug` | PASS0error final; `phpstan-final.log` |

Run saling tumpang tindih, **tidak dijumlahkan sebagai kasus unik**. Aggregate contoh empat transaksi legal menghasilkan Rp36.893.488.138.829.168.640; browser dengan transaksi normal tambahan menunjukkan Rp36.893.488.138.830.518.640. Laba negatif browser tepat Rp-36.893.488.138.829.168.640. Harga/modal unit4294967295 ×qty2147483648 tetap legal per transaksi.

`visual-summary.json` memetakan bukti final: **18observasi axes/tooltip final** (besar3, negatif9, nol3, normal3) pada360/768/1280. Dokumen/inner/visual viewport sama requestedWidth; tick satu baris tidak bertabrakan, tanggal13utuh, tooltip opacity1 dan di dalam plot, tabHP≥44px, nominal full exact, snapshot publik tanpa keuangan. Negatif mencakup seluruh3seri dan ketukan CDP nyata padaHP. Keyboard tab ArrowRight/Home/End serta chart End terbukti pada run terkait. Menu WA hanya diperiksa link/template; mark servis/sale nyata, fokus kembali setelah request, stale error dan tech403 tanpa nominal ada di `browser-continuation-results.json`/`database-proof.json`.

Gambar final dibuka, termasuk tiga lebar via gambar/contact yang tersimpan dan perbandingan mockup persis. Shell/font IBM Plex, lane, panel antrean/garansi dan tab/grafik mengikuti acuan; KPI harian tambahan memenuhi AC. Menu kiri mengganti tombol satu aksi mockup karena baris kini mempunyai beberapa aksi. Source view final cocok dengan bukti akhir; perubahan setelah capture hanya memperkuat waktu mark, diverifikasi dua tes final. Bukti garansi/backend lain direuse sesuai kondisi, bukan replay penuh. Riwayat FAIL/harness/gambar antara tetap disimpan; gunakan **axis-final-** untuk axes/tooltip final.

## Preservasi dan batas

`source-manifest.json`: hanya scope Dashboard/routes scaffold/tes/evidence; tidak ada model, schema/migrasi, dependency, helper WhatsApp, shared CSS/JS yang berubah. Empat belas build lama tetap hash-identik; **tidak rebuild/install**, build gabungan menjadi T022. `.env`/AGENTS serta TODO worktree tidak diubah; ledger canonical hanya coordinator. Tidak push/merge main/deploy atau mengakses/reset MySQL.

Empat SQLite baru milik task, config/env0600 dan direktori0700, session/cache/views/storage privat. Chrome headless process/profile/CDPport0/TASTE terpisah, port8190; tanpa GUI/CUA/Orca. `resource-release.json`: listener8190false, Chrome/profil milik task0, semua fixture dipertahankan.

Tidak diuji: fullsuite/build gabungan, MySQL race, route laporan setelah integrasi, Android/printer fisik. Self-check ini tidak menerima F005 atau melepas QA independen T023. T022 harus merakit source/build terbaru sebelum QA akhir.
