# T052 A1 — QA independen daftar bank dalam dialog pembayaran

Tanggal: 17 November 2026 (WITA)

## Kontrak dan pin

- Tester: worker independen `/root/t052_bank_dialog_qa`.
- Task QA: T052 untuk implementasi T051.
- Commit yang diperiksa: `e8ebf10e4056dec6fc09ff348c1f537e2f9b1f1b` (`main`).
- Bukti implementer yang dipin: `docs/evidence/T051/A1/README.md`.
- Revision T051 dari ledger: `sha256:3e20dd33780404d0a27ef63b6fc1b125b9799a7e57dffa3b3798447f1c4bcde5`.
- Hash berkas CSS saat QA: `sha256:c2226584ed2516d01d8c48287dd947d5a0ef4fa414205f97e4c0f2c22b6d3e8b` untuk `resources/css/layout.css`.
- Model yang diminta assignment: `gpt-6.1-terra`. Receipt model efektif tidak tersedia pada runtime worker, sehingga model efektif tidak diklaim.
- Ponytail **full** dimuat dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`.
- Kontrak QA project-delivery dibaca dari `/Users/muzib/.agents/skills/project-delivery/SKILL.md` beserta `references/qa-and-release.md` dan `references/coordination.md`.

## Audit perubahan

`git show` memastikan perubahan produk T051 hanya satu aturan CSS berikut, tanpa perubahan logika pembayaran atau data:

```css
.sheet-dialog .search-select-menu { position: static; margin-top: 4px; }
```

Aturan dasar `.search-select-menu` di luar dialog tetap `position: absolute`, `max-height: 240px`, dan `overflow-y: auto`.

## Pemeriksaan otomatis

| Pemeriksaan | Hasil |
|---|---|
| `/opt/homebrew/opt/php@8.3/bin/php artisan test tests/Feature/Invoice/InvoiceTest.php` | **PASS** — 23 tes, 134 assertion |
| `npm run build` | **PASS** — aset Vite berhasil dibuat; hanya warning font lokal opsional yang sudah ada |
| `/opt/homebrew/opt/php@8.3/bin/php artisan view:cache` | **PASS** |
| `git diff --check` | **PASS** |
| `git status --short` sebelum QA | Bersih |

## QA browser independen

Fixture lokal sintetis dipakai melalui SQLite sementara dan server `127.0.0.1:8095`; tidak memakai atau mengubah database produksi. Tombol simpan pembayaran dan simpan penjualan tidak ditekan.

### Dialog pembayaran — layar HP

- Browser dijalankan pada breakpoint HP; override yang diminta 390×844 dan viewport CSS yang dilaporkan browser 487×1055 karena skala tampilan browser.
- Dialog `Catat pembayaran` dibuka dari detail `TTS-DEMO-001`, lalu metode `Transfer bank` dipilih.
- Sembilan opsi tersedia: BCA, BNI, BRI, Bank Mandiri, BSI, Bank Kaltimtara, CIMB Niaga, PermataBank, dan Lainnya.
- Opsi pertama `BCA` dan opsi terakhir `Lainnya` masing-masing berhasil dipilih; nilai combobox terverifikasi menjadi `BCA` lalu `Lainnya`.
- Listbox berada di alur dialog: `position: static`, `clientHeight: 239`, `scrollHeight: 404`, `overflow-y: auto`. Ini membuktikan daftar panjang menggunakan scroll internal.
- Tombol `Kembali` dan `Simpan pembayaran` terlihat penuh di bawah daftar.
- `documentElement.scrollWidth === clientWidth` (487), sehingga tidak ada overflow horizontal.

### Dialog pembayaran — desktop

- Override desktop yang diminta 1440×900; viewport CSS yang dilaporkan browser 1800×1125 karena skala tampilan browser.
- Seluruh sembilan opsi tetap tersedia.
- Listbox: `position: static`, tinggi 240px, `clientHeight: 239`, `scrollHeight: 404`, dan `overflow-y: auto`.
- Kedua tombol dialog terlihat penuh di bawah listbox.
- `documentElement.scrollWidth === clientWidth` (1800), sehingga tidak ada overflow horizontal.

### Search-select di luar dialog

- Halaman `Penjualan baru` dibuka pada breakpoint HP.
- Search-select `Barang` tetap `position: absolute` (floating), bukan terkena aturan khusus `.sheet-dialog`.
- Opsi `Charger USB-C · 4 unit` tampil dan berhasil dipilih; nilai combobox terverifikasi sama.
- Tidak ada overflow horizontal.

## Putusan

**PASS** — perubahan satu aturan CSS T051 menyelesaikan daftar bank yang terpotong di dialog pada tampilan HP dan desktop, mempertahankan scroll internal serta tombol dialog, dan tidak meregresikan search-select di luar dialog.

## Batas dan risiko tersisa

- QA memakai Chrome pada fixture lokal sintetis, bukan perangkat HP fisik atau data produksi.
- Skala browser membuat viewport CSS terukur 1,25× dari nilai override, tetapi kedua pengujian tetap masuk masing-masing layout HP dan desktop serta pemeriksaan geometri dilakukan dari DOM aktual.
- Tidak dilakukan penyimpanan pembayaran/penjualan karena scope T052 hanya perilaku tampilan; alur simpan tercakup oleh Invoice feature test.
- Tidak ada perubahan kode aplikasi, PRD/TODO, commit, push, deploy, atau data produksi oleh tester.
