# T047/A1 — Perbaikan responsif laporan dan empty state servis

## Diagnosis

- Laporan: wrapper field tanggal tidak ikut menjadi flex item; `.field` di dalamnya diberi `width: 0`, sehingga label dan tombol tanggal terkompres.
- Daftar servis kosong: satu-satunya `td[colspan]` tetap menempati kolom aksi 44px pada grid mobile.

## Perubahan

- Wrapper tanggal mobile menjadi dua flex item `min-width: 0`; field dan tombol memakai lebar penuh wrapper.
- Sel tabel mobile yang memakai `colspan` membentang dari kolom pertama sampai terakhir.

Tidak ada perubahan data, query, route, otorisasi, dependency, atau workflow deployment.

## Pemeriksaan

- Browser sintetis pada 360/768/1280: tidak ada horizontal overflow dan tidak ada console error.
- Lebar field tanggal: 158/354/162px; trigger sama persis dengan wrapper pada ketiga viewport.
- Empty state servis: 328px pada viewport 360 dan 720px pada viewport 768; `grid-column: 1 / -1`.
- `ReportsTest` + `TtsTest`: PASS, 25 tes / 258 assertion.
- Blade view cache, build frontend, Pint, dan `git diff --check`: PASS.

Revisi tetap lokal; belum ada commit, push, merge, atau deployment.
