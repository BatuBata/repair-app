# T040/A2 — QA independen datepicker

**Verdict: BLOCKED (environment browser).** Kandidat `sha256:aa571baa88e28a0840eaf209f41925ff85c024ac4a333b6d87737167cfc451dc`, base `89e1c5b739c9d7036b085ba8753ea1c0eae2d6d2`. Model diminta `gpt-6.1-terra`, model efektif `not_exposed_by_runtime`. Ponytail full telah dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`.

## Pemeriksaan aktual

- Python hashlib membaca 11 berkas dalam urutan manifest, memeriksa tiap hash dan menghitung SHA-256 dari baris `hash  path\n`: seluruh 11 cocok dan agregat tepat `aa571baa88e28a0840eaf209f41925ff85c024ac4a333b6d87737167cfc451dc`. HEAD cocok dengan base. `git diff --name-only 89e1c5b739c9d7036b085ba8753ea1c0eae2d6d2 -- .github/workflows` kosong.
- `/opt/homebrew/opt/php@8.3/bin/php artisan test tests/Feature/DatePickerTest.php tests/Feature/Sales/SalesTest.php tests/Feature/Stock/StockTest.php tests/Feature/Expenses/ExpensesTest.php tests/Feature/Reports/ReportsTest.php tests/Feature/Tts/TtsTest.php`: PASS, 62 tes dan 718 assertion. Full CI developer pada pin yang sama dilaporkan PASS di `docs/evidence/T039/A2/README.md`; tidak dijalankan ulang.
- Audit source: delapan kontrol memakai picker bersama (penjualan sold_on, stok entry_date, pengeluaran spent_on/from/to, laporan from/to, TTS picked_up_on); tidak ada raw input type=date pada view aplikasi. Source memiliki dialog/grid/row/gridcell, handler keyboard, pembatas dan fokus setelah render. Ini bukan bukti perilaku browser atau jumlah row runtime.
- Screenshot developer A2 `date-picker-360.png`, `date-picker-768.png`, `date-picker-1280.png` ditinjau: popup terlihat utuh pada ketiga capture form penjualan. Ukuran sentuh dan posisi interaktif independen belum diukur.
- Coba browser independen via `cua.createBrowserTab('iab', ...)`: IAB tidak tersedia dalam sesi worker. Chrome `cua.createBrowserTab` gagal `Cannot read properties of undefined (reading 'timeoutMs')`. Memilih Chrome dan tab kosong berhasil, namun `tab.goto` gagal dengan pesan yang sama. Tidak ada browser aktual A2 yang berhasil dibuka; ini kendala environment, bukan bukti cacat produk. Dokumentasi troubleshooting browser dibaca.

## Belum dibuktikan

Uji ulang dua FAIL A1 (fokus PageDown/PageUp dan 6 row/42 cell di browser), min/max/boundary, prev/next, klik luar, keyboard seluruh arah, Hari ini, Escape, hidden value/filter live, penyimpanan form dan fokus error, delapan caller runtime, ukuran target serta viewport independen masih NOT RUN. Screenshot developer dan tes source tidak menggantikannya; jangan terima kandidat sebagai PASS sampai browser independen tersedia. Tidak ada commit, push, merge, deploy, perubahan kode, ledger atau database non-sintetis oleh tester.
