# T013 A1 — Penjualan barang dengan garansi opsional

Candidate implementasi di atas input T012 `2ce3ca3c46bcaab19c334240ff4fd3e72add85ac`, branch `codex/t013-sales`. Hash source final ada di `source-manifest.json`; commit final mengikat seluruh bukti. Worker tidak mengubah ledger kanonik, menerima fitur sendiri, push, merge atau deploy. F003 tetap menunggu QA independen/gabungan.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, tetap berlaku. Project Delivery/worker-runtime dan design-taste-frontend/app-screen digunakan. Sol high diminta; receipt identitas backend efektif tidak tersedia dan tidak diklaim.

## Perilaku final

- Rute admin `/penjualan`, `/penjualan/baru`, `/penjualan/{id}`: daftar, buat, detail dan hapus dengan dialog konfirmasi. Satu aksi Lihat berada di kolom paling kiri; hapus di detail. Tidak ada edit atau tombol cetak palsu (struk/PDF T017).
- Form memilih barang aktif, harga jual awal dari Product, jumlah/harga Rupiah bulat, baris tambah/hapus, total sementara aman, nama/HP opsional, garansi0 tanpa masa berlaku. Garansi>0 memerlukan HP yang dinormalisasi; tanggal akhir dari sold_on+hari, dibatasi tipe tanggal database.
- CreateSale memvalidasi input, membaca admin terbaru di transaksi, mengunci produk berurutan menurut ID, menolak duplikasi barang/nonaktif/stok kurang. Pengurangan stok, nomor JL, snapshot nama/harga/modal dan baris disimpan dalam satu transaksi dengan retry deadlock terbatas. Batas uint32, subtotal dan akumulasi PHP_INT_MAX diperiksa sebelum perkalian/penjumlahan.
- Nomor memakai bulan pembuatan WITA, bukan sold_on yang dapat dibackdate. Timestamp pembuatan dipasang eksplisit pada atribut model sebelum save karena Fillable Sale tidak mencakup created_at. Clock yang melewati batas bulan di tengah insert tetap menyimpan nomor dan timestamp dari saat yang sama.
- DeleteSale mengunci sale lalu produk menurut ID, mengembalikan stok termasuk produk nonaktif, menolak overflow dan menghapus sale/baris secara atomik. Panggilan kedua mendapat404 sehingga stok tidak dikembalikan lagi. Tidak mengubah sequence lama untuk memakai ulang nomor.
- Route, boot/mount/render dan setiap aksi memeriksa admin aktif terbaru. ID/state konfirmasi terkunci; ID aksi tervalidasi sebelum lookup, state baris divalidasi sebelum render. Semua perubahan terbatas scope T013; tidak ada model/migrasi/dependency/CSS-JS global berubah.

## Pemeriksaan aktual

| Bukti | Expected → actual |
| --- | --- |
| `artisan test --compact tests/Feature/Sales tests/Feature/Stock tests/Feature/Auth` | **53PASS604assertions**, tanpa skip/error; Sales17, Stock12, Auth24 |
| Clock-boundary sesudah review timestamp | **1PASS2assertions**; 23:59:59Nov30→00:00:01Dec1 WITA dalam event creating tetap JL-2611-001/created_atNov30 |
| Final targeted form/dialog/timestamp | **5PASS49assertions**, `final-focused.log`; tidak mengulang suite/MySQL yang tak terdampak |
| PHPStan scope Actions/Sales+Livewire/Sales | **0error**; tidak menambah ignore/baseline |
| Pint source/test/helper PHP sendiri | **PASS**, `final-pint.log` |
| Lint desain `--app` | **0FAIL1WARN**, Mono hanya nomor JL sesuai DESIGN; invocation non-app awal dipertahankan terpisah |
| MySQL nyata dua admin | **PASS**, detail di bawah; `mysql-inventory/init/worker-0/worker-1/proof.json` |
| Browser Chrome157 | **22observasiPASS**, core9halaman/probe **0FAIL7WARN** (`browser-results.json`) |
| Polish final | **10observasiPASS** nominalnormal/besar/list3lebar+lowerform, lalu **4PASS** summary normal/besar360/1280. Teks nilai utuh, documentWidth=viewport (`visual-final.json`, `summary-final.json`) |

