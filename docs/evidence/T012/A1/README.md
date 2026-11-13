# T012 A1 — Stok barang dan restok

Candidate implementasi untuk QA independen, base `1d831380cd89b601c67321298eff07f686d8f010`, input T008 diterima `3610253be1dedb1d9abad958059f52e33377f320`. Commit final mengikat SHA source di `source-manifest.json`. Ledger kanonik tidak diubah worker; tidak merge, push, atau deploy.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery + kontrak worker/runtime dan design-taste-frontend/app-screen dibaca. Sol high diminta; receipt identitas backend efektif tidak diekspos dan tidak diklaim.

## Hasil dan perilaku

- Rute admin `/stok`, `/stok/baru`, `/stok/{id}/edit`, `/stok/{id}/restok`. Tambah barang dengan nama, harga beli/jual Rupiah bulat, stok awal; ubah nama/harga; nonaktifkan/aktifkan via menu kiri. Stok awal hanya saat create, perubahan berikutnya memakai restok agar barang masuk tercatat. Edit form lama tidak menimpa stok yang telah berubah.
- Restok mengunci akun admin terbaru dan barang dalam satu transaksi, menambahkan stok, memperbarui harga beli, menyimpan jumlah/harga/tanggal/catatan/pembuat. Overflow dan barang nonaktif ditolak. Riwayat tidak mempunyai aksi ubah/hapus. Tidak membuat Expense; tidak menyentuh snapshot SaleItem.
- Otorisasi admin aktif pada route, boot, mount/render dan setiap aksi; akun demosi/nonaktif ditolak walau komponen telah terbuka. ID tervalidasi sebelum lookup barang dan ID form/restok terkunci dari client.
- Reuse Product/StockEntry/User, layout, field/money/row-actions dan bundle yang ada. Tidak ada migrasi, dependency, model atau CSS/JS global berubah.

## Bukti aktual

| Pemeriksaan | Expected → actual |
| --- | --- |
| Regresi Stock/Auth/Users/Settings/Profile/Layout | 69 PASS, 695 assertions; tanpa skip/error (`checks.json`) |
| Stock sesudah perbaikan inferensi tipe lookup | 12 PASS, 201 assertions; termasuk uang/stok negatif, pecahan/batas unsigned, rollback bila riwayat gagal, stock form lama, riwayat/snapshot lama, akses/ID palsu, account stale |
| PHPStan komponen Stock + Pint own source/test/helper | 0 error; PASS |
| Lint design source | 0 FAIL, 0 WARN; salinan Blade berekstensi HTML karena CLI tidak mengenali Blade (`design-lint.log`) |
| Chrome nyata 157.0.8103.52 | 19 observasi PASS, tanpa runtime JS exception (`browser-results.json`) |
| Format dan persistensi uang | Masuk150000 → tampilan150.000, save lalu edit ulang; pembuktian DB integer150000 (`money-edit-database-proof.log`, `edit-money-1280.png`, `inspector-results.json`) |
| Restok nyata | Stok4 +2 →6, harga beli175000, satu riwayatqty2/cost175000, Expense0. Edit harga kembali150000 menjaga stok6 dan riwayat175000 |
| Responsive dan interaksi | 360/768/1280 tidak side-scroll; body16/16/14px IBM Plex Sans; tombol menu44px di HP, kiri nama; ArrowDown mengubah fokus, Escape mengembalikan fokus; satu primary terlihat; input numeric; sticky primary tepat di atas nav |
| Akses teknisi | Empat URL Stock ditolak403 di browser nyata |
| Probe/judge skill pada halaman authenticated | 12 halaman (4 rute ×3lebar), 0 FAIL/12 WARN; lihat alasan di bawah (`inspector-results.json`) |

Kegagalan awal dipertahankan ringkas di `checks.json`: satu ekspektasi test salah setelah logout middleware (fixture diperbaiki), sembilan error static typing lookup ID tervalidasi (pakai pola existing whereKey/firstOrFail). Tidak ada retry/reset attempt; semua bagian A1 sebelum candidate handoff. Browser dilaksanakan sebelum tiga perubahan lookup ekuivalen untuk static typing; markup/style/JS tidak berubah sesudah screenshot. Uji Stock dan static diulang pada source final; suite69 tidak diulang karena tidak ada perubahan perilaku guard/layout.

