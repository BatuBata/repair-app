# T051 A1 — Daftar bank tidak terpotong di dialog

Tanggal: 17 November 2026 (WITA)

- Akar masalah: daftar `search-select` memakai posisi absolut sehingga tidak menambah tinggi isi dialog dan terpotong oleh batas dialog.
- Perbaikan: khusus di dalam `.sheet-dialog`, daftar kembali ke alur dokumen (`position: static`) dengan jarak 4px. Komponen di luar dialog tetap memakai dropdown mengambang existing.
- Tidak mengubah data, validasi pembayaran, daftar bank, route, dependency, atau database.

Pemeriksaan:

- Invoice feature: **23 tes, 134 assertion — PASS**.
- `npm run build`, `artisan view:cache`, dan `git diff --check`: **PASS**.
- Browser sintetis 390×844 dan 1128×836: semua 9 bank tersedia; daftar mempunyai scroll internal, opsi terakhir **Lainnya** dapat dipilih, tombol dialog tetap terlihat, dan halaman tidak overflow horizontal.

Batas: belum ada QA tester independen dan belum push/deploy.