Tes otomatis meliputi stok3→jual2→1→jual2ditolak, multi-baris rollback, duplikasi/ID palsu/nonaktif, qty/harga negatif/pecahan/overflow, subtotal dan akumulasi PHP_INT_MAX, exception write rollback stock/sale/sequence, nomor WITA/backdate/deletion, harga default/snapshot setelah restok/edit barang, warranty/HP, delete rollback overflow/restore sekali, preview malformed/besar, confirmation/locked ID, role yang baru demosi/nonaktif, route dan action authorization, empty state/escaping/no-edit.

## MySQL konkuren dan preservasi

Resource `db:mysql-test` diperoleh eksklusif sebelum akses apa pun. Inventory **20tabel**, engine InnoDB untuk tabel workflow, stock int unsigned, kolom wajib dan unique index sales.number sesuai. Baseline isi seluruh tabel disimpan privat sebelum mutasi dan dicocokkan lagi sebelum menambah fixture. Tidak menjalankan test MySQL lama, migrate/fresh/drop/truncate/reset, tidak mengakses repair_app.

Task menambah **2admin aktif berbeda +1barang stok1** dengan prefix unik privat. Dua proses PHP memakai **Action CreateSale yang sama dengan UI**, barrier start, akun berbeda. Test-only Product::updating memberi jeda250ms untuk memastikan kontensi; tidak menggantikan workflow atau SQL mutasi. Hasil **1success/1shortage**, stokakhir0, **1sale/1item**, orphan0, nomor baruunik, **sequence JL current-period+1**. Waktu lock produk pemenang9.04ms, pihak yang menunggu294.27ms; proses dimulai terpisah≈1.2ms. Tidak ada stok minus/penjualan parsial/counter yang terpakai oleh transaksi gagal.

Semua row baseline tetap identik, kecuali perubahan last_seq+1 pada row JL yang memang diotorisasi workflow. Delta tabel hanya users+2/products+1/sales+1/sale_items+1; tabel lain0 dan sequence rowcount0 karena counter sudah ada. Fixture/provenance privat tetap disimpan, tidak dibersihkan. Password/koneksi/row lama tidak dicetak; `.env` sumber dibaca hanya dalam memori helper. MySQL dilepas sebelum browser. Bukti race tidak diulang setelah perbaikan timestamp saja: lock, validasi stok, nomor dan mutasi terkait tidak berubah; uji batas waktu terarah membuktikan bagian yang berubah.

## Browser dan urutan fixture

Browser eksklusif port8190 memakai SQLite baru0600 dalam direktori0700, key/password sintetis privat, config cache mem-pin session/view/storage privat. Bundle **14file identik T012/T008**; tidak perlu build/npm. Semua nama/HP pada gambar adalah fixture sintetis.

Flow nyata: empty → pilih barang aktif dengan harga125000 → tambah/hapus baris → qty2, harga150000 tampil150.000 → garansi30 tanpaHP ditolak dan fokusHP → isi0812-3456 7890 → simpan. DB menyimpan qty2/price150000/cost100000, HP6281234567890, stok3→1, garansi13Nov→13Des2026. Detail menampilkan total300000/modal200000/laba100000. Cancel dialog360/1280 mengembalikan fokus; delete aktual menghapus sale/items dan mengembalikan stok3,5,5. Teknisi ketiga rute mendapat403. Tidak ada runtime JS exception. Bukti `create-database-proof.log` dan `delete-database-proof.log` mempertahankan dua keadaan tersebut.

Helper awal menunggu tidak adanya `dialog` setelah cancel; dialog Sales sudah hilang tetapi dialog navigasi existing tetap ada. Pemilihan `.sheet-dialog` saja masih cocok nav yang memakai class sama; selector final `.sales-page dialog.sheet-dialog` benar. Tiga timeout runner dan diagnosis (`browser-failure.json`, `dialog-debug.json`) dipertahankan; salah satu patch sempat ditolak sandbox sehingga helper masih versi lama. Tidak ada fix produk karena masalah itu. Continuation hanya menyelesaikan cancel/delete/lebar yang belum terbukti dan memakai ulang hasil create/360.

