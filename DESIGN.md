---
name: Anugerah Servis
description: Aplikasi servis Toko Anugerah — bingkai abu-biru berbentuk L, pekerjaan di atas lembar putih, warna hanya pada data, status, dan satu aksi hitam.
colors:
  ground: "#f2f5f6"
  surface: "#ffffff"
  raised: "#f5f7f8"
  active: "#e6eaec"
  border: "#e3e7e9"
  rule: "#eef1f2"
  control: "#cfd4d7"
  field: "#8a9296"
  text: "#131416"
  muted: "#5d5f60"
  faint: "#6b6e70"
  accent: "#1b6c80"
  primary: "#131416"
  primary-hover: "#2b2d30"
  primary-ink: "#ffffff"
  positive: "#1e7b4f"
  negative: "#c4242a"
  warning: "#92400e"
  pill-info-bg: "#e8f0fa"
  pill-info-ink: "#1f4f8a"
  pill-warning-bg: "#fdf3dc"
  pill-warning-ink: "#92400e"
  pill-positive-bg: "#dcfae5"
  pill-positive-ink: "#04250e"
  pill-neutral-bg: "#ebedee"
  pill-neutral-ink: "#353736"
  pill-negative-bg: "#ffedea"
  pill-negative-ink: "#831c1e"
  chart-1: "#1b6c80"
  chart-2: "#2f70bb"
  chart-3: "#a86f0c"
  chart-muted: "#c9ced2"
  grid: "#eef1f2"
  axis-ink: "#5d5f60"
typography:
  title:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "22px"
    fontWeight: 600
    lineHeight: "30px"
    letterSpacing: "-0.01em"
  section:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 600
    lineHeight: "22px"
  kpi:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "28px"
    fontWeight: 400
    lineHeight: "34px"
    letterSpacing: "-0.01em"
    fontFeature: "tnum"
  kpi-phone:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "20px"
    fontWeight: 400
    lineHeight: "26px"
    fontFeature: "tnum"
  body:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: "20px"
  body-phone:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: "24px"
  label:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 500
    lineHeight: "20px"
  small:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: "18px"
  small-phone:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: "18px"
  brand:
    fontFamily: "IBM Plex Sans, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    letterSpacing: "-0.01em"
  id:
    fontFamily: "IBM Plex Mono, ui-monospace, monospace"
    fontSize: "14px"
    fontWeight: 500
    letterSpacing: "-0.01em"
    fontFeature: "tnum"
  id-title:
    fontFamily: "IBM Plex Mono, ui-monospace, monospace"
    fontSize: "26px"
    fontWeight: 500
    lineHeight: "34px"
    letterSpacing: "-0.02em"
rounded:
  sheet: "16px"
  dialog: "12px"
  card: "8px"
  control: "6px"
  tag: "4px"
  track: "2px"
  pill: "999px"
spacing:
  sheet-inset: "8px"
  gap: "24px"
  gap-phone: "16px"
  pad: "32px"
  pad-tablet: "24px"
  pad-phone: "16px"
  card-pad: "20px"
  card-pad-phone: "16px"
  form-gap: "20px"
  block: "40px"
