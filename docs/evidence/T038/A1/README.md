# T038 A1 — QA independen selectbox pencarian

Verdict: **PASS** untuk snapshot T037 `sha256:aa4cc7ca6e9f37da95c412858144484b20f3b33a93fb433dbc7f2fbe7be9751c`.

Ponytail full — loaded `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; alur tester loaded dari `/Users/muzib/.agents/skills/project-delivery/SKILL.md` dan `references/qa-and-release.md`.

## Hasil utama

- Delapan hash file implementasi/tes/PRD dan hash agregat cocok persis dengan `docs/evidence/T037/A1/source-manifest.json` sebelum dan sesudah QA.
- Audit seluruh Blade menemukan empat caller yang wajib memakai `x-ui.search-select`: barang penjualan, kategori TTS, teknisi, dan kategori pengeluaran. Satu-satunya `<select>` tersisa adalah peran dengan dua opsi tetap.
- Browser aktual: pencarian tidak peka kapital, filter satu opsi, Arrow/Enter, pemilihan mouse, Escape, buka ulang seluruh opsi, status `Tidak ada hasil`, dan sinkronisasi harga Livewire semuanya PASS.
- Regresi state dinamis PASS: setelah dua baris penjualan memilih barang berbeda lalu baris pertama dihapus, baris yang tersisa tetap menampilkan `Remote TV Universal · 12 unit`, harga `50.000`, dan total `Rp50.000`.
- Kategori TTS lama yang dinonaktifkan pada fixture sementara tampil sebagai `Laptop (nonaktif)`, tetap tersedia bersama kategori aktif, dan penyimpanan tanpa mengganti kategori kembali ke detail dengan kategori `Laptop`.
- Teknisi dinamis (`BUDI` → `Teknisi Budi`) dan empat kategori pengeluaran (`GAJ` → `Gaji`) PASS. Form pengguna tetap memakai native `SELECT` dengan tepat dua opsi `Teknisi`, `Admin` dan tidak memuat `.search-select`.
- ARIA aktual PASS: `role=combobox`, `aria-autocomplete=list`, `aria-controls`, `aria-expanded`, `aria-required`; popup memakai `role=listbox`, pilihan memakai `role=option`, dan hasil kosong memakai `role=status`. Tidak ada warning/error console selama alur QA.
- Tes terarah independen PASS: 43 tes, 412 assertion. Seluruh Blade berhasil dikompilasi. `git diff --check` PASS dan workflow GitHub Actions identik dengan HEAD.
- Bukti responsif T037 dipakai ulang setelah hash file dan dimensi diverifikasi; screenshot 360×900, 768×900, 1280×900 diperiksa visual dan popup tetap selebar field, terbaca, tidak keluar horizontal, serta berada di atas action bar.

Tidak dijalankan ulang: full suite/Pint/PHPStan/build karena source sama dan hasil developer full CI sudah tersedia; QA menjalankan subset terdampak sendiri. Tidak diuji: HP fisik, sentuhan nyata, atau pembaca layar fisik; risiko tersisa rendah pada sintesis event sentuh dan pengumuman screen reader.
