# T039/A1 — Datepicker bersama

Verdict implementer: **PASS** pada snapshot `sha256:786a43d792389a26cf259ab561b0b9297f7f29d6a7a1b7ef252430c564db39a3`.

## Cakupan

- Delapan kontrol tanggal lintas penjualan, stok, pengeluaran, laporan, dan pengambilan TTS memakai komponen bersama; audit tidak menemukan raw `<input type="date">` pada view aplikasi.
- Trigger menampilkan `dd/mm/yyyy`; input tersembunyi mempertahankan `YYYY-MM-DD` untuk Livewire/backend.
- Kalender menyediakan bulan sebelumnya/berikutnya, Hari ini, min/max, klik luar, Escape, panah keyboard, Page Up/Down, dialog/grid ARIA, dan fokus kembali ke trigger.
- Posisi dihitung ketika popup dibuka. Ini memperbaiki temuan browser awal yang menempatkan popup di luar viewport karena posisi dihitung saat render awal.

## Pemeriksaan aktual

- Targeted feature suite: 71 tes, 711 assertions — PASS.
- `npm run build` — PASS; hanya warning optional `fontaine`/font resolution yang sudah ada.
- `composer ci:check` dengan PHP 8.3 — Pint PASS, PHPStan 0 error, PHPUnit 189 tes (188 PASS, 1 SKIP), 1.979 assertions.
- `git diff --check` — PASS; `.github/workflows` tidak berubah.
- Browser lokal dengan fixture sintetis: popup sepenuhnya di viewport pada 360/768/1280, 42 cell, target terkecil 44.28×44 px.
- Browser aktual: panah kanan + Enter mengubah 15 menjadi 16 November; tombol bulan berikutnya menampilkan Desember 2026; Escape menutup dan mengembalikan fokus; Hari ini pada filter live menghasilkan display `15/11/2026` dan nilai `2026-11-15`.

Tidak ada commit, push, merge, atau deployment. GitHub Actions tetap aktif dan tidak diubah.
