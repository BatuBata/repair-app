# T048/A1 — QA independen responsif laporan dan servis

## Verdict

**PASS** pada kandidat lokal T047. Tester memakai Ponytail full dan tidak mengubah kode aplikasi, `TODO.md`, Git, atau deployment.

## Pin kandidat

- Kandidat gabungan: `sha256:4422b4f35ceb521f09e81cbc9289993645aa25f9f41d6696c073d659f3234fd2`
- `resources/css/layout.css`: `sha256:be94e6ce22bf0f45ffc48c515948c61609792bde7e0b5637db482e6c8d0fdabd`
- `resources/views/livewire/reports/styles.blade.php`: `sha256:d46abb724d4ae56caf7259213538a4318e79c4ae0899b200e2f37afd7a7425f8`
- Input T047: `sha256:4422b4f35ceb521f09e81cbc9289993645aa25f9f41d6696c073d659f3234fd2`

## Audit perubahan

- Wrapper langsung `.report-dates > div` kini menjadi flex item dengan `min-width: 0`; `.field` mengisi lebar wrapper. Ini memperbaiki penyusutan field tanggal tanpa mengubah komponen datepicker.
- `td[colspan]` pada tabel mobile melintasi seluruh grid. Audit seluruh caller menemukan pola yang sama pada empty state kategori, restok, pengeluaran, penjualan, servis, laporan, stok, dan pengguna; aturan bersama sesuai untuk semua empty state tersebut.
- Tidak ada perubahan data, query, route, otorisasi, dependency, atau konfigurasi deployment.

## Pemeriksaan aktual

Environment browser: server Laravel lokal `127.0.0.1:8199`, SQLite sintetis `/tmp/repair-search-select.sqlite`, akun uji sintetis.

| Pemeriksaan | Hasil aktual |
|---|---|
| Hash kandidat | Cocok dengan pin brief |
| Browser laporan 360px | viewport/document 360/360; root 328px; dua wrapper dan trigger tanggal masing-masing 158px |
| Browser laporan 768px | viewport/document 768/768; root 720px; dua wrapper dan trigger tanggal masing-masing 354px |
| Browser laporan 1280px | viewport/document 1280/1280; root 978px; wrapper dan trigger desktop masing-masing 162px |
| Browser servis 360px | viewport/document 360/360; empty cell dan empty state 328px; `grid-column: 1 / -1` |
| Browser servis 768px | viewport/document 768/768; empty cell dan empty state 720px; `grid-column: 1 / -1` |
| Browser servis 1280px | viewport/document 1280/1280; empty cell dan empty state 978px |
| Console browser | Tidak ada error pada enam halaman/viewport |
| `ReportsTest` + `TtsTest` | PASS — 25 tes, 258 assertion |
| `php artisan view:cache` | PASS |
| `npm run build` | PASS — 25 modul; warning font lokal tetap diselesaikan saat runtime |
| `git diff --check` | PASS |

Screenshot hasil nyata:

- `reports-360.png`, `reports-768.png`, `reports-1280.png`
- `tts-360.png`, `tts-768.png`, `tts-1280.png`

Pemeriksaan visual memastikan label dan nilai tanggal terbaca, empty state tidak lagi membungkus satu huruf per baris, dan tidak ada overflow horizontal dokumen.

## Batas dan risiko

- Full regression suite tidak dijalankan karena perubahan hanya dua aturan CSS dan tes fitur dua layar terkait telah lulus.
- Belum diuji pada perangkat HP fisik dan belum diterapkan ke server; bukti ini hanya mencakup browser lokal 360/768/1280 pada kandidat yang dipin.