Review gambar menemukan wrap digit di tabel desktop, lalu pada summary nominal ekstrem. Dua polish CSS scoped: numeric/date/ID desktop nowrap; summary value tidak menyusut dan membungkus sebagai satu unit di bawah label bila perlu. Untuk capture akhir, Action nyata menambah sale002normal dan sale003nominalbesar yang sah (qty2147483648×4294967295=9223372034707292160), keduanya hanya SQLite privat. Mereka dipertahankan; ini sesudah sale001 yang telah dihapus pada flow uji. Gambar awal tetap bukti sebelum polish, gambar `*-final` dan terutama `*-summary-final` adalah hasil akhir. Tidak mengulangi MySQL, suite penuh, atau build untuk CSS.

**32screenshot dibuka:** 21awal lewat3contact sheet plus gambar utama, dan11gambar polish/lowerform dibuka sendiri. Daftar/form/detail360/768/1280, empty/error/warranty/filled, dialog360/1280, delete-success, teknisi403; mockup/tts dan tts-rincian360/1280 sebagai padanan. Layar akhir normal/besar dan lowerform membuktikan angka/harga/jumlah dapat dicapai di HP, tidak tertutup permanen sticky bar.

## Review visual — ROUND 2

Seen in one second: nomor/total penjualan pada detail; stok yang dijual dan Simpan penjualan pada form.
Idea (one sentence): lembar transaksi Anugerah dengan snapshot harga dan pengembalian stok yang jelas bagi owner.
Live thing: penjualan, validasi warranty, total, konfirmasi hapus dan stok nyata.
Layout words by section: list=ledger / form=baris→kartu / detail=rincian+ringkasan / confirmation=dialog.
Scores: design7.5 · usability8 · creativity7 · content8 → overall7.6.
Caps applied: aturan halaman marketing/foto/motion tidak berlaku; DESIGN aplikasi mengikat.
Top three observations/fixes:
1. Tabel detail/list1280 awal memecah Rupiah/tanggal/ID karena nama panjang; diperbaiki nowrap khusus desktop. Gambar final menunjukkan seluruh digit utuh dan nama tetap membungkus.
2. Summary nominal ekstrem1280 awal memecah digit terakhir; diperbaiki wrapping label/value sebagai unit. `detail-large-360/1280-summary-final` menampilkan Rp9.223.372.034.707.292.160 satu baris utuh tanpa overflow; normal300k tetap satu baris bersama label.
3. Form HP panjang karena tanggal/customer/HP/garansi; ukuran field48px dipertahankan, tombol utama tetap terlihat dan kontrol baris/total dapat dicapai dengan scroll (`form-lower-360`). Tidak mengecilkan target demi memadatkan layar.

Warning probe sisanya: edge-text label nav Beranda13px berasal dari shell existing; panel/hero void pada akun dengan hanya1sale merupakan sheet penuh yang ditetapkan DESIGN. Tidak menambah data/dekorasi untuk mengisi ruang. Framed sheet existing seed1526667476, IBM Plex Sans dan Mono khusus JL, aksi hitam/teal logo-fokus; tidak ada arah baru. Aksi baris tunggal Lihat sesuai aturan satu aksi langsung, seluruhnya di kiri. Hapus mempunyai konfirmasi keyboard native dan state server.

Resource browser dan MySQL sudah dilepas; server8190 berhenti exit0, listener tidak ada, owned profileChrome0. Runtime privat SQLite dan provenance MySQL dipertahankan. Reproduksi helper `runtime.py` membuat hanya fixture baru/serve; `mysql-run.py inventory/race` **memerlukan grant resource baru**, bukan perintah yang otomatis boleh diulang.

Belum diuji: QA independen/integrasi gabungan, cetak/PDF T017, Android fisik dan produksi/deploy. Race hapus serentak tidak dijalankan pada MySQL; unit/feature membuktikan restore sekali dan kode mengunci sale. Penerimaan F003 tetap pada gate coordinator/QA.
