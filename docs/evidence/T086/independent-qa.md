# T086 — QA independen

Verdict: **PASS**, tidak ada temuan tersisa untuk kontrak D-49.

Input A1: `docs/evidence/T086/A1/source-manifest.json`, base `0a441173228990701959eff4e1ae5dcbae541306`. SHA-256 berkas manifest beku: `cf55fb8b034de853649d2d87169cf07ba809ac1fd486ee62cc49c6702f7ce1ac`. Tester menghitung ulang seluruh **14 hash file**: cocok, tanpa mismatch. Pin ini mencakup source, tes fitur/migrasi dan reproducer MySQL; report/screenshot pendukung terpisah.

Ponytail **full** tetap berlaku, dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; project-delivery/aturan QA sudah dibaca dalam sesi ini. Tidak mengubah kode produk, ledger atau Git.

## Review source independen

- Membaca kontrak T086 dan PRD D-49; komponen/model kategori dan pengeluaran, concern tenant, route, seluruh view/nav terdampak, pembaca laporan, tes baru serta migrasi. Mencari seluruh caller `ExpenseCategory` dan pemakaian kategori. Cast enum telah diganti menjadi snapshot string di pembaca daftar/edit/dialog; Report existing sudah memakai raw category string sehingga tetap membaca nama lama.
- Empat bawaan berasal dari enum existing dan aktif secara implisit sebelum ada konfigurasi toko. Hanya statusnya dapat diubah; edit/hapus bawaan ditolak pada komponen dan model. Custom memakai pola Bank existing, pencarian dan validasi nama 50 karakter, duplikat per toko, serta perlindungan nama bawaan tanpa membedakan kapital.
- Route admin dan semua jalur mutasi memeriksa akun admin aktif (create melalui cancel); ID komponen terkunci. Global scope tenant, tenant pada unique rule, pencarian ID terscope, serta pemeriksaan tenant asli pada model mencegah akses/mutasi lintas toko.
- Form dan model pengeluaran mengecek daftar aktif terkini. Pengecualian edit diturunkan dari kategori asli database, bukan nilai kategori publik. Rename/hapus/nonaktif master tidak memperbarui transaksi sehingga snapshot lama tetap terbaca, sedangkan transaksi baru tidak dapat memilih nama lama yang sudah tidak aktif/tersedia.
- Migrasi memperluas ENUM menjadi string 50 dan membuat tabel konfigurasi tanpa backfill atau perubahan baris transaksi. Rollback mengecek snapshot nonbawaan sebelum menyempitkan kolom/menghapus tabel; penolakan menjaga data. Reproducer MySQL dibaca: hanya tiga tabel prefiks acak miliknya dimutasi, target dibatasi ke MySQL lokal `repair_app_test`, baseline schema/baris dibandingkan dan fixture dibersihkan. `mysql-proof.json` melaporkan empat baris fixture identik dan 20 tabel existing tidak berubah. Tester tidak menjalankan ulang MySQL.
- `git diff --check` PASS, exit 0.

## Tes tambahan yang dijalankan tester

Empat kasus yang melengkapi bukti worker dijalankan memakai PHP 8.3 dan PHPUnit konfigurasi proyek, SQLite `:memory:` dengan RefreshDatabase; tidak menyentuh database operator. Tes sementara di `/private/tmp/T086IndependentTest.php`:

1. Objek kategori toko lain yang sudah dipegang menolak save/delete setelah `tenant_id` dipalsukan menjadi toko aktif; baris asli tetap sama.
2. Pengeluaran existing dapat mempertahankan kategori aslinya yang nonaktif, tetapi menolak menggantinya dengan kategori nonaktif lain; nominal yang tersimpan tetap benar.
3. Klien tidak dapat mengganti `categoryId` yang terkunci; exception penguncian Livewire teramati.
4. Semua bawaan dan custom nonaktif menghasilkan daftar aktif kosong, tanpa fallback terselubung; menambah satu custom aktif hanya menampilkan kategori tersebut.

Perintah aktual: `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit -c phpunit.xml /private/tmp/T086IndependentTest.php --colors=never`.

Receipt: `{"tool":"phpunit","result":"passed","tests":4,"passed":4,"assertions":10,"duration_ms":441}` (exit 0).

## Review hasil browser dan bukti lainnya

Tester melihat `categories-desktop.jpg`, `categories-mobile.jpg` (375px) dan `history-preserved.jpg`. Susunan mengikuti halaman Bank existing: nav tersedia, status berupa teks, aksi di kiri dengan ikon/menu, tidak terlihat overflow HP. Riwayat menampilkan nama kategori lama dan Rp15.000 setelah master berubah. Screenshot full-page HP memuat posisi fixed navigasi; ini bukan klaim seluruh konten terlihat sekaligus dalam satu viewport.

Interaksi dilakukan **coordinator**, bukan tester: `browser.md` mencatat toggle bawaan bensin, membuat Listrik uji, mencatat pengeluaran Rp15.000, rename menjadi Utilitas uji lalu nonaktif, riwayat mempertahankan Listrik uji, pilihan baru hanya Konsumsi/Gaji/Lainnya, dan lebar dokumen HP 375px. Delete tidak dijalankan lewat browser; jalur delete custom dan perlindungan bawaan dibuktikan tes Livewire worker, sementara tes tester menambah jalur delete model asing.

Hasil worker yang direview: 66 tes/920 assertion, Pint dan PHPStan 0 error, serta MySQL fixture PASS (lihat `A1/HANDOFF.md`). Suite gabungan coordinator awal 275 kasus menghasilkan 273 PASS, satu concurrency SKIP, satu FAIL hanya literal tes tanggal T085. Tester membaca log kegagalan tersebut; perubahan assertion tanggal diperiksa dalam tambahan laporan T085, lalu coordinator melaporkan DatePickerTest 2 tes/21 assertion PASS. Log build final coordinator juga dibaca: PASS 438ms. Tidak mengulang suite/build pada source yang sama.

Tidak menjalankan MySQL ulang, browser sendiri, HP fisik, pembaca layar, push atau deploy. Risiko operasional: rollback penyempitan sengaja ditolak selama transaksi memakai snapshot kategori tambahan; pertahankan schema string sampai data tersebut ditangani dengan izin yang sesuai.
