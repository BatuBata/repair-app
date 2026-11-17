# S014/A2 — Perbaikan aksi responsif

Ponytail full diterapkan pada temuan S020/A1. Akar masalahnya adalah aturan tabel generik `max-width:72px` yang tetap membatasi tombol platform meski lebarnya sudah 96px.

Perubahan minimum:

- tombol aksi platform pada viewport di bawah 1024px menghapus batas generik dengan `max-width:none`;
- label `Nonaktifkan` tetap satu baris di dalam tombol 96px;
- tidak ada perubahan route, autentikasi, data, dependency, atau fitur.

Pemeriksaan developer:

- focused platform + identity: 18 tes, 150 assertion PASS;
- frontend build PASS;
- `git diff --check` PASS.

QA visual independen pada candidate dan pascadeploy tetap diperlukan. Nama tenant produksi `Anugrah Komputer` dipertahankan; QA sebelumnya memakai ekspektasi label `Anugerah` yang tidak sesuai data existing.
