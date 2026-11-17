# S011/A1 — QA independen produksi PASS

Target HTTPS produksi `https://demo-repair-app.scannara.asia` diperiksa 17 November 2026 05:00–05:11 UTC pada **99f64bf59ff1489825333aa821015a281db611ea**. Versi diamati lewat SSH; marker deployment dan maintenance Laravel tidak ada. Tidak ada tenant kedua dibuat. Tidak ada source, ledger, deployment, database bisnis, akun, stok atau pengaturan yang diubah oleh pemeriksa.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, QA/release dan kontrak worker, AGENTS aktual, kontrak S011, rancangan SaaS §10–§13 dan receipt S010/A2 dibaca. Model diminta `gpt-6.1-sol`, effort high; model efektif tidak diklaim karena receipt runtime tidak tersedia. Scope tulis hanya folder bukti ini. S010/A2 dan manifest backup dipin pada `result.json`.

## Hasil yang diamati sendiri

| Pemeriksaan | Hasil aktual |
| --- | --- |
| Versi dan health | Exact99f64bf; MariaDB10.11.14; migrasi tenant batch2 Ran; `/up`, `/login`, 3 aset build HTTPS200 |
| Tenant dan relasi | Tepat1 tenant aktif id1, nama sama dengan store existing;8 root wajib/FK/backfill tenant1;7 model scoped sama jumlah;19 FK tanpa orphan atau tenant silang |
| Baseline sebelum browser | Seluruh18 tabel nonmigration/nonsession identik seluruh field lama; sessions dikecualikan simetris |
| Baseline sesudah browser | Seluruh17 tabel proyeksi cocok. Cache/sessions framework dikecualikan simetris. Pada users hanya1 remember_token berotasi akibat logout; seluruh field lain termasuk password, role, status dan timestamp identik |
| Backup, environment, key, upload | Manifest12 berkas diverifikasi independen di VPS dan salinan Mac; digest sama receipt S010; environment/key/upload identik. Retensi minimum1 Desember2026 |
| Browser Chrome nyata | Login admin; dashboard; navigasi toko/pengguna/kategori; TTS13 record/detail existing; stok1barang; penjualan1record/detail; pengeluaran kosong sesuai baseline; laporan; logout dan login generik PASS |
| Dokumen | TTS HTML struk+PDF thermal+A5 dan penjualan HTML struk+PDF thermal+A5 semua200; signaturePDF, ukuran/hash dicatat. HTML memuat toko dan nomor existing yang sama |
| Tracking publik | Dua record existing: belum nota dan sudah nota, browser guest tanpa autentikasi; identitas toko/nomor cocok. Tracking noindex/no-store. PDF publik record bernota200,23.866byte, signaturePDF valid |
| Laporan | Periode1–17 November WITA: omzet1.195.001, modal830.000, laba365.001, pengeluaran0. Hari17: omzet300.000, modal100.000, laba200.000. Tujuh angka utama UI cocok SQL independen dan service laporan; hash report baseline S010 tetap identik |
| Error | Browser console0/page0/request-failed0/HTTP4xx–5xx0; direktori log Laravel tidak memiliki file. Log Nginx tidak readable oleh muzib; tidak memakai sudo |

Inventaris bisnis tetap: users5, kategori10, TTS13, item servis7, produk1, penjualan1, item jual1, pengeluaran0, stock_entries0, counter2, store_settings1. Tidak ada transaksi sintetis ditambahkan. Logo existing memang kosong pada baseline; keadaan kosong itu dipertahankan pada pengaturan, bukan mengklaim logo unggahan diuji.

## Cara dan batas pembuktian

`verify.php` dikirim ke PHP melalui stdin SSH strict existing, bukan dipasang di server. Guard menolak SQL di luar SELECT/SHOW; `activeAccount` existing memakai SELECT FOR UPDATE dalam autocommit dan tidak mengubah baris. Query tenant/FK, hash field lama, report dan SQL agregat dijalankan independen. Sesudah login/logout, token asli dibaca dari dump privat **hanya dalam memori server** dan dipakai untuk normalisasi hash users; tidak disalin keluar server, direset atau dicetak. Digest proyeksi sebelum/sesudah berbeda bentuk karena pengecualian cache; keduanya dibandingkan ke baseline dengan bentuk yang sama.

`browser.mjs` dan `guest-invoice.mjs` menjalankan Google Chrome melalui Playwright. Kredensial diterima hanya lewat environment proses. Record dan token dipakai dalam memori browser; tidak ada trace, HAR, storageState, screenshot customer, PDF mentah atau token yang disimpan. Redirect WhatsApp dibaca tanpa mengikuti situs eksternal; tidak ada pesan dikirim. Filter laporan hanya mengubah state tampilan, bukan data bisnis. Browser/context ditutup dan folder temporer kosong dibersihkan.

Dua hasil awal browser dipertahankan: selector tautan pengguna mengasumsikan URL relatif, sedangkan aplikasi memakai URL absolut; selector penjualan mengasumsikan nomor di tautan, sedangkan nomor berada di kolom terpisah. Setelah selector mengikuti template aktual, kasus tersisa lulus. Tidak ada perubahan aplikasi. `data-after-initial.log` mempertahankan deteksi cache/users berubah sebelum normalisasi framework. Harness pertama terlalu membatasi SELECT FOR UPDATE dengan transaksi READ ONLY dan dihentikan; guard SELECT/SHOW menggantikannya. Pembaca dump awal mengasumsikan INSERT satu baris; format aktual multi baris dibuktikan dan diakomodasi. Tidak ada percobaan DDL/migrasi/transaksi bisnis.

Automatic approval review menolak ekspor fixture nama customer/token ke berkas lokal karena pemindahan payload sensitif mentah belum diotorisasi. Langkah itu tidak dijalankan dan tidak diulang. Jalur yang dipakai akhirnya adalah browser langsung serta normalisasi in-memory server yang disetujui coordinator; bukti hanya boolean/count/hash.

Scan membandingkan bukti dengan60 nilai privat existing (termasuk kredensial proses), pola token/telepon/private key/hash password; hasil akhir0 temuan dan0 media mentah. Hasil scan awal dipertahankan: nama operator yang sudah publik di path SSH dan kata peran aplikasi yang generik menghasilkan kecocokan palsu; keduanya dikecualikan secara eksplisit.

Batas: log Nginx server tidak diperiksa karena hak baca akun terbatas; Laravel dan sinyal error browser sudah diperiksa. Tidak menguji printer/Android fisik, transaksi produksi baru, login teknisi (tanpa kredensial), pilot tenant kedua, atau jadwal/monitoring backup harian. PASS ini adalah kontrak S011 dengan batas observabilitas log tersebut; tidak menerima S012 atau perangkat fisik.
