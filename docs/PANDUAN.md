# Panduan singkat Aplikasi Servis Anugerah

Panduan ini untuk pemilik/admin dan teknisi. Masuk dengan nama pengguna dan password yang diberikan pemilik. Menu yang tersedia mengikuti peran akun. Jangan membagikan password melalui panduan ini.

## Membuka di ponsel

Di Android, buka alamat aplikasi di Chrome dan masuk. Buka menu Chrome **⋮** lalu pilih **Tambahkan ke layar utama** atau **Instal aplikasi** bila tersedia, kemudian ikuti petunjuknya. Nama pilihan dapat berbeda menurut versi Chrome.

## Teknisi: membuat TTS

1. Pilih **Buat TTS**. Isi **Nama customer**, **Nomor HP / WhatsApp**, pilih kategori, dan isi **Keluhan / kerusakan**. Keempatnya wajib.
2. Isi **Keterangan barang** atau **Alamat** bila diperlukan, lalu pilih **Simpan TTS**. Nomor TTS dibuat otomatis.
3. Buka TTS dari **Daftar TTS** untuk melihat status dan tanda terima. Pembuat TTS dapat memilih **Ubah TTS** hanya saat status **Diterima**. Setelah status berubah menjadi **Dikerjakan**, hubungi admin jika ada perubahan.
4. Pada rincian TTS tersedia **Cetak struk** dan **PDF struk**. Untuk memakai printer struk 58 mm, pilih **Cetak struk**, pilih printer yang sesuai, muat kertas, lalu periksa hasil cetak agar teks terbaca dan tidak terpotong. Kecocokan printer perlu diperiksa pada perangkat yang akan digunakan.

## Admin: servis, WhatsApp, dan nota

1. Di **Servis**, pilih **Buat TTS** untuk mencatat servis. Admin dapat melihat seluruh TTS.
2. Pada TTS berstatus **Diterima**, pilih teknisi lalu **Tetapkan teknisi**. Saat status **Dikerjakan**, admin dapat mengganti teknisi.
3. Untuk menghubungi customer, buka rincian TTS atau pengingat di **Beranda**, pilih **Kirim WA**, lalu periksa dan kirim pesan melalui WhatsApp. Aplikasi menyiapkan pesan; pengiriman dilakukan manual. Fitur ini tersedia untuk admin.
4. Setelah pekerjaan selesai, pilih **Isi rincian & nota**. Untuk tiap baris, pilih jenis **Part** atau **Jasa**, isi deskripsi, **Qty**, **Harga jual**, dan **Modal**. Modal part boleh 0; modal jasa tidak perlu diisi. Periksa total dan lama garansi, lalu pilih **Terbitkan nota**. Nilai 0 hari berarti tanpa garansi.
5. Jika nota perlu diperbaiki, pilih **Koreksi nota**, sesuaikan isian, lalu **Simpan koreksi**. Tanggal nota tetap tanggal semula, sehingga tanggal akhir garansi juga dihitung dari tanggal nota semula.
6. Setelah customer mengambil barang, pada status **Selesai** isi tanggal pengambilan lalu pilih **Tandai Diambil**.
7. TTS berstatus **Diterima** atau **Dikerjakan** dapat dibatalkan lewat menu tindakan **···** → **Batalkan TTS**. TTS yang dibatalkan tidak dapat diaktifkan lagi.
8. Pada rincian TTS tersedia **Cetak struk** dan **PDF struk**; admin juga mendapat **PDF A5**. Customer dapat membuka tautan status tanpa login. Setelah nota terbit, pilih **Unduh nota (PDF)** pada halaman status untuk mengunduh nota.

## Admin: Beranda dan pengingat garansi

**Beranda** memuat ringkasan TTS dan omzet hari ini. Grafik bulanan dapat menampilkan **Omzet**, **Laba kotor**, atau **Nota servis**; pilih tab untuk mengganti grafik.

Pengingat garansi muncul sejak tiga hari sebelum tanggal akhir sampai tanggal akhir, termasuk hari terakhir. Buka menu tindakan **···** pada pengingat: pilih **Kirim WA** untuk membuka pesan WhatsApp, lalu kirim secara manual; setelah customer dihubungi, pilih **Tandai dihubungi** agar pengingat tidak muncul lagi. Pengingat juga mencakup penjualan yang memiliki garansi.

## Admin: stok dan penjualan

1. Buka **Stok barang** → **Tambah barang**. Isi nama, **Harga beli**, **Harga jual**, dan stok awal, lalu **Simpan barang**.
2. Untuk barang masuk, buka menu tindakan **···** → **Restok & riwayat**. Isi jumlah, harga beli baru per unit, tanggal masuk, dan catatan bila perlu. Pilih **Simpan restok**. Restok menambah stok dan memperbarui harga beli; restok bukan catatan pengeluaran.
3. Buka **Penjualan** → **Buat penjualan**. Pilih barang dan jumlah, isi data customer bila perlu, lalu periksa total sebelum menyimpan. Judul formulirnya **Penjualan baru**. Nomor HP wajib bila penjualan memakai garansi. Stok berkurang saat penjualan disimpan.
4. Penjualan yang sudah disimpan tidak dapat diedit. Jika harus membatalkan, buka rinciannya dan pilih **Hapus penjualan**; penghapusan tidak dapat dibatalkan dan stok barang dikembalikan.
5. Rincian penjualan menyediakan **Cetak struk**, **PDF struk**, dan **PDF A5**. Untuk printer struk 58 mm, pilih **Cetak struk**, pilih printer, muat kertas, dan periksa keterbacaan hasil pada perangkat yang dipakai.

## Admin: pengeluaran dan laporan

1. Buka **Pengeluaran** → **Tambah pengeluaran**. Isi tanggal, kategori **Bensin**, **Konsumsi**, **Gaji**, atau **Lainnya**, nominal, serta keterangan bila perlu. Pilih **Simpan**.
2. Buka **Laporan**. Periode awal adalah tanggal 1 bulan ini sampai hari ini. Ubah **Dari tanggal** dan **Sampai tanggal** untuk memilih rentang lain. Total mencakup seluruh rentang; grafik harian ditampilkan dalam jendela maksimal 31 hari. Gunakan **Hari sebelumnya** atau **Hari berikutnya** untuk berpindah jendela.
3. Pilih **Semua**, **Servis**, **Penjualan**, atau **Pengeluaran** untuk menyaring rincian. Gunakan tombol rincian pada baris untuk membuka bukti transaksi.

## Admin: pengguna, kategori, dan toko

- Dari **Menu** → **Pengaturan** → **Pengguna**, pilih **Tambah pengguna** untuk membuat akun. Pilih peran Admin atau Teknisi, lalu berikan nama pengguna dan password secara pribadi. Akun admin aktif minimal satu harus tetap tersedia.
- Dari **Menu** → **Pengaturan** → **Kategori**, admin dapat menambah atau menonaktifkan kategori servis. Kategori nonaktif tidak muncul untuk TTS baru.
- Dari **Pengaturan toko**, perbarui informasi toko, syarat garansi, atau lama garansi bawaan, lalu pilih **Simpan pengaturan toko**.
- Pilih **Profil** untuk mengganti password sendiri. Pilih **Keluar** setelah selesai, terutama pada perangkat bersama.

Jika tombol tidak tersedia, akun mungkin tidak memiliki akses ke tindakan itu. Hubungi admin toko.