24 screenshot dibuka lewat tiga contact sheet (`contact-360/768/1280.jpg`); beberapa gambar utama dibuka sendiri. Daftar/restok/form/menu pada360/768/1280; empty/error/focus/filled/success/history360; edit uang1280; teknisi403; mockup/tts dan tts-baru pada360/1280 sebagai padanan. Tidak ada mockup khusus stok. Gambar list/restok utama menangkap harga175000 sebelum supplemental edit150000; urutan ini disengaja dan tercatat dalam log browser.

## Review visual — ROUND 1

Seen in one second: jumlah barang jualan dan tombol Tambah barang; di form, jumlah masuk dan harga beli baru.
Idea (one sentence): lembar kerja Anugerah yang memudahkan owner mencatat barang dan barang masuk dari HP.
Live thing: simpan barang/restok nyata dengan riwayat, validasi, dan jumlah/harga terbaru.
Layout words by section: title=ringkas / list=tabel→daftar / form=satu kolom / history=ledger.
Scores: design7.5 · usability8 · creativity7 · content8 → overall7.6.
Caps applied: aturan halaman marketing/foto/motion tidak berlaku; DESIGN aplikasi yang disetujui mengikat.
Top three observations (most visible first), screenshot dan alasan dipertahankan:
1. `list-1280` menyisakan kanvas putih untuk hanya4fixture; inspector hero-void/panel-void menandai sheet min-height. Shell existing memang satu lembar penuh; tidak menambah data palsu/dekorasi untuk mengisi ruang.
2. `form-1280`/`edit-money-1280`: kolom form berhenti sebelum sisi kanan (unused-canvas), sesuai batas560px DESIGN agar isian mudah dibaca. Tidak memperlebar form atau menambah panel yang tidak dibutuhkan.
3. Label Beranda pada nav360 berjarak13px dari tepi (edge-text), komponen shell existing yang sama pada layar lain. Konten stok tetap16px; target nav dan tombol44px+. Tidak mengubah shell di luar scope.

Semua warning dijelaskan di atas. Nama panjang membungkus; angka/harga tidak terpotong; status selalu kata; menu tetap kiri; form mengikuti tts-baru dan list mengikuti tts pada mockup. Riwayat terlihat dan dapat dicapai dengan scroll di HP (`history-360`). Tidak menggambar arah baru: existing **Framed sheet**, seed1526667476, IBM Plex Sans, abu-biru/putih, aksi hitam, teal hanya logo/fokus. Data seluruhnya sintetis lokal, bukan data pelanggan.

## Reproduksi dan isolasi

`runtime.py init` membuat direktori0700 SQLite0600 baru, key/password sintetis privat, migration biasa tanpa fresh/drop/reset dan config cache privat. `runtime.py serve` memakai8190; `browser.mjs` menguji flow dan screenshot; `inspect-authenticated.mjs` memakai fungsi probe/layout/app/judge skill tanpa modifikasi pada sesi login nyata, karena CLI inspector tidak dapat login. `fixture.php proof` membaca angka final tanpa key/password. Hanya source/test/helper sendiri diperiksa Pint; bukti T008 lama tidak diubah. Bundle14file dibandingkan dengan hash T008 di manifest; tidak perlu npm/build ulang.

Browser eksklusif telah dilepas ke coordinator; dua Chrome ditutup, profile owned0, server berhenti exit0/listener8190 tidak ada (`resource-release.json`). Fixture privat dipertahankan untuk QA; root.env dan MySQL existing tidak diakses/diubah.

Belum diuji: race paralel MySQL/InnoDB nyata (row locking ditulis, SQLite hanya membuktikan transaksi/rollback dan dua form stale serial), keyboard/touch Android fisik, integrasi penjualan T013, QA independen/gabungan, remoteCI atau deploy. Risiko penerimaan tersisa pada pemeriksaan tersebut; worker tidak menerima fitur sendiri.
