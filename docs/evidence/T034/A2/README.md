# T034 A2 — perbaikan placeholder angka

Target tetap working tree lokal di atas `89e1c5b`; tidak ada commit, push, merge, atau deployment.

Perbaikan dibatasi pada akar temuan T035/A1: kondisi render placeholder sekarang memeriksa `null` secara eksplisit, sehingga string `0` tidak dibuang. Tes komponen ditambah untuk field angka.

Hasil aktual:

- Reproducer independen `docs/evidence/T035/A1/render-probe.php`: PASS seluruh delapan kasus, termasuk default angka dan override string `0`.
- `ScaffoldTest`: PASS 5 tes, 30 assertion.
- Full `composer ci:check` dengan PHP 8.3: PASS — Pint, PHPStan 0 error, PHPUnit 185 tes (184 lulus, 1 skip), 1.945 assertion.
- Source diff `resources/views/**` dan `tests/Feature/ScaffoldTest.php`: `sha256:2d31e5b2b6091fa2d301907bbef59b3301cf3bdc347a15333a473f51a998aa07`.
- Workflow GitHub tetap byte-identik dengan HEAD dan status remote tetap aktif; tidak ada aksi remote pada A2.

Screenshot form pengguna T034/A1 tetap berlaku karena A2 hanya mengubah kondisi khusus placeholder bernilai `0`; empat placeholder pengguna dan layout tidak berubah.
