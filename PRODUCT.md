# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Aplikasi: Laravel 13 + Livewire + Tailwind + MySQL (PRD.md §7). Mockup desain: HTML/CSS statis yang bisa diklik di folder `mockup/`, terpisah dari kode aplikasi (keputusan user 11 Nov 2026).

## Users

- **Teknisi** — bekerja di lokasi customer (rumah, untuk mesin cuci) atau di toko. Memakai HP Android, sering satu tangan, sambil berhadapan dengan customer. Tugasnya mencatat barang yang diterima secepat mungkin: nama, nomor HP, kategori barang, keluhan.
- **Owner/admin** — pemilik toko yang ikut bekerja langsung. Lebih sering memakai HP, kadang laptop di toko. Menetapkan teknisi, QC, mengisi rincian biaya (harga jual, modal, jasa), menerbitkan nota, mengirim TTS/nota lewat WhatsApp pribadi (biasanya sekaligus sekitar jam 18.00–19.00), melihat garansi yang hampir habis, mencatat penjualan, pengeluaran, dan laporan laba.
- **Customer** — tidak login; membuka link tracking dari WhatsApp atau QR di struk untuk melihat status servis dan nota.

## Product Purpose

Aplikasi servis untuk satu toko servis elektronik kecil (Toko Anugerah) yang saat ini mencatat lewat grup WhatsApp dan rekap manual. Tujuannya: setiap barang servis punya tanda terima (TTS) yang bisa dilacak, nota keluar rapi tanpa menampilkan modal, garansi terpantau, dan owner melihat omzet serta laba bersih tanpa rekap manual. Detail kebutuhan ada di `PRD.md`.

## Positioning

Input super singkat untuk toko servis kecil yang belum punya SOP dan pembagian posisi: teknisi cukup mengisi 4 kolom. Klien secara eksplisit menolak aplikasi servis yang panjang dan cocok untuk toko besar dengan banyak karyawan.

## Operating Context

- Alur: teknisi isi TTS → admin kirim TTS via WA / cetak struk → admin tetapkan teknisi → owner QC → admin isi rincian dan terbitkan nota → customer ambil barang → pengingat garansi.
- Dokumen fisik: struk printer thermal 58mm (dicetak 2 lembar: satu ditempel di barang, satu untuk customer) dan PDF A5 yang dikirim lewat WhatsApp pribadi.
- Pengiriman WhatsApp tetap manual dari WA pribadi admin; aplikasi hanya membuka WA dengan teks siap kirim.
- Kategori barang: mesin cuci, TV, laptop, komputer, monitor, printer, catokan, HP.
- Toko juga menjual barang (mis. printer bekas) dan mencatat pengeluaran harian (bensin, konsumsi, gaji).
- Zona waktu WITA.

## Capabilities and Constraints

- Dua peran: Admin dan Teknisi. Teknisi tidak boleh melihat harga modal, nota, atau laporan.
- Istilah: TTS (Tanda Terima Service), nota, harga jual, modal, jasa, garansi, omzet, laba bersih. Nomor `TTS-YYMM-NNN` dan `JL-YYMM-NNN`.
- Status TTS: Diterima, Dikerjakan, Selesai, Diambil, Batal.
- Mobile-first dan responsif untuk semua layar, termasuk admin (PRD.md §10.2): navigasi bawah di HP, sidebar di desktop, kartu di HP, tabel di desktop, tombol aksi utama menempel di bawah.
- Bisa ditambahkan ke layar utama Android; tanpa mode offline.
- Bahasa Indonesia; format uang `Rp1.100.000`.
- Belum diputuskan: modul proyek/investor, email laporan harian, master kerusakan (di luar v1).

## Brand Commitments

Belum ada logo, warna, atau aset merek yang wajib. Nama tampilan: "Anugerah" (Toko Anugerah).

Preferensi tetap (keputusan user 11 Nov 2026, setelah meninjau 10 arah visual bertema): tampilan aplikasi **bersih dan profesional tanpa metafora atau tema** (bukan gaya rambu, struk, buku nota, dll.). Patokan kualitas: setara aplikasi kasir/toko Indonesia seperti Moka POS dan Majoo, dikerjakan dengan rapi.

## Evidence on Hand

- Transkrip wawancara klien (`10-11-2026 22.24.txt`, di luar repo) dan `PRD.md`.
- Belum ada data customer nyata, foto, logo, atau testimoni. Mockup memakai data sintetis yang diberi label contoh.

## Product Principles

1. Input sesingkat mungkin; setiap kolom tambahan harus punya alasan kuat.
2. HP dulu: tugas utama selesai dengan satu tangan di layar 360px.
3. Modal dan laba hanya untuk admin; dokumen customer hanya menampilkan harga jual.
4. Sederhana untuk toko kecil, bukan sistem SOP perusahaan besar.
5. Data harus bisa dilacak dari nomor TTS, dari terima sampai garansi habis.

## Accessibility & Inclusion

Target sentuh minimal 44×44px, teks dan input minimal 16px, kontras teks WCAG AA, status dibedakan dengan label teks (bukan warna saja) (PRD.md §10.2, NFR-07).