components:
  sidebar:
    backgroundColor: "{colors.ground}"
    width: "228px"
    padding: "12px 12px 16px"
  nav-item:
    textColor: "{colors.muted}"
    rounded: "{rounded.control}"
    height: "32px"
    padding: "0 8px"
  nav-item-hover:
    backgroundColor: "{colors.raised}"
    textColor: "{colors.text}"
  nav-item-active:
    backgroundColor: "{colors.active}"
    textColor: "{colors.text}"
    typography: "{typography.label}"
  topbar:
    backgroundColor: "{colors.ground}"
    height: "48px"
  topbar-phone:
    backgroundColor: "{colors.ground}"
    height: "52px"
    padding: "0 8px 0 16px"
  topbar-search:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.control}"
    height: "32px"
    width: "548px"
    padding: "0 44px 0 32px"
  sheet:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.sheet}"
    padding: "24px 32px 40px"
  sheet-phone:
    backgroundColor: "{colors.surface}"
    rounded: "0px"
    padding: "20px 16px 96px"
  panel:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.card}"
    padding: "{spacing.card-pad}"
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.primary-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    height: "36px"
    padding: "0 14px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    height: "36px"
    padding: "0 14px"
  button-secondary-hover:
    backgroundColor: "{colors.raised}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    height: "36px"
    padding: "0 14px"
  button-danger:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.negative}"
    rounded: "{rounded.control}"
    height: "36px"
  button-small:
    height: "32px"
    padding: "0 10px"
  button-phone:
    height: "44px"
    padding: "0 14px"
  button-actionbar:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.primary-ink}"
    rounded: "{rounded.control}"
    height: "48px"
  button-disabled:
    backgroundColor: "{colors.raised}"
    textColor: "{colors.faint}"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    height: "40px"
    padding: "0 12px"
  input-phone:
    typography: "{typography.body-phone}"
    height: "48px"
  choice:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    height: "72px"
    padding: "6px"
  choice-selected:
    backgroundColor: "{colors.raised}"
    textColor: "{colors.text}"
    typography: "{typography.label}"
  segmented:
    backgroundColor: "{colors.active}"
    rounded: "{rounded.card}"
    padding: "2px"
  segmented-on:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    height: "28px"
  tab:
    textColor: "{colors.muted}"
    typography: "{typography.body}"
    height: "40px"
  tab-active:
    textColor: "{colors.text}"
    typography: "{typography.label}"
  tab-phone:
    height: "44px"
  lane-cell:
    backgroundColor: "{colors.surface}"
    padding: "16px 20px 18px"
  lane-tag:
    backgroundColor: "{colors.raised}"
    textColor: "{colors.muted}"
    typography: "{typography.small}"
    rounded: "{rounded.tag}"
    height: "22px"
    padding: "0 7px"
  lane-tag-late:
    backgroundColor: "{colors.pill-warning-bg}"
    textColor: "{colors.pill-warning-ink}"
  kpi-tab:
    textColor: "{colors.text}"
    typography: "{typography.kpi}"
    padding: "16px 20px 14px"
  kpi-tab-phone:
    typography: "{typography.kpi-phone}"
    height: "52px"
    padding: "12px 16px"
  kpi-tab-phone-selected:
    backgroundColor: "{colors.raised}"
  table-header:
    textColor: "{colors.muted}"
    typography: "{typography.small}"
    height: "36px"
    padding: "0 12px"
  table-row:
    height: "52px"
    padding: "8px 12px"
  table-row-hover:
    backgroundColor: "{colors.raised}"
  row:
    height: "56px"
    padding: "10px 0"
  row-phone:
    height: "64px"
  pill:
    backgroundColor: "{colors.pill-neutral-bg}"
    textColor: "{colors.pill-neutral-ink}"
    typography: "{typography.small}"
    rounded: "{rounded.pill}"
    height: "22px"
    padding: "0 8px"
  pill-diterima:
    backgroundColor: "{colors.pill-info-bg}"
    textColor: "{colors.pill-info-ink}"
  pill-dikerjakan:
    backgroundColor: "{colors.pill-warning-bg}"
    textColor: "{colors.pill-warning-ink}"
  pill-selesai:
    backgroundColor: "{colors.pill-positive-bg}"
    textColor: "{colors.pill-positive-ink}"
  pill-diambil:
    backgroundColor: "{colors.pill-neutral-bg}"
    textColor: "{colors.pill-neutral-ink}"
  pill-batal:
    backgroundColor: "{colors.pill-negative-bg}"
    textColor: "{colors.pill-negative-ink}"
  track-step:
    backgroundColor: "{colors.active}"
    rounded: "{rounded.track}"
    height: "4px"
  track-step-done:
    backgroundColor: "{colors.accent}"
  notice:
    backgroundColor: "{colors.raised}"
    textColor: "{colors.muted}"
    rounded: "{rounded.control}"
    padding: "10px 12px"
  notice-warn:
    backgroundColor: "{colors.pill-warning-bg}"
    textColor: "{colors.pill-warning-ink}"
  empty:
    textColor: "{colors.muted}"
    rounded: "{rounded.control}"
    height: "96px"
    padding: "16px"
  dialog:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.dialog}"
    width: "440px"
    padding: "20px"
  toast:
    backgroundColor: "{colors.text}"
    textColor: "{colors.primary-ink}"
    rounded: "{rounded.card}"
    padding: "10px 14px"
  bottomnav:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    height: "64px"
  bottomnav-create:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.primary-ink}"
    rounded: "{rounded.pill}"
    width: "48px"
    height: "30px"
  actionbar:
    backgroundColor: "{colors.surface}"
    padding: "12px 16px"
---

# Design System: Anugerah Servis

## Overview

**Creative North Star: "Lembar di Atas Bingkai"**

