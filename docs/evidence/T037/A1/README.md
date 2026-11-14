# T037 A1 — Selectbox pencarian

Status implementasi: **PASS lokal** pada snapshot `sha256:aa4cc7ca6e9f37da95c412858144484b20f3b33a93fb433dbc7f2fbe7be9751c`.

- Satu komponen `x-ui.search-select` dipakai oleh barang penjualan, kategori TTS, teknisi, dan kategori pengeluaran.
- Pilihan peran (2 opsi tetap) sengaja tetap memakai select native.
- Browser aktual membuktikan pencarian, hasil kosong, panah/Enter, Escape, klik ulang seluruh opsi, sinkronisasi harga barang, kategori TTS, kategori pengeluaran, dan teknisi.
- Screenshot 360/768/1280 menunjukkan popup tetap selebar field, dapat digulir, dan berada di atas action bar.
- Targeted: 20 tes, 196 assertion, PASS. Full CI lokal: 187 tes (186 PASS, 1 SKIP), 1960 assertion, Pint PASS, PHPStan 0 error. `npm run build` PASS.
- `.github/workflows/tests.yml` identik dengan HEAD. Tidak ada commit, push, merge, deployment, atau perubahan database demo/lokal utama.

Yang tidak diuji: sentuhan pada HP fisik dan pembaca layar fisik. QA independen ada di T038.
