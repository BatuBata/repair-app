# Mockup aplikasi — surface brief

Scope: mockup HTML statis 10 layar inti (PRD.md §10.1, §10.2) di `mockup/`. Mode: Operate. Redesign 11 Nov 2026: versi pertama ditolak user ("jelek, tidak profesional atau elegan"; hijau terlalu banyak, terlalu kotak-kotak, huruf kurang rapi, terlalu polos).

Audiens dan tugas: teknisi membuat TTS dari HP di lokasi customer; owner/admin menetapkan teknisi, mengisi rincian, menerbitkan nota, memantau garansi dan laba, dari HP maupun laptop; customer membuka halaman status. Harus terbaca di luar ruangan, tidak padat, tidak mainan, tidak seperti template admin generik.

## Direction contract

THESIS: Alat kerja toko yang tenang dan mahal: bingkai abu-biru tipis berbentuk L, pekerjaan di atas lembar putih, warna hanya pada data dan status. Menolak dashboard kit (kartu statistik berikon, aksen di setiap tombol, kartu bersarang) dan versi pertama yang hijau di mana-mana.

OWN-WORLD: Bingkai #f2f5f6 tanpa garis antara sidebar dan bilah atas; lembar putih inset 8px, sudut 16px, tepi 1px, tanpa bayangan. IBM Plex Sans 400/500/600, IBM Plex Mono khusus nomor TTS/JL. Tombol utama hitam #131416, sekunder putih bergaris. Satu aksen teal #1b6c80 hanya pada garis grafik, garis bawah tab aktif, batang tahapan, dan logo tiket. Status sebagai pil pucat dengan teks gelap senada.

STORY: Owner membuka Beranda dan langsung membaca jalur servis (berapa yang belum ada teknisi, dikerjakan, siap diambil, dengan umur tiap TTS), lalu menindaklanjuti dari daftar "Perlu tindakan". Teknisi mengisi 4 isian dan melihat struknya terbentuk di sebelah. Customer melihat status dan nota tanpa angka modal.

FIRST VIEWPORT: Beranda 1440: judul "Beranda" 22px dengan satu kalimat temuan; di bawahnya jalur servis empat sel bergaris pembagi berisi angka 28px dan tag nomor TTS beserta umurnya; lalu dua panel (Perlu tindakan 7 kolom, Garansi 5 kolom); lalu KPI sebagai tab grafik omzet 1–11 Nov. Tombol hitam "Buat TTS" di kanan judul.

FORM: tasteskill app sheet `framed-sheet` (seed 1526667476), dipilih user di antara dua gaya yang dibagikan `draw.mjs --app --kind commerce`. Wajah produk: bingkai diwarnai satu langkah abu-biru dari aksen; elemen khas: jalur servis dengan tag umur TTS. Patokan kualitas tetap Moka POS/Majoo (PRODUCT.md). Seed key impeccable sebelumnya 486f9203 digantikan oleh draw tasteskill atas permintaan user.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