Aplikasi ini adalah alat kerja toko yang tenang dan rapi. Kerangka aplikasi (sidebar dan bilah atas) adalah satu bingkai abu-biru tipis berbentuk huruf L. Semua pekerjaan diletakkan di atas satu lembar putih yang "duduk" di dalam bingkai itu: menjorok 8px dari tepi kanan dan bawah jendela, bersudut 16px, bertepi garis 1px, tanpa bayangan. Kedalaman hanya datang dari satu langkah warna (bingkai → lembar), bukan dari bayangan atau kartu bertumpuk.

Warna dihemat. Hampir semua yang terlihat adalah tinta gelap, abu-abu, dan putih. Warna hanya muncul di tiga tempat: data (garis grafik), status (pil pucat dengan teks gelap senada), dan satu aksi utama per layar yang berwarna hitam. Satu aksen teal dipakai sangat sedikit, sebagai penanda pilihan dan kemajuan, bukan sebagai warna merek yang ditempel di mana-mana. Ini sengaja menolak dua hal yang sudah ditolak user: versi pertama yang hijau di mana-mana, dan gaya "dashboard kit" (kartu statistik berikon, aksen di setiap tombol, kartu di dalam kartu).

Gaya ini dibagikan oleh tasteskill app sheet `framed-sheet` (seed 1526667476, `.taste-skill/app/direction.json`) dan dipilih user. Bingkainya diwarnai satu langkah abu-biru dari aksen teal. Elemen khas produk adalah **jalur servis**: empat sel bergaris pembagi berisi jumlah TTS per tahap dan tag nomor TTS beserta umurnya. Di HP bingkai hilang, lembar menjadi layar penuh, dan navigasi pindah ke bawah.

**Key Characteristics:**
- Bingkai L abu-biru (`ground`) tanpa garis antara sidebar dan bilah atas; lembar putih inset 8px, sudut 16px, tepi 1px, tanpa bayangan.
- IBM Plex Sans 400/500/600 untuk semua teks; IBM Plex Mono khusus nomor TTS/JL.
- Angka besar ditulis ringan (400); tebal diletakkan pada label dan keadaan aktif.
- Satu aksi utama hitam per layar; aksi lain putih bergaris.
- Teal hanya pada garis grafik, garis bawah tab/KPI aktif, batang tahapan, logo tiket, dan cincin fokus.
- Status selalu ditulis sebagai kata di dalam pil pucat.
- HP dulu: lembar jadi layar, bilah atas 52px, navigasi bawah, bilah aksi menempel di bawah, target sentuh 44px.

## Colors

Palet netral abu-biru dingin dengan satu aksen teal yang sangat hemat; kroma dibawa oleh data dan status, bukan oleh kerangka.

