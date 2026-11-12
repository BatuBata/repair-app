# T004 — review visual candidate A1

Arah **framed-sheet**, seed **1526667476**, diwarisi dari `DESIGN.md` dan mockup yang disetujui. Tidak ada draw/redeal atau perubahan token, CSS/JS, komponen dan shell T003. Identitasnya tetap logo tiket teal, Anugerah, IBM Plex Sans, bingkai abu-biru dan satu aksi hitam. Login memakai layout auth dengan kelas lokal; pengguna/profil memakai shell serta field/row-actions T003.

Pertanyaan utama: login “siapa yang masuk”; pengguna “akun mana yang aktif dan apa perannya”; profil “bagaimana mengganti password sendiri”. Objek pengguna hanya nama, username, Admin/Teknisi, Aktif/Nonaktif; fixture privat berisi Arif, Rizky, Dimas dan Budi. Semua nama/data screenshot sintetis. Nilai password dan APP_KEY tetap privat, tidak ada di gambar.

## Perbandingan langsung

- `login-{360,768,1280}.png` berhadapan dengan `reference-login-{360,768,1280}.png` dari `mockup/login.html`: kartu pusat maks380px, ground #f2f5f6, brand/Plex, heading20px, label di atas input, aksi hitam44px, teks bantuan admin yang sama. Perbedaan sengaja: checkbox awal tidak dicentang dan target tombol mata/label checkbox44px untuk sentuh; input contoh mockup dikosongkan sebelum capture referensi. Pada layar kecil posisi vertikal bergeser beberapa piksel karena area checkbox44px. Tidak ada dark branding atau link recovery mandiri.
- `users-list-*` berhadapan dengan `reference-list-*` dari `mockup/tts.html`: tabel desktop tanpa kartu, kepala ringan, garis baris, semua aksi di kolom kiri; mobile menjadi daftar dengan aksi44px di kiri, username/nama/peran dan pil status. Daftar pengguna tidak menambahkan tab/filter milik domain TTS. Data lebih sedikit sehingga baris dan ruang kosong berbeda secara wajar.
- `users-create-*`, `users-edit-*`, `users-reset-*`, `profile-admin-*`, `profile-technician-*` berhadapan dengan `reference-form-*` dari `mockup/tts-baru.html`: label500, gap form20px, lebar form maks560px, isian40px desktop dan48px mobile/tablet, border/fokus shared, satu kolom, aksi utama menempel di atas navigasi bawah. `users-create-scrolled-360.png` membuktikan isian konfirmasi, petunjuk dan Batal dapat dijangkau tanpa tertutup bilah aksi.
- `users-errors-*`, `login-errors-*`, `login-inactive-*`, `users-last-admin-360.png`: border dan pesan error jelas dalam bahasa Indonesia, nilai non-password tetap tersimpan, akun terakhir tetap Aktif. Screenshot error tidak memuat nilai password.
- `users-dropdown-360.png`: menu shared208px dipasang ke BODY, separator sebelum aksi perubahan status, item44px. `dropdown-final.json`: pointer coarse true, klik membuka menu, ArrowDown memilih Reset password, Escape menutup dan memulihkan fokus pemicu. Native konfirmasi perubahan status juga diuji; tidak direkam sebagai layar baru.

Seluruh screenshot asli pada direktori ini dan enam screenshot inspector dibuka/dilihat, termasuk ketiga lebar setiap layar. Contact sheet sementara membantu membandingkan semuanya; layar mobile/form/dropdown/error penting juga dibuka pada ukuran asli. Tidak direkam screenshot starter sebelum edit; referensi pembanding adalah mockup yang disetujui, bukan klaim rekaman before starter.

## Tujuh pemeriksaan app-screen

| Pemeriksaan | Hasil |
| --- | --- |
| Pertanyaan/judul dibaca pertama | PASS — satu h1 nyata; tindakan utama sesuai akun/profil |
| Tepi/rangka konsisten | PASS — shell T003, inset dan padding yang sama; form maks560px |
| Warna pada status/aksi seperlunya | PASS — putih/ground/tinta, logo/fokus teal; status selalu berlabel |
| Angka konsisten dan rata | N/A — tidak ada KPI, harga, jumlah rekaan atau grafik |
| Kosong/aktif/nonaktif jujur | PASS — daftar berasal dari DB privat, status kata jelas; tidak ada dashboard palsu |
| Legenda/encoding | N/A — tidak ada grafik/legenda |
| Mobile dapat dikerjakan | PASS — target44px, input16px, aksi48px tetap terlihat, semua field dapat digulir |

Semua file `*-measurements.json` layar T004 menunjukkan **tanpa overflow horizontal**, body/input **16px pada360/768** dan **14px pada1280**; Plex Sans400/500/600 loaded. Metadata sekunder boleh13–14px sesuai DESIGN. Mono tidak dipakai karena layar ini tidak berisi nomor TTS/JL.

Source lint final: **0 FAIL, 0 WARN**, memakai empat salinan Blade berekstensi .html karena linter tidak mengenali .blade.php. Inspector login: **0 FAIL, 1 WARN hero-void** pada desktop. Warning diterima karena login380px yang sengaja terpusat diwajibkan mockup; mengisi ruang tersebut justru mengubah acuan. Inspector login juga memuat seluruh font yang ditetapkan. Untuk layar terautentikasi, ukuran/font/overflow/target dan interaksi diperiksa melalui Orca pada sesi sintetis, bukan mengklaim hasil inspector login sebagai bukti layar akun.

Batas tersisa: QA independen M1 belum dilakukan; layout tidak diuji pada perangkat fisik/keyboard Android atau produksi HTTPS. Perilaku domain `/tts/baru` tetap T009. Bukti CSS/JS T003 yang tidak berubah tidak diklaim sebagai pengujian ulang alur domain baru.
