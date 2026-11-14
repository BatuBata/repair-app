# T040/A3 — QA independen datepicker

**Verdict: BLOCKED (pembuktian browser).** Kandidat `sha256:aa571baa88e28a0840eaf209f41925ff85c024ac4a333b6d87737167cfc451dc`. Model diminta `gpt-6.1-terra`, model efektif `not_exposed_by_runtime`. Ponytail full dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Tidak ada perubahan kode, ledger, workflow atau data permanen; tidak commit, push, merge atau deploy.

## Identitas dan bukti yang dipakai ulang

`shasum -a 256` pada 11 file dalam urutan `docs/evidence/T039/A2/source-manifest.json`, lalu SHA-256 atas output tersebut menghasilkan tepat hash kandidat; 11 hash individual cocok. `git diff --name-only 89e1c5b739c9d7036b085ba8753ea1c0eae2d6d2 -- .github/workflows` kosong. D-25 mempertahankan workflow aktif dan revisi lokal; D-28 mengharuskan kontrol bersama dan nilai ISO.

Uji terarah independen T040/A2 pada pin sama: 62 tes/718 assertion PASS; full CI implementer T039/A2 pada pin sama: 189 tes (188 PASS, 1 SKIP), 1.981 assertion, Pint/PHPStan PASS. Tidak mengulang suite sama. Audit source menemukan delapan kontrol: sold_on, entry_date, spent_on/from/to, laporan from/to, picked_up_on; field delegasi ke komponen bersama, tidak ditemukan `<input type="date">` mentah. Audit ini bukan uji runtime semua caller.

## Browser aktual — IAB tersembunyi, fixture lokal sintetis

Tab id 4 yang diberikan tidak tersedia dalam sesi subagent; membuat satu tab IAB tersembunyi berhasil dan sudah login. Membuat tab IAB visible:true ditolak karena visibility tidak didukung subagent; satu percobaan membuat tab Chrome resmi gagal `Cannot read properties of undefined (reading 'timeoutMs')`. Tidak memakai CDP. Kalender menjalankan fokus setelah render dengan `requestAnimationFrame`; efek tab tersembunyi terhadap penjadwalan belum dipastikan.

- A1 struktur: pada filter Dari tanggal, accessibility tree menampilkan table > enam row > 42 cell; DOM picker yang sama menghitung 6 dan 42. PASS untuk struktur.
- A1 fokus: dari 01/11 maupun 02/11 PageDown menampilkan Desember tetapi AX dan DOM menunjukkan fokus `body`. Dalam satu percobaan PageUp dari 02/12 menunjukkan fokus sel 01/11; PageUp lain dari sel 01/12 menunjukkan body. **Hasil tidak konsisten dalam tab tersembunyi; belum dapat membedakan cacat produk dari lingkungan, sehingga bukan PASS maupun FAIL produk.** Perlu ulang dalam browser terlihat setelah render.
- Tombol sebelumnya Desember→November dan berikutnya November→Desember, klik luar menutup, Escape menutup dan mengembalikan fokus trigger, Hari ini memilih 15/11/2026: PASS pada skenario ini. ArrowLeft dari 02/12 terfokus 01/12; ArrowRight dan ArrowDown sempat fokus 03/12 dan 09/12. Urutan cepat seluruh panah menghasilkan pembacaan fokus yang tidak konsisten, jadi ArrowUp dan rangkaian keyboard lengkap belum terbukti. Enter menutup dan mengembalikan fokus.
- Memilih 02/12 ketika Sampai 15/11 memunculkan pesan validasi rentang secara live; Hari ini menghilangkan pesan. Fixture tidak memiliki baris pengeluaran, sehingga penyaringan hasil data belum dibuktikan. Nilai hidden terbaca disunting oleh lapisan browser (`<redacted>`); perbandingan literal tidak dapat dipercaya. Source mengikat input hidden ke nilai dan `wire:model.live`, tetapi bukan verifikasi nilai runtime.
- Form Tambah pengeluaran: Simpan dengan kategori dan nominal kosong memunculkan kedua pesan validasi dan memfokuskan kategori; tidak menyimpan data. Belum membuktikan validasi/fokus error tanggal atau penyimpanan tanggal valid.
- Ukuran independen popup filter, `[kiri,atas,kanan,bawah]`: 360×800 `[16,234,344,653]`; 768×900 `[24,234,352,653]`; 1280×900 `[261,223,589,642]`. Semua di dalam viewport; tombol terkecil 44×44 CSS px pada masing-masing. Viewport direset. Screenshot implementer A2 360/768/1280 ditinjau; popup form penjualan tampak utuh. Ini tidak membuktikan seluruh posisi/tinggi layar.
- Source memuat role dialog, grid, row dan gridcell serta min/max default 1000-01-01/9999-12-31 dan logika disabled. Batas serta tanggal disabled belum dieksekusi pada runtime; tidak ada caller dengan batas khusus yang diuji.

## Batas

Perlu browser terlihat untuk mengulang PageDown/PageUp dan semua panah dengan observasi sesudah render; juga verifikasi batas min/max, nilai hidden ISO, filter dengan data, save dan fokus error tanggal serta caller runtime lain. Pembaca layar dan perangkat fisik tidak diuji. Jangan menerima kandidat berdasarkan bukti ini saja.
