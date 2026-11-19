# T090 — pencarian dan pagination index

D-53, main lokal, tanpa push/deploy. Perubahan terkecil memakai pagination Laravel/Livewire existing: 20 baris, pencarian sesuai isi daftar, satu komponen pencarian dan satu tampilan navigasi. Tidak menambah dependency, skema atau data operator.

Cakupan: TTS, pelanggan, penjualan, stok, pengeluaran, pengguna, kategori servis, bank, kategori pengeluaran, transaksi laporan, toko platform. Filter, aksi kiri, otorisasi dan scope tenant dipertahankan. Pencarian/filter serta simpan/hapus yang dapat mengurangi hasil mengembalikan halaman awal. Daftar kosong tetap memberi jumlah 0 hasil. Urutan SQL diberi id sebagai pembeda stabil. Ringkasan platform seluruh toko dan ringkasan utama laporan seluruh periode; footer transaksi menjumlahkan semua hasil pencarian/filter sebelum pagination.

## Bukti aktual

- PHP8.3 `artisan test --compact`: 283 kasus, 282 PASS, 3100 assertions, 15918ms, 1 tes konkurensi MySQL guarded SKIP. Dilakukan setelah implementasi listing, sebelum penambahan resetPage setelah save.
- QA independen menguji perubahan resetPage dan seluruh perilaku baru pada snapshot final: `artisan test --compact tests/Feature/IndexPaginationTest.php`, 6 PASS, 126 assertions,1831ms. Tidak mengulang suite penuh karena tambahan resetPage sudah dicakup pengujian target.
- `artisan view:cache` PASS. `npm run build` PASS386ms; warning existing font fallback opsional dan file IBM Plex runtime tetap. Eksekusi langsung pint awal memakai PHP7.4 sehingga gagal prasyarat; diperbaiki memakai PHP8.3. PHP8.3 `vendor/bin/pint --dirty --test` final PASS; `git diff --check` PASS.
- Browser CUA pada fixture SQLite disposable `/private/tmp/repair-t090.sqlite`, akun sintetis. Seluruh10 Livewire index diuji halaman1→2, jumlah20/halaman dan sisa hasil benar, tanpa overflow viewport1280 dan375. Stok pencarian dari page2 menghasilkan tepat1 hasil dan kembali page1. Tombol pagination HP44×44, halamanaktif jelas dan bisa discroll di atas actionbar.
- Platform26toko diuji nativeGET: page1=20,page2=6; pencarian24toko menjadi20+4,querystring tetap saatnext,ringkasan26toko tidak berubah. Mobile375 nohorizontaloverflow.
- `browser-checks.json`, screenshot desktop/HP dan `independent-qa.md` menyimpan bukti. Pin source pada `source-manifest.json` sha256:f68e052bc637fc5448ac23bd7ec36ffadc511dab0c9a48e63d6429613ae9de3e; semua26file cocok.

Server fixture dan tab agent ditutup; viewport dipulihkan. Server operator8006 tidak dimatikan, database lokal/produksi tidak diubah. Transaksi laporan masih memakai koleksi yang dibangun layanan laporan existing; optimasi pengambilan agregat/data sangat besar di luar scope. Tidak menguji produksi, perangkat fisik atau konkurensi MySQL ulang. Tidak ada push/deploy.
