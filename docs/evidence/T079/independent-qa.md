# T080 — QA independen detail menu

**PASS dalam scope D-45.** Tidak ditemukan cacat produk pada tiga halaman detail yang diubah. QA dilakukan sebagai tester terpisah, dengan Ponytail **full** yang dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, serta aturan QA project-delivery, AGENTS, kontrak T079/T080, PRD D-45 dan DESIGN.

Input/checked revision: `sha256:08d717292c9a3fbbc956de414cdefa32be657788480af5fb30a49324eedef478`, di atas base `0284f963b155d5862196d2d28eb1a6d315020c68`. Kelima hash file pada `source-manifest.json` dicocokkan dengan isi aktual sebelum dan sesudah pemeriksaan; seluruhnya sama. Diff produk hanya tiga view, stylesheet detail bersama, dan DESIGN; domain, query, route, TTS, schema serta dependency tidak diubah.

## Pemeriksaan yang benar-benar dijalankan

PHP 8.3.30, PHPUnit 12.5.43, konfigurasi proyek `phpunit.xml`: SQLite **`:memory:`**, bukan database operator atau MySQL. Skrip fixture sementara: `/private/tmp/T080DetailQaTest.php` (SHA-256 `e00b048fdbfac8a3a2b116d6124a9a7829f2dbf3b2a0be63d54592b555e28e2b`).

```sh
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --configuration phpunit.xml /private/tmp/T080DetailQaTest.php --testdox --colors=never
```

Hasil akhir **6 tests, 234 assertions PASS**, durasi PHPUnit 517 ms, log `/private/tmp/repair-t080-independent-tests.log`.

| Kasus | Expected dan actual | Hasil |
|---|---|---|
| Penjualan populated, dua item, harga master kemudian berubah | Snapshot nama/harga/modal tetap; subtotal Rp93.000 + Rp24.690, total Rp117.690, modal Rp55.690, laba Rp62.000; garansi sampai 19/12/2026 dan transfer bank tetap tampil. HTML nama/item/bank disaring; tiga tautan dokumen tepat; konfirmasi lalu Batal tidak mengubah sale/item/stok. | PASS |
| Penjualan lama tanpa pembayaran/nama/HP/garansi | Customer umum, nomor HP belum dicatat, pembayaran belum dicatat dan tanpa garansi tampil, `paid_at` tetap null. | PASS |
| Pelanggan berisi 21 TTS dan 21 penjualan | Masing-masing 20 baris halaman pertama. `page` hanya menggeser TTS; `salesPage` hanya menggeser penjualan. Halaman kedua memuat record lama tanpa relasi yang cocok lewat HP. Seluruh 40 aksi pada halaman pertama ada di sel paling kiri, memiliki label aksesibel dan target detail TTS/penjualan. Snapshot transaksi tidak berubah. | PASS |
| Pelanggan kosong/tanpa alamat, restok kosong | Kedua pesan riwayat kosong dan alamat belum diisi tampil. Menu ubah barang menuju `stock.edit` barang yang sama. Tidak ada `aria-describedby` yang menunjuk error yang belum ada. | PASS |
| Restok salah lalu valid, kemudian barang dinonaktifkan | Empat input salah menghasilkan error dan pasangan `aria-invalid`/`aria-describedby` yang valid. Gagal tidak mengubah stok/riwayat. Simpan +2 mengubah stok 10→12 dan modal 17.000→19.000, menampilkan notice dan riwayat; snapshot sale tetap. Nonaktif menolak save berikutnya tanpa perubahan stok/riwayat. Catatan HTML disaring. | PASS |
| Hak akses tiga detail | Admin toko lain mendapat 404 atas ketiga ID; teknisi mendapat 403. | PASS |

DOM (struktur HTML) dari halaman/state di atas juga diperiksa: tidak ada ID ganda atau target `aria-describedby` yang hilang. Nama, alamat, produk, keluhan dan catatan panjang berisi markup tampil sebagai teks, bukan HTML aktif.

Run awal skrip QA gagal karena asumsi fixture tester: konteks tenant harus dipasang ulang setelah request HTTP, pola URL TTS adalah `/tts` dan bukan `/servis`, serta snapshot pembanding harus dimuat ulang agar kolom nullable bawaan database ikut dibandingkan. Diperbaiki hanya pada skrip sementara; tidak ada perbaikan produk. Log awal dipertahankan di `/private/tmp/repair-t080-independent-initial.log` dan `/private/tmp/repair-t080-independent-fixture-followup.log`.

## Review tampilan independen

Tester membuka dan memeriksa screenshot CUA yang dibuat coordinator, tanpa menguasai browser bersama:

- `customer-1440.jpg`, `customer-768.jpg`, `customer-375.jpg`, `customer-bottom-375.jpg`.
- `sales-1440.jpg`, `sales-768.jpg`, `sales-bottom-768.jpg`, `sales-375.jpg`, `sales-bottom-375.jpg`.
- `stock-1440.jpg`, `stock-768.jpg`, `stock-bottom-768.jpg`, `stock-375.jpg`, `stock-bottom-375.jpg`.
- `customer-empty-long-375.jpg`, `customer-empty-long-bottom-375.jpg`, `stock-inactive-long-375.jpg`, `stock-inactive-long-bottom-375.jpg`.

**PASS:** informasi utama terbuka dan terkelompok; penjualan desktop memakai panel item/ringkasan berdampingan, restok desktop memakai form/riwayat berdampingan, tablet/HP menumpuk dengan urutan tetap. Menu hitam, ikon lihat di kiri, isi angka dan garansi terbaca. Header panjang membungkus penuh; nilai maksimum Rp4.294.967.295 muat di HP. Kedua riwayat kosong/tanpa alamat dan status nonaktif terbaca. Capture viewport atas/bawah pengganti sudah direview karena full-page awal menempatkan bilah tetap di tengah gambar; itu masalah capture, bukan cacat produk.

Pengukuran lebar dokumen sama dengan viewport, interaksi menu penjualan→hapus→Batal, fokus error restok, save fixture browser +2 dan buka/tutup kalender berasal dari observasi CUA coordinator; tester memverifikasi hasil render serta tindakan Livewire terpisah pada SQLite in-memory di atas. Tidak mengklaim tester mengoperasikan CUA sendiri.

Tidak mengulang suite penuh/build, matriks backend yang tidak berubah, atau render PDF; tautan dokumen dan kontrak source diperiksa. Tidak menyentuh database operator/MySQL, ledger, Git, push atau deploy. Perangkat Android/printer nyata serta penerimaan produksi tetap di luar scope T080.
