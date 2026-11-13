# T022 A2 — kandidat final setelah perbaikan target sentuh

**PASS kontrak integrasi; menunggu QA independen T023 A2.** Sumber final `a6e570f000846763e5f294bffe5d1193e6615cbc`, branch `codex/t022-release`. Commit bukti sesudahnya hanya mengubah `docs/evidence/T022/A2`; 230 sumber, 14 build dan 9 font dibekukan lewat manifest dan `checked-source.json`. Worker tidak mengubah TODO/AGENTS, tidak push/merge main/deploy.

Ponytail **full** tetap aktif; skill dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md` dan project-delivery dari `/Users/muzib/.agents/skills/project-delivery/SKILL.md`. Ini sesi Sol integrator yang sama, A2/percobaan implementasi terakhir default. Model diminta Sol high; backend efektif tidak diekspos sehingga tetap unknown.

## Pin dan perubahan

Input pada `input-source-equality.json`: T017 `2d7e613d5592ff169cb7743ff6fd086ec06fe58c`; T018 `c9d1f429056676576310e88e524a75c9bc897c83`; T019 dan revalidasi T021 `069725bc434fc2ab42a486773863d9c362e5e20d`; T020 `6095a5ca34a5b28bf65a481007d69b21a8bc7084`. Seluruh file input yang diperiksa cocok; runbook tetap memakai rekonsiliasi/instalasi pertama A1 yang sudah direview. Guide identik; revalidasi coordinator ada di `docs/evidence/coordinator/t021-revalidation-after-t019-a2.json`.

Terhadap kandidat A1 `a95a3058`, hanya 2 dari 230 sumber berubah (`source-delta.json`):

- Perbaikan T019 yang sudah diterima: anchor nomor laporan menjadi inline-block minimal 44×44px hanya di bawah 1024px; desktop tetap inline.
- Perbaikan integrasi yang diotorisasi coordinator setelah audit: satu `@source not '../../docs/evidence';` di `resources/css/app.css`. Tailwind tetap memindai sumber lainnya dan seluruh sumber view/vendor eksplisit. Tidak ada perubahan PHP, route, markup tampilan, uang, dependency, lockfile atau database.

## Stabilitas build dan alasan pengecualian

Build A2 awal menambahkan utilitas `.lowercase` karena teks bukti A1 yang ditulis setelah build A1. Compiler Tailwind terpasang 4.3.5 membuktikan pemindaian awal membaca 602 file bukti dari 1351 file; token tepat ditemukan di `T022/A1/browser-public.mjs` posisi 4822 dan result JSON. Probe tidak mengubah aplikasi (`scan-provenance-before.json`, `scan-probe.mjs`).

Pengecualian didukung compiler terpasang, mengeluarkan seluruh 602 file bukti dan mempertahankan semua sumber view/vendor eksplisit. Perbandingan CSS produksi sebelum/sesudah (`final-built-css.diff`) hanya menghapus 2 aturan yang tidak dipakai runtime: `[cursor:pointer]` dan `lowercase`, total 72 byte. Tidak ada aturan yang dipertahankan berubah. Tidak ada token kedua kelas itu di app/resources. Bundle final memakai `app-CDn1G0_W.css`; 14 file dibekukan pada `build-manifest.json`.

Self-check nyata `scan-stability.mjs` menambahkan file sentinel milik task di docs/evidence berisi `w-[123457px]`: hash kandidat dan CSS compiler tetap identik sebelum, saat file ada, dan setelah file sentinel dihapus. Kontrol tanpa pengecualian mendeteksi token tersebut. `scan-stability.json` membuktikan commit bukti/QA berikutnya di subtree itu tidak mengubah hasil pemindaian; tidak perlu build produksi kedua untuk uji sentinel. Pengecualian hanya meliputi docs/evidence; perilaku pemindaian path lain tetap berlaku.

## Pemeriksaan yang benar-benar dijalankan

| Pemeriksaan | Hasil |
|---|---|
| Suite penuh PHP8.3, SQLite memory, DB_URL kosong, RUN_MYSQL_DOMAIN=0, cache/session array | PASS 181/182, 1913 assertion; 1 skip MySQL guard (`full-suite.log`, `checks.json`). Dijalankan setelah fix 44px dan sebelum pengecualian scan; pengecualian CSS tidak mengubah source PHP/test/config DB, sehingga suite tidak diulang. |
| Build produksi | Build awal PASS; kemudian satu build final PASS setelah source-exclusion diotorisasi. Kedua hasil dipertahankan, tidak ada retry tanpa perubahan sumber. |
| Cache privat | config:cache/route:cache/view:cache PASS; APP_ENV production, SQLite/config/cache/session/view/log/storage privat terbukti di `private-runtime-proof.json`. |
| Laporan pada bundle final | 11 kasus PASS (`browser-results.json`): dashboard→laporan, layout 360/768/1280, 4 sentuhan terpercaya di bawah teks anchor TTS/penjualan pada 360/768 yang benar-benar berpindah route, serta 3 dialog mata/fokus. |
| Ukuran dan layout | Kedua link 96.14×44 dan 108.42×44 pada 360/768; desktop tetap inline (tinggi 18px). Lebar dokumen sama dengan viewport; aksi mata tetap di kiri. Omzet Rp1.350.000 dan net Rp650.000 sesuai fixture. |
| Aset/font final | 23 HTTP200 dengan SHA256 persis disk (`asset-http-check.json`); 14 build/9 font, font seluruhnya sama dengan A1. |
| Whitespace | `git diff --check` PASS; spasi akhir/baris kosong akhir log dinormalisasi, isi hasil tetap sama. |

A2 awal/pre-exclusion juga PASS 11 kasus dan 23 aset, disimpan di `pre-exclusion-smoke/`; pemeriksaan dibatasi pada laporan dan diulang karena bundle/configscan berubah. Tidak mengulang26 alur bisnis/5 PDF/MySQL/Edge. Suite menggunakan satu invocation baru; tes MySqlConcurrencyTest tetap dijaga0 karena membutuhkan database kosong dan tidak boleh diaktifkan atas fixture MySQL existing.

Pint/PHPStan, 26 smoke bisnis/5 PDF/3 render A1 dan31 safety case + 3 syntax T020 dipakai ulang dengan provenance pada `reused-proof.json`: seluruh PHP/route/markup/domain/document/font/dependency identik; PDF memakai stylesheet dokumen sendiri, bukan Vite CSS; dua aturan CSS yang dibuang tidak dipakai markup runtime. Ini bukti source/condition-equal, bukan klaim pengujian baru. Temuan QA T023 A1 tetap FAIL historis; bukti developer ini tidak menggantikan T023 A2.

## Sumber dan resource

Bundle lama 14 file cocok manifest A1 sebelum build dan dipertahankan (`old-build-match.json`). Snapshot sebelum pengecualian dan bundle final juga dipertahankan privat. Sumber230 pada manifest seluruhnya cocok dengan Git sumber final; 228 sama dengan A1. Bukti build lama, probe dan kegagalan QA sebelumnya tidak dihapus.

Server 8192 milik task dihentikan, browser/profile Chrome tertutup, direktori Chrome privat kosong. SQLite dan bundle privat dipertahankan (`resource-release.json`). Tidak mengakses/mengubah MySQL, tidak reset/drop, tidak GUI/Orca/WA, tidak merge main/push/deploy. Coordinator menangani ledger/push serta penyerahan exact 14 bundle ke native QA.

Belum diuji/dilewati: QA independen T023 A2 termasuk Edge, perangkat/printer fisik dan infrastruktur produksi. Risiko: belum boleh merge main atau menerima rilis sebelum T023 A2 PASS; deployment T024–T025 tetap belum diizinkan.
