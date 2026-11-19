# T089 — QA independen

**Verdict: PASS** untuk acceptance T089 pada pin di bawah, tanpa temuan.

Pemeriksa: worker `/root/t089_dashboard_qa`, 19 November 2026. Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`; panduan QA Project Delivery, AGENTS.md, kontrak T089, PRD D-52 dan DESIGN.md dibaca. Worker tidak mengubah kode produk, ledger, Git, atau database.

## Pin yang diperiksa

- Base/HEAD: `48d9feee42a7dd22e954c19b6689814e9d998806`.
- Candidate: `sha256:df232169dfb2f8131ed9d8946e9abef00e73a1fdca1cf2cd55952e239cb75dfa`, menurut `source-manifest.json`.
- SHA256 Blade: `12024d2c413df74adace586c6ac36f3053825c46eaddc31add2cac89faefcb05`; dihitung ulang dan cocok.
- SHA256 manifest: `dba7ac17ed7cb5603465cdfc1cadb52200f5f81c588ca31719843db1880f9813`.

## Pemeriksaan yang dijalankan sendiri

| Kasus | Hasil dan bukti |
| --- | --- |
| Perubahan terkecil, hanya layout/ikon | PASS. `git diff` menunjukkan satu Blade produk berubah. Perbandingan Python dengan `git show <base>:<path>` membuktikan markup selain `<style>` identik setelah tiga jenis ikon dekoratif yang disetujui diabaikan. CSS menghapus 3 baris dan menambah 5 baris. |
| Pin sumber | PASS. SHA256 file dihitung ulang dengan Python `hashlib.sha256` dan sesuai manifest. |
| Aksi, URL, rendering dan izin tetap | PASS. Kesetaraan markup membuktikan seluruh `wire:key`, loop, URL/detail/WA, `target`, `rel`, handler `markContacted`, disabled/finally/focus, empty state, counter dan binding tidak berubah. PHP dashboard, komponen row-actions/icon, layout CSS/JS byte-identik base. Review PHP mengonfirmasi active-admin guard, validasi kind/id, lock transaksi, pemeriksaan kelayakan ulang dan scope model tetap. |
| Ikon dan aksesibilitas menu | PASS. `eye`, `message-circle`, `circle-check` tersedia di `public/icons/lucide.svg`. Komponen ikon `aria-hidden=true` menjaga nama aksi berasal dari teks. Tombol tetap memiliki label, `aria-haspopup`, `aria-controls`, `aria-expanded`. Layout JS tetap memakai `closest()` sehingga klik anak SVG tetap memilih menu/action; Escape, panah, Tab, scroll/resize dan pemulihan fokus dipertahankan. |
| Root cause CSS | PASS review. Priority grid sejajar awal; kartu tidak memiliki min-height220; rows tidak lagi flex:1, grid rows align-content:start; setiap baris dan header sejajar awal. Batas .dashboard-page/.action-panel membuat perubahan tidak merembet ke daftar lain. |
| Review visual desktop sebelum/sesudah | PASS. Saya membuka `before-desktop.jpg` dan `after-desktop.jpg` dengan view_image. Sebelum: satu isi garansi berada jauh di bawah tombol; sesudah: isi berada di awal tombol, kartu garansi mengikuti isi, 5 backlog tetap terpisah rapi dan nama panjang membungkus. Screenshot diproduksi coordinator, review gambar dilakukan worker ini. |
| Review visual HP | PASS. Saya membuka `after-mobile.jpg` dengan view_image. Tombol kiri44px, nama/tenggat/keterangan terbaca dan membungkus, header/penghitung tidak bertabrakan, fokus tombol terlihat, tinggi panel mengikuti isi. Screenshot diproduksi coordinator, review gambar dilakukan worker ini. |
| Review mixed garansi HP | PASS. `mixed-mobile.jpg` saya buka dengan view_image: dua pengingat service/sale, nama penjualan panjang tiga baris, tenggat dan detail barang membungkus, pemisah serta tombol tetap sejajar awal. Tidak terlihat pemotongan/overlap. |
| Review garansi kosong / 1024 | PASS. `empty-warranty.jpg` saya buka dengan view_image: backlog5 dan garansi0, pesan kosong jelas dalam kartu mandiri, header/penghitung membungkus tanpa tabrakan, kartu garansi tidak disamakan dengan backlog. |
| Review kedua daftar kosong HP | PASS. `empty-mobile.jpg` saya buka dengan view_image: kedua pesan kosong terbaca, panel garansi0 mengikuti isi, header/penghitung serta panel bulanan tetap terpisah rapi. |
| Whitespace | PASS. `git diff --check` exit0. |

Tidak ditemukan cacat pada pemeriksaan independen di atas.

## Receipt coordinator yang ditinjau

Saya membaca `README.md`, `geometry.json`, log `/private/tmp/repair-t089-tests.log` dan `/private/tmp/repair-t089-build.log`. Receipt browser berasal dari coordinator, sementara pemeriksaan sumber, review gambar, dan validasi JSON dilakukan worker ini. Tidak mengklaim menjalankan sesi CUA independen kedua.

- Python assert atas `geometry.json`: **PASS**, tiga viewport375/768/1024 dengan document width sama viewport, 18 baris memiliki action/text top delta0 dan overflow=false, 5backlog+1garansi di setiap lebar. Tinggi siap/garansi542/276,444/156,544/228 menunjukkan kartu mandiri sesuai isi. Source SHA256 dihitung ulang setelah receipt dan tetap cocok.
- `README.md` mencatat desktop1440 document1440, 6baris delta0 dan tinggi442/180; bukti gambarnya telah ditinjau sendiri. Mixed service/sale pada375/1024 mempunyai7baris delta0/tanpa overflow; nama panjang dibuktikan gambar mixed HP.
- Receipt menu001: ArrowDown memilih WA, Escape kembali ke trigger dan aria-expandedfalse. Menu sale tetap3item dengan ikon dan label Lihat penjualan. Mark service kemudian sale melalui UI menghilangkan kedua pengingat, counter0, fokus ke h-warranty. Pengiriman WA tidak dijalankan. Handler/menu/key/focus juga diperiksa sendiri melalui source tanpa perubahan perilaku.
- Receipt zero-case menggunakan fixture SQLite terpisah:5baris fixture menjadi diambil, kedua daftar kosong pada1024 (tinggi238/306) dan375 (document375/2empty); gambar empty HP serta empty-warranty desktop ditinjau sendiri. Tidak memakai database operator atau produksi.
- Log tes yang dibaca: PHPUnit **15PASS/192assertion/991ms**. Log build berakhir **built in399ms**. Ini hasil eksekusi coordinator pada sumber sama, bukan tes/build ulang worker.

Ketiga acceptance T089 terpenuhi oleh review independen sumber/gambar dan receipt pengujian coordinator yang dipin ke sumber sama. Tidak ada perubahan kode setelah QA.

Suite/build tidak diulang karena sumber, dependency dan kondisi relevan sama. Tidak diuji dengan HP/printer fisik atau screen reader; tidak melakukan push/deploy, mengubah operator/produksi, atau membuka penerimaan produksi di luar T089.
