# T049 A1 — Pembayaran sederhana

Tanggal: 17 November 2026 (WITA)

## Cakupan

- Pembayaran penuh satu kali untuk servis dan penjualan: Tunai, QRIS, atau Transfer bank.
- Transfer wajib memilih bank dari daftar tetap.
- Pembayaran servis dapat dicatat setelah nota terbit atau bersamaan dengan pengambilan.
- Laporan memakai filter metode pembayaran dan bank pada daftar transaksi; ringkasan periode tidak berubah.
- Data lama tetap `null` dan ditampilkan sebagai **Belum dicatat**; tidak ada backfill.
- Tidak menambah DP/cicilan, refund, gateway, tabel pembayaran, atau laporan baru.

Hash gabungan 21 file implementasi/tes: `sha256:1e8464a094ec3d8a3b4db6f37e240cdd02229b7df1a009ccc19ccfcbe1fa0ccc`.

## Pemeriksaan yang dijalankan

- Tes terarah Invoice, Sales, Reports: **PASS — 50 tes, 430 assertion**.
- Seluruh suite: **PASS — 230 lulus, 1 existing skipped, 2.458 assertion**.
- PHPStan dengan batas memori 1 GB: **PASS — 0 error**.
- Pint `--test`: **PASS**.
- `artisan view:cache`: **PASS**.
- `npm ci`: **PASS**, audit 0 kerentanan.
- `npm run build`: **PASS**; hanya peringatan font optional/existing.
- Browser dengan SQLite sintetis terpisah pada 390, 768, dan 1440 px: **PASS**, `scrollWidth === innerWidth`.
  - Form penjualan menampilkan tiga metode dan bank hanya untuk Transfer.
  - Detail servis belum dibayar menampilkan metode pada alur **Bayar & Diambil**; Transfer menampilkan bank.
  - Filter laporan Transfer menyusutkan 2 transaksi menjadi 1 dan menampilkan filter bank; ringkasan periode tetap sama.

## Batas

- Belum ada QA oleh tester independen; T050 tetap pending.
- Tidak menguji MariaDB produksi, tidak menjalankan migrasi server, tidak push, dan tidak deploy.
- Opsi bank **Lainnya** disimpan sebagai label tetap; nama bank bebas tidak ditambahkan agar scope tetap sederhana.
