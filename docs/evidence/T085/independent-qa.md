# T085 — QA independen

Verdict: **PASS untuk review source dan hasil render T085**. Tidak ada temuan tersisa. Penerimaan gabungan beserta pemeriksaan integrasi akhir tetap dicatat coordinator.

- Checked revision akhir: `sha256:9fff0a4c325d26b99976578d029b2d9a55c1b3658e096882481d2c2e53f55380` (dua view dan tambahan tes tanggal).
- Input/base: `0a441173228990701959eff4e1ae5dcbae541306`; ketiga file sesuai `source-manifest.json`, diverifikasi SHA-256 secara independen. Pin review dua view sebelumnya `sha256:d2b44477dada28c124088165ac4fec404c4646c5baaa7e59a37a4f23328f9c1e` tetap tercakup; hash kedua view tidak berubah.
- Scope: `resources/views/livewire/sales/form.blade.php`, `resources/views/livewire/sales/styles.blade.php`, dan `tests/Feature/DatePickerTest.php`. Perubahan T086 tidak termasuk penilaian ini.
- Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Aturan QA project-delivery juga dibaca. DESIGN.md dan PRD D-48 menjadi acuan tampilan, dibantu panduan app-screen design-taste-frontend dan UI/UX Pro Max.

## Pemeriksaan yang dilakukan tester

1. Membaca kontrak T085, PRD D-48, DESIGN.md, diff kedua view, `Sales/Form.php`, komponen field/date-picker/search-select/payment-fields, detail-styles dan detail penjualan existing. Hasil: tetap form tambah dengan isian aktif, pelanggan/barang terbuka di area utama dan total/tanggal/garansi/pembayaran/simpan pada ringkasan samping. Tidak mengubahnya menjadi detail baca-saja.
2. Menelusuri seluruh binding yang dipindahkan: pemilihan pelanggan `.live`, tombol pelanggan baru dan kondisi identitas nonaktif; pilih/tambah/hapus barang; kuantitas dan harga; preview total; tanggal dengan nilai awal; garansi; metode pembayaran serta bank bersyarat; submit `save`, tujuan Batal, validasi per isian dan fokus error. Hasil: binding dan jalur existing dipertahankan, backend tidak berubah oleh T085. Wrapper ikon simpan berada di dalam span `wire:loading.remove`; aturan inline-flex tidak memaksa span loading tersembunyi menjadi tampil.
3. Meninjau cascade CSS desktop, band 1200–1399px, satu kolom di bawah 1200px, dan HP/tablet di bawah 1024px. Hasil: grid memakai kolom menyusut `minmax(0,1fr)`, checkout tidak mendapat margin sibling yang keliru, satu tombol simpan terlihat menurut breakpoint, tombol hapus HP selebar 44px. Aturan akhir HP sengaja membuat baris barang datar dengan pemisah atas; kekhawatiran awal garis bawah kartu hilang sudah terselesaikan karena kartu dalam memang dihapus seluruhnya.
4. Melihat screenshot coordinator secara independen: `form-desktop.jpg` (1440), `form-1280.jpg`, `form-mobile.jpg` (375), `items-mobile.jpg` (viewport 375), `bank-mobile.jpg`. Hasil: konsep detail terbuka konsisten; dua kolom desktop, satu kolom HP; tidak terlihat overflow halaman; ikon simpan sejajar; kontrol barang terbaca tanpa tertutup pada viewport yang digulir; semua opsi bank terlihat di atas bilah navigasi. Overlay pada full-page HP merupakan posisi elemen fixed saat capture; viewport item membuktikan akses konten setelah digulir.
5. Menjalankan `git diff --check -- resources/views/livewire/sales/form.blade.php resources/views/livewire/sales/styles.blade.php`: PASS, exit 0. Memeriksa SHA-256 kedua file dengan Python `hashlib`: keduanya cocok dengan manifest final.

## Bukti perilaku dari coordinator, bukan interaksi tester

`README.md` mencatat sesi CUA fixture SQLite terpisah pada port 8193: cari/pilih barang dan harga awal, tambah/hapus baris, pilih pelanggan lalu clear/unlock melalui Pelanggan baru, input nama, kuantitas 2 memperbarui total menjadi Rp1.300.000, submit kosong menampilkan error serta fokus barang, tanggal 18 November, QRIS dan transfer BCA, serta simpan berhasil ke `/penjualan/2` dengan stok 22→20. Tester menilai bukti screenshot dan kecocokannya dengan source; tidak mengklaim menjalankan sesi browser tersebut sendiri.

Developer melaporkan SalesTest + CustomerRelationsTest 25 tes/235 assertion dan build PASS sebelum perubahan presentasi terakhir (wrapper ikon/separator HP). Hasil itu tidak dilabeli ulang sebagai suite pada pin final. Suite yang sama tidak diulang oleh tester; pemeriksaan integrasi akhir coordinator dilaporkan terpisah.

Tidak mengubah source, ledger, Git, atau database; tidak build/deploy. Tidak menguji HP/printer fisik, pembaca layar, atau simulasi latensi loading. Risiko tersisa terbatas pada kondisi/perangkat yang tidak diperiksa tersebut; tidak ada cacat T085 yang teramati.

## Tambahan tes tanggal setelah integrasi

Review independen atas perubahan `tests/Feature/DatePickerTest.php` juga **PASS**. Pin tambahan file tes: `sha256:051dadfa5a6c59313009ef6097e926244d0b2a5bc42423e23558388c9c5280d3`; pin kedua view di atas tetap sama. Assertion lama mencari literal konfigurasi tanggal di dalam foreach yang sudah dipisah oleh T085. Assertion pengganti mencari deklarasi eksplisit `name="sold_on" label="Tanggal jual" type="date"`, sama dengan pola kontrol tanggal restock. Pemeriksaan `<x-ui.field>` yang meneruskan tipe date ke shared picker tetap ada, sehingga kontrak kalender tidak dilemahkan dan tidak memerlukan perubahan produk.

Coordinator melaporkan suite gabungan awal 275 kasus: 273 PASS, satu concurrency SKIP terjaga, satu FAIL hanya assertion literal tersebut. Setelah penyesuaian tes, DatePickerTest 2 tes/21 assertion PASS. Tester membaca diff dan source terkait; tidak menjalankan ulang suite itu.

Tester juga membaca log kegagalan awal `/private/tmp/repair-t085-t086-suite.log` dan log build final `/private/tmp/repair-t085-t086-final-build.log` (PASS, 438ms). Kegagalan lama tetap tercatat; build final serta hasil tes tanggal dari coordinator tidak diklaim sebagai eksekusi tester.
