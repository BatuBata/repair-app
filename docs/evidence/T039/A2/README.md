# T039/A2 — Perbaikan fokus dan struktur grid datepicker

Verdict implementer: **PASS** pada snapshot `sha256:aa571baa88e28a0840eaf209f41925ff85c024ac4a333b6d87737167cfc451dc`.

## Perubahan terkecil

- Setelah kalender merender bulan baru, fokus ditunda satu frame lalu diarahkan ke tanggal target. Reproduksi T040/A1 sekarang berpindah November → Desember dan tetap fokus pada gridcell 1 Desember.
- Empat puluh dua hari dikelompokkan menjadi enam `role="row"`; browser sekarang membaca struktur table → row → cell.
- Tidak ada perubahan kontrak nilai, caller, workflow, dependency, atau database.

## Pemeriksaan aktual

- Targeted suite: 62 tes, 718 assertions — PASS.
- `npm run build` — PASS; warning optional `fontaine`/font resolution tetap warning lama.
- Browser aktual: PageDown mempertahankan fokus pada 01/12/2026; enam row/42 cell terlihat di accessibility tree; klik luar menutup kalender.
- Screenshot dan pengukuran aktual 360/768/1280: popup di dalam viewport, target terkecil 44.28×44 px.
- Full `composer ci:check`: Pint PASS, PHPStan 0 error, PHPUnit 189 tes (188 PASS, 1 SKIP), 1.981 assertions.
- `git diff --check` PASS; `.github/workflows` tidak berubah.

Tidak ada commit, push, merge, atau deployment. Menunggu QA independen T040/A2.