### Primary
- **Tinta Hampir Hitam** (`primary` #131416): warna tombol aksi utama (Buat TTS, Simpan TTS, Terbitkan nota), gelembung "Buat TTS" di navigasi bawah, dan latar toast. Hover memakai `primary-hover`, teks di atasnya `primary-ink` putih (kontras 18.4:1).
- **Teal Bengkel** (`accent` #1b6c80, 5.99:1 di atas putih): satu-satunya warna merek. Dipakai hanya untuk garis bawah 2px tab aktif dan tab KPI aktif, segmen batang tahapan yang sudah dilalui, logo tiket, garis grafik utama (`chart-1`), dan cincin fokus (`:focus-visible` 2px, serta glow fokus isian `rgb(27 108 128 / .18)`).

### Neutral
- **Bingkai Abu-Biru** (`ground`): latar sidebar dan bilah atas (satu bingkai L), latar bilah atas yang menempel di HP, dan latar halaman publik/dokumen.
- **Lembar Putih** (`surface`): lembar kerja, panel, isian, tombol sekunder, navigasi bawah, bilah aksi, dialog.
- **Angkat Tipis** (`raised`): hover baris/tombol/nav, tag di jalur servis, kotak pemberitahuan, pilihan yang terpilih, KPI terpilih di HP.
- **Aktif** (`active`): item nav yang sedang dibuka, rel segmented control, batang tahapan yang belum dilalui.
- **Tepi** (`border`): tepi lembar, panel, jalur servis, garis bawah baris tab, kepala tabel, navigasi bawah.
- **Garis Baris** (`rule`): pemisah antar baris tabel, daftar, rincian, dan laporan; lebih pucat dari `border`.
- **Garis Tombol** (`control`): garis tombol sekunder, garis pilihan barang, garis putus-putus empty state.
- **Garis Isian** (`field`, 3.17:1 di atas putih): tepi semua kolom isian dan kolom cari di toolbar, supaya batas isian terbaca di luar ruangan.
- **Tinta** (`text`, 18.4:1): teks utama, angka, judul.
- **Abu Sekunder** (`muted`, 6.42:1): sub-judul, label kunci, baris kedua, label sumbu grafik (`axis-ink`).
- **Abu Pucat** (`faint`, 5.14:1 di putih, 4.69:1 di `ground`): placeholder, ikon kecil, tanda "—", jumlah di tab yang tidak aktif. Tetap lolos AA untuk teks.

### Status dan nilai
- **Positif** (`positive` #1e7b4f): angka laba di ringkasan rincian dan tanda sukses setelah TTS disimpan. Bukan warna merek.
- **Negatif** (`negative` #c4242a): teks tombol bahaya (Batalkan TTS), tepi isian yang salah, pesan error.
- **Peringatan** (`warning` #92400e): teks tenggat garansi ("berakhir besok").
- **Pasangan pil status** (latar pucat + teks gelap senada, semua ≥ 6.4:1): Diterima = `pill-info-bg`/`pill-info-ink`; Dikerjakan = `pill-warning-bg`/`pill-warning-ink`; Selesai = `pill-positive-bg`/`pill-positive-ink`; Diambil = `pill-neutral-bg`/`pill-neutral-ink`; Batal = `pill-negative-bg`/`pill-negative-ink` plus coret. Pasangan warning juga dipakai untuk tag TTS terlambat (≥ 2 hari) dan pemberitahuan peringatan.

### Grafik
- **Seri utama** `chart-1` (sama dengan teal), seri kedua `chart-2` biru, seri ketiga `chart-3` amber (4.25:1), seri lain `chart-muted`. Garis bantu `grid`, label sumbu `axis-ink`. Layar yang ada sekarang hanya memakai satu seri.

### Named Rules
**The One Teal Rule.** Teal hanya boleh muncul pada garis grafik, garis bawah 2px tab/KPI aktif, batang tahapan, logo tiket, dan cincin fokus. Tidak pernah pada tombol, teks paragraf, atau latar bidang besar.

**The Black Action Rule.** Satu layar punya paling banyak satu tombol hitam; semua aksi lain adalah tombol putih bergaris atau tombol hantu.

**The Status In Words Rule.** Status selalu ditulis sebagai kata di dalam pil (Diterima, Dikerjakan, Selesai, Diambil, Batal); warna hanya membantu, tidak pernah menjadi satu-satunya penanda.

## Typography

**Body Font:** IBM Plex Sans (dengan system-ui, sans-serif), berat 400/500/600
**Label/Mono Font:** IBM Plex Mono (dengan ui-monospace, monospace), berat 400/500

**Character:** Plex Sans memberi kesan teknis tapi ramah, rapi untuk angka rupiah. Plex Mono hanya menulis nomor TTS/JL sehingga nomor itu langsung dikenali sebagai "kunci" yang dicari, dipanggil, dan ditempel di barang.

### Hierarchy
- **Title** (600, 22px, 30px, -0.01em): judul halaman di baris judul ("Beranda", "Servis"). Sama di desktop dan HP.
- **Section** (600, 16px, 22px): judul panel dan bagian ("Perlu tindakan", "Laba rugi"), judul dialog.
- **KPI** (400, 28px, 34px, tabular): jumlah di jalur servis, nilai tab KPI, total besar rincian, laba bersih laporan. KPI terpilih naik ke 500. Satuan ("Rp", "nota") ditulis 14px abu di sampingnya. Di HP nilai tab KPI menjadi 20px/26px (`kpi-phone`).
- **Body** (400, 14px, 20px di desktop; 16px, 24px di HP): teks umum, sel tabel, isian. Isian di HP selalu 16px.
- **Label** (500, ukuran body): label isian, tombol, tab aktif, nav aktif, nama customer di baris.
- **Small** (400, 12px, 18px di desktop; 13px di HP): kepala tabel, meta, petunjuk, pil status, tag jalur.
- **Brand** (600, 15px): nama "Anugerah" di sidebar.
- **ID** (Plex Mono 500, -0.01em, tabular, ikut ukuran sekitarnya): nomor `TTS-YYMM-NNN` dan `JL-YYMM-NNN` di tabel, daftar, dan tag jalur (tiga digit terakhir, 12px).
- **ID Title** (Plex Mono 500, 26px, 34px, -0.02em; 24px di HP): nomor TTS sebagai judul halaman detail. Halaman status customer memakai 28px/36px, halaman sukses 34px/44px (28px di HP).

### Named Rules
**The Mono Is For Numbers Rule.** IBM Plex Mono hanya untuk nomor TTS/JL (termasuk tag tiga digit dan nomor besar di struk/nota). Nama, harga, tanggal, dan label tetap Plex Sans.

**The Light Big Number Rule.** Angka besar ditulis 400; tebal diberikan ke label dan keadaan terpilih. Nilai yang tidak terpilih tetap tinta (bukan abu), karena angka abu terbaca seperti nonaktif.

**The Tabular Rule.** Semua uang, jumlah, tanggal, dan nomor memakai `font-variant-numeric: tabular-nums`; angka uang rata kanan.

## Layout

**Desktop (≥ 1024px): bingkai L.** Grid aplikasi dua kolom: sidebar 228px + kolom utama. Sidebar dan bilah atas 48px berbagi latar `ground` tanpa garis di antaranya. Bilah atas memegang kolom cari 548 × 32px di tengah (maks 50% lebar) dengan petunjuk tombol `/`. Lembar putih menempel ke sidebar dan bilah atas, menjorok 8px dari kanan dan bawah jendela, dengan padding 24px atas, 32px samping, 40px bawah. Di dalam lembar ada grid 12 kolom dengan jarak 24px (pada 1440px: isi 1138px = 12 × 72,8 + 11 × 24). Pasangan bentang yang dipakai: 7 + 5 (Beranda), 8 + 4 (detail, rincian), 5 + 7 (laporan). Panel samping yang berisi ringkasan menempel (`sticky`, top 24px).

**Tablet (640–1023px):** kerangka HP (tanpa sidebar, navigasi bawah), tetapi padding lembar 24px, jalur servis tetap 4 kolom, pilihan barang 5 kolom.

**HP (< 640px): lembar menjadi layar.** Bingkai hilang; latar body menjadi putih. Bilah atas 52px menempel di atas dengan latar `ground`, berisi logo + "Anugerah" dan avatar 44px; kolom cari pindah ke dalam halaman. Navigasi bawah 64px (+ safe area) tetap di bawah. Halaman dengan aksi utama punya bilah aksi yang menempel di atas navigasi bawah. Padding lembar 16px, jarak 16px, padding panel 16px; semua bentang grid menjadi lebar penuh dengan urutan yang sama. Jalur servis menjadi 2 × 2, pilihan barang 3 kolom.

**Ritme jarak:** jarak antar bagian 40px (`block`), antar isian form 20px, form maks 560px, baris judul ke isi 24px (20px di HP). Halaman publik (status customer) memakai lembar terpusat maks 640px; di HP lembar itu tetap bertepi 1px dengan sudut 12px dan jarak 12px dari tepi layar. Login adalah satu kartu 380px di tengah. Dokumen (struk 58mm, nota A5) adalah kertas putih bertepi 1px di atas `ground`, dengan aturan cetak sendiri.

### Named Rules
**The L-Frame Rule.** Sidebar dan bilah atas adalah satu bidang `ground`; tidak ada garis atau beda warna di antara keduanya.

**The One Gutter Rule.** Satu jarak untuk semua baris grid di satu ukuran layar: 24px di desktop, 16px di HP. Baris KPI dan baris grafik tidak boleh punya jarak berbeda.

## Elevation & Depth

Sistem ini datar. Kedalaman datang dari satu langkah warna (bingkai `ground` → lembar `surface`) dan garis 1px, bukan dari bayangan. Lembar, panel, jalur servis, dan tabel tidak punya bayangan sama sekali. Bayangan hanya ada pada benda yang benar-benar melayang di atas halaman (dialog dan toast) dan pada tanda fokus/pilihan.

### Shadow Vocabulary
- **Dialog** (`box-shadow: 0 16px 40px -12px rgb(19 20 22 / .28)`, backdrop `rgb(19 20 22 / .36)`): dialog aksi lain dan konfirmasi batal.
- **Toast** (`box-shadow: 0 12px 28px -12px rgb(19 20 22 / .4)`): pesan singkat di bawah tengah.
- **Glow fokus isian** (`box-shadow: 0 0 0 3px rgb(27 108 128 / .18)`): isian yang sedang diketik, bersama tepi berubah menjadi `text`.
- **Garis pilihan dalam** (`box-shadow: inset 0 0 0 1px #131416`): menebalkan tepi pilihan barang yang terpilih menjadi 2px tanpa menggeser ukuran.

### Named Rules
**The One Tone Step Rule.** Lembar dibedakan dari bingkai hanya oleh warna dan garis 1px; tidak ada bayangan di bawah lembar, panel, atau kartu.

**The Three Levels Rule.** Bingkai → lembar → panel sudah cukup. Jangan ada tingkat keempat (kartu di dalam panel di dalam lembar).

## Shapes

Sudut lembut yang mengecil sesuai ukuran benda: lembar 16px, dialog dan lembar publik di HP 12px, panel/jalur/segmented/toast 8px, tombol/isian/pilihan/nav 6px, tag jalur dan kertas pratinjau 4px, batang tahapan 2px, pil status dan gelembung "Buat TTS" bulat penuh (999px). Avatar berbentuk lingkaran 28px. Semua tepi adalah garis 1px solid; satu-satunya garis putus-putus ada di empty state (`control`) dan pemisah di struk. Ikon memakai Lucide garis 1.5px (2px untuk nav bawah aktif), ukuran 16px (20–22px di HP dan pilihan barang). Logo adalah tiket servis berlekuk dengan garis sobekan putus-putus, berwarna teal.

## Components

### Buttons
Tegas dan sedikit: satu hitam, sisanya putih.
- **Shape:** sudut 6px, tinggi 36px di desktop (32px untuk versi kecil di dalam baris), 44px di HP; tombol utama di bilah aksi HP 48px.
- **Primary:** latar `primary`, teks putih 500, padding 0 14px, ikon 16px di kiri bila ada. Hover `primary-hover`.
- **Secondary:** latar putih, garis 1px `control`, teks tinta 500. Hover `raised`.
- **Ghost:** tanpa garis dan latar; hover `raised` (Batal, Tutup, Tambah baris).
- **Danger:** tombol sekunder dengan teks `negative`.
- **Press / Focus / Disabled:** ditekan turun 1px (0.12s, `cubic-bezier(0.22, 1, 0.36, 1)`); fokus cincin teal 2px offset 2px; nonaktif latar `raised`, teks `faint`, garis `border`. Saat menyimpan: label menjadi "Menyimpan…" dengan ikon putar dan opasitas 0.8.
- **Link teks:** tinta 500 dengan chevron kecil, garis bawah saat hover ("Semua servis ›", "Laporan lengkap ›").

### Inputs / Fields
- **Style:** latar putih, garis 1px `field`, sudut 6px, tinggi 40px (48px dan teks 16px di HP), padding 0 12px. Label di atas, 500; tanda "opsional" kecil abu. Isian uang rata kanan dengan awalan "Rp" abu di dalam isian.
- **Focus:** tepi berubah menjadi `text` dengan glow teal pucat 3px.
- **Error:** tepi `negative`, pesan 12–13px 500 merah dengan ikon segitiga di bawah isian; fokus pindah ke isian pertama yang salah.
- **Pilihan barang (choices):** grid 5 kolom (3 di HP < 640px) kotak 72px (60px di HP) berisi ikon 20px dan label; terpilih = tepi tinta 2px, latar `raised`, teks 500.
- **Segmented (Part/Jasa):** rel `active` 2px, tombol aktif putih.
- **Checkbox:** 18px (22px di HP), warna aksen tinta.
- **Kolom cari:** di bilah atas bergaris `border` (bukan isian kerja); di toolbar daftar bergaris `field`, lebar 300px (penuh dan 44px di HP).

### Navigation
- **Sidebar (desktop):** logo + nama, baris toko, lalu item nav 32px bersudut 6px dengan ikon 16px. Default `muted`; hover `raised` + tinta; halaman aktif `active` + tinta 500. Pengaturan/profil dan kartu pengguna (avatar 28px, nama, peran) di dasar sidebar.
- **Bilah atas:** desktop 48px di atas bingkai dengan cari di tengah; HP 52px menempel dengan logo dan avatar.
- **Navigasi bawah (HP/tablet):** 64px + safe area, latar putih, garis atas `border`. Item: ikon 22px di atas label 12px 500, `muted`; aktif tinta dengan ikon 2px. Item tengah "Buat TTS" adalah gelembung hitam 48 × 30px.
- **Bilah aksi (HP/tablet):** menempel tepat di atas navigasi bawah, latar putih, garis atas; tombol utama 48px mengisi sisa lebar, opsional total di kirinya (13px abu + 18px 500 tabular) atau tombol ikon 48px.

### Tabs
- **Desktop:** baris label 40px dengan jarak 24px di atas garis 1px `border` selebar penuh; jumlah di samping label (`faint`, tinta saat aktif). Tab aktif tinta 500 dengan garis bawah teal 2px selebar label.
- **HP:** baris yang sama bisa digeser ke samping, menembus padding sampai tepi layar, tinggi 44px, jarak 20px, garis bawah teal tetap.

### Tabel ↔ daftar dua baris
- **Desktop:** tanpa kartu di sekelilingnya. Kolom pertama selalu kolom aksi (48px), lalu data. Kepala 36px, label 12px abu tanpa latar; baris 52px dengan garis `rule`, hover `raised`, nomor menjadi tautan ke detail. Kolom angka rata kanan tabular; nilai kosong ditulis "—" `faint`. Laporan menutup tabel dengan baris total (`tfoot`) yang sama dengan laba bersih.
- **HP:** kepala disembunyikan; tiap baris menjadi blok dengan tombol aksi 44px di kiri, lalu nomor + pil status/jumlah di kanan, nama customer, barang · keluhan (14px abu), teknisi kiri + total kanan.

### Aksi baris (aturan user, 11 Nov 2026)
Semua tombol aksi di tabel dan daftar baris berada di **kolom paling kiri**. Satu aksi: tombol sekunder bertulisan langsung ("Lihat", "Tetapkan teknisi", "Kabari"). Lebih dari satu aksi: satu tombol pilihan `···` (32px desktop, 44px HP, `aria-haspopup="menu"`) yang membuka **menu dropdown**: lebar min 208px, latar putih, garis `border`, sudut 8px, bayangan melayang, item 36px (44px di HP) berikon abu, pemisah `rule` sebelum aksi berbahaya (merah `negative`). Menu ditempel ke `body` dan diposisikan dari tombolnya, membalik ke atas bila dekat tepi bawah; tutup dengan klik di luar, Escape, scroll; panah atas/bawah memindah fokus. Daftar "Perlu tindakan" dan "Garansi" memakai kolom aksi kiri selebar 132px (104px di HP) agar teks baris mulai di satu garis.

### Service lane (jalur servis)
Elemen khas Beranda. Satu wadah bergaris 1px sudut 8px, dibagi garis menjadi 4 sel sama lebar (2 × 2 di HP < 640px): Belum ada teknisi, Dikerjakan, Siap diambil, Diambil bulan ini. Tiap sel: nama tahap abu, jumlah 28px 400 dengan catatan kecil 500 ("perlu ditetapkan"), lalu tag 22px berisi tiga digit nomor TTS (Mono 500) dan umurnya ("hari ini", "2 hari"). Tag berumur ≥ 2 hari memakai pasangan warning. Seluruh sel adalah tautan ke daftar terfilter; hover `raised`.

### KPI tabs + grafik
Satu panel lebar penuh. Baris atasnya adalah 3 KPI yang berfungsi sebagai tab seri grafik (Omzet, Laba kotor, Nota servis), dibagi garis 1px. KPI terpilih: label tinta 500, nilai 500, garis bawah teal 2px; lainnya label abu, nilai tetap tinta 400. Di bawahnya rentang tanggal dan tautan laporan, lalu grafik garis 220px: garis `chart-1` 2px lurus tanpa isian dan tanpa titik, garis bantu `grid` 1px, label sumbu 12px `axis-ink`, catatan peristiwa 11px di atas plot dengan garis penunjuk tipis. Hover: garis tegak, titik 3.5px putih bergaris seri, tooltip putih bergaris `grid` sudut 6px. Di HP KPI menjadi baris bertumpuk (label kiri, nilai 20px kanan), yang terpilih berlatar `raised` tanpa garis bawah.

### Rows with actions (Perlu tindakan, Garansi)
Daftar baris minimal 56px (64px di HP) dipisah garis `rule`. Kiri: kolom aksi 132px (104px di HP) berisi satu tombol sekunder penuh lebar ("Tetapkan teknisi", "Kabari" dengan ikon pesan). Setelahnya baris 1 nomor TTS Mono + pil status (atau nama + tenggat `warning`), baris 2 detail abu. Di HP label "Tetapkan teknisi" dipendekkan menjadi "Tetapkan" dan tombol 44px.

### Status pills
Tinggi 22px (24px di HP), padding 0 8px, bulat penuh, teks 12px 500, satu pil per baris. Batal dicoret 1px.

### Track (tahapan servis)
Empat langkah sama lebar (Diterima, Dikerjakan, Selesai, Diambil), jarak 4px, selalu di posisi dan skala yang sama. Tiap langkah: batang 4px sudut 2px, lalu nama 12px dan waktu tabular. Sudah dilalui dan sekarang: batang teal; sekarang: nama tinta 500; belum: batang `active`, nama `faint`.

### Timeline (riwayat)
Titik tinta 7px dengan garis tegak 1px `border` ke titik berikutnya; judul 500 lalu waktu dan pelaku 12px abu.

### Grafik omzet per hari (Laporan)
Batang per hari dengan satu warna teal: hari-hari lalu teal muda `#a9cbd3`, hari ini teal pekat `accent`; lebar batang maks 28px (50% pita), sudut atas 3px. Setiap tanggal diberi label angka hari (hari ini tinta 600). Garis bantu `rule` per 500 rb, garis dasar `control`, garis rata-rata putus-putus tinta 55% yang dijelaskan di keterangan atas grafik (bukan label di dalam plot). Hari tanpa omzet tidak diberi batang (nol yang jujur). Hover/ketuk: kolom berlatar `raised` dan tooltip putih bergaris (tanggal, total, rincian servis dan penjualan). Tinggi 300px desktop, 230px HP; meta kanan menyebut nilai tertinggi.

### Statement (laporan laba rugi)
Daftar kunci-nilai, baris minimal 44px dengan garis `rule`. Subtotal (Omzet, Laba kotor) tinta 500 dengan garis `border`; pengurang ditulis dengan tanda minus sejati "−". Baris terakhir "Laba bersih" label 600 dan nilai 28px tanpa garis.

### Rincian lines (rincian nota)
Desktop: kepala 36px lalu baris grid 6 kolom (hapus 32 · Jenis 120 · Deskripsi · Qty 72 · Harga jual 140 · Modal 140) dengan subtotal kecil rata kanan di bawahnya. Baris Jasa menyembunyikan isian modal dan menampilkan "—". HP: tiap baris bertumpuk (Part/Jasa + hapus 44px, deskripsi penuh, qty | harga, modal), label muncul di atas isian, subtotal kiri-kanan. Panel ringkasan menampilkan Total untuk customer (28px), Modal, dan Laba (`positive`).

### Dialog
Lebar maks 440px, sudut 12px, padding 20px, jarak 16px, judul 16px; aksi rata kanan (ghost lalu aksi). Muncul naik 8px dalam 0.2s.

### Toast
Latar tinta, teks putih, sudut 8px, ikon info; di bawah tengah (di HP di atas bilah aksi), hilang setelah 2.6 detik.

### Empty state
Kotak minimal 96px dengan garis putus-putus `control` sudut 6px, teks tengah: judul tebal ("Tidak ada TTS yang cocok") dan saran 12px.

### Notice
Kotak `raised` sudut 6px dengan ikon `faint` dan teks abu; versi peringatan memakai pasangan warning. Dipakai untuk kunci peran ("TTS hanya bisa diedit saat statusnya Diterima") dan garansi di halaman customer.

## Do's and Don'ts

### Do:
- **Do** letakkan semua pekerjaan di lembar putih yang menjorok 8px dengan sudut 16px dan garis 1px `border` di atas bingkai `ground`.
- **Do** pakai tepat satu tombol `primary` hitam per layar; aksi lain putih bergaris `control` atau ghost.
- **Do** tulis nomor TTS/JL dengan IBM Plex Mono 500 dan semua angka dengan tabular figures, uang rata kanan dengan format `Rp1.100.000`.
- **Do** tulis status sebagai kata di dalam pil pucat dengan pasangan `pill-*-bg`/`pill-*-ink`.
- **Do** beri isian garis `field` (3.17:1) dan tinggi 48px dengan teks 16px di HP.
- **Do** jaga target sentuh minimal 44 × 44px di HP (tombol, tab, tautan, ikon hapus, item navigasi).
- **Do** ubah tabel menjadi daftar dua baris dan baris tab yang bisa digeser di bawah 1024px.
- **Do** taruh aksi utama HP di bilah aksi yang menempel di atas navigasi bawah.
- **Do** taruh tombol aksi baris di kolom paling kiri; lebih dari satu aksi dikumpulkan dalam menu pilihan `···`.

### Don't:
- **Don't** pakai teal untuk tombol, teks, atau latar bidang besar; teal hanya untuk garis grafik, garis bawah tab/KPI aktif, batang tahapan, logo, dan fokus.
- **Don't** kembalikan hijau sebagai warna merek seperti versi pertama yang ditolak; hijau hanya `positive` (laba, sukses) dan pil Selesai.
- **Don't** beri bayangan pada lembar, panel, atau jalur servis; bayangan hanya untuk dialog, toast, dan fokus.
- **Don't** pasang garis antara sidebar dan bilah atas.
- **Don't** buat kartu statistik berikon, aksen di setiap tombol, atau kartu di dalam kartu (tingkat keempat).
- **Don't** pakai IBM Plex Mono untuk nama, harga, tanggal, atau label.
- **Don't** tulis nilai KPI yang tidak terpilih dengan warna abu; tetap tinta dengan berat 400.
- **Don't** bedakan status hanya dengan warna.
- **Don't** taruh tombol aksi di ujung kanan baris atau berjajar lebih dari satu tombol per baris.
