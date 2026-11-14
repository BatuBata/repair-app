# T042/A2 — Verifikasi lanjutan dashboard

**BLOCKED**: fixture kosong dapat diakses, tetapi pemeriksaan visual pada lebar wajib 360/768/1280 belum dapat dilaksanakan. Hasil A1 yang lulus tetap berlaku untuk pin yang sama; tidak mengulang tes atau build.

HEAD awal/akhir `89e1c5b739c9d7036b085ba8753ea1c0eae2d6d2`; hash awal/akhir empat file cocok dengan A1 dan dicatat pada assessment.json. Ponytail full tetap digunakan.

- CUA native Chrome membuka `127.0.0.1:8197`, masuk menggunakan akun sintetis pada SQLite kosong. Pada tampilan desktop, ringkasan nol, alur dengan Batal, dua panel kosong dan performa bulanan tampil berurutan. Dua panel berisi penjelasan yang jelas; grafik menyatakan belum ada omzet. Klik Batal membuka daftar servis dengan status Batal terpilih.
- Keyboard: dari alur, Shift+Tab mencapai Batal dengan outline fokus terlihat; Tab mencapai lane pertama dan kemudian Semua servis, Laporan lengkap dan tab Omzet. Panah kanan memilih Laba kotor dan mengganti label grafik; kiri kembali ke Omzet. Tab berikut mencapai grafik, panah kanan menampilkan 01 Nov dan nilai Rp0; panah kiri pada batas awal tetap di 01 Nov. State kosong tidak memiliki tombol aksi baris.
- Browser in-app tidak tersedia pada turn ini. Koneksi CUA Chrome untuk membuat/mengikat tab menghasilkan `Cannot read properties of undefined (reading 'timeoutMs')`, sehingga override viewport melalui API tidak dapat dipakai. Melalui DevTools native, preset Pixel 10 412×924 dapat ditampilkan: ringkasan, Batal dan awal alur terlihat pada screenshot, tetapi bukan salah satu lebar yang diminta. Pemilihan mode Responsive untuk memasukkan lebar manual tidak berhasil. DevTools ditutup kembali.
- Belum diuji: visual empty/initial dan overflow/ukuran target sentuh pada 360, 768, 1280 secara independen. Tidak ada cacat produk teramati; hasil parsial tidak cukup untuk PASS. Screenshot diamati pada tool, tidak disimpan. Tidak ada aksi perubahan data, perubahan kode, commit, push atau deploy.
